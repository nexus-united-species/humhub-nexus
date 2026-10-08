<?php

namespace humhub\modules\nexusVideoFaststart;

use humhub\modules\file\models\File;
use Yii;

class Events
{
    /** Pfad zu ffmpeg/ffprobe aus den Moduleinstellungen (Module::$ffmpeg / Module::$ffprobe). */
    private static function programm(string $name): string
    {
        return (string)\Yii::$app->getModule('nexus-video-faststart')->$name;
    }

    /** Dateiname der Videonachrichten aus nexus-voice (Aufnahme im Browser). */
    private const VIDEONACHRICHT = 'Videonachricht-';

    /**
     * Zwei getrennte Probleme, beide am selben Beitrag gefunden (Josh,
     * 11.09.2026, content#604 -- ein per ssstik.io heruntergeladenes
     * TikTok-Video, "am Handy sichtbar, am PC Fehler"):
     *
     * 1. Der "moov"-Atom (das Inhaltsverzeichnis der Videodatei) lag am
     *    Dateiende statt am Anfang. Browser am PC lesen dieses
     *    Verzeichnis zuerst und brechen bei einer grossen Datei ab, bevor
     *    sie ueberhaupt zu spielen anfangen -- mobile Player sind da
     *    nachsichtiger.
     * 2. Das Video war in HDR kodiert (H.264 "High 10", 10 Bit,
     *    BT.2020-Farbraum) -- ein Format, das Desktop-Browser praktisch
     *    nie abspielen koennen (nur 8-Bit H.264 ist dort verlaesslich
     *    unterstuetzt), waehrend Handys mit HDR-faehiger Hardware es
     *    anstandslos wiedergeben. Erst nach Beheben von Punkt 1 ist das
     *    hier zutage getreten -- "Seite refresht, aber Video geht nicht".
     *
     * Deshalb zwei Weges: normale (8-Bit) Videos bekommen nur einen
     * verlustfreien, schnellen Faststart-Remux (Bild/Ton unveraendert).
     * HDR/10-Bit-Videos werden zusaetzlich auf 8-Bit/Standardfarbraum
     * heruntergerechnet (per Tonemapping, nicht einfaches Abschneiden der
     * Farbtiefe -- sonst wirkt das Bild flau). Schlaegt irgendein Schritt
     * fehl (kaputte Datei, fremdes Format, ffmpeg-Fehler), bleibt die
     * Originaldatei unangetastet -- ein hochgeladenes Video darf dadurch
     * nie verloren gehen.
     */
    public static function onAfterNewStoredFile($event): void
    {
        /** @var File $file */
        $file = $event->sender;

        if (!str_starts_with((string)$file->mime_type, 'video/')) {
            return;
        }

        $pfad = $file->store->get();
        if (!is_file($pfad) || filesize($pfad) === 0) {
            return;
        }

        if (!is_file(self::programm('ffmpeg')) || !is_file(self::programm('ffprobe'))) {
            Yii::warning('nexus-video-faststart: ffmpeg/ffprobe nicht gefunden (' . self::programm('ffmpeg') . ', '
                . self::programm('ffprobe') . ')', 'nexus-video-faststart');
            return;
        }

        // Videonachrichten (Josh, 02.10.2026) werden IMMER neu kodiert: Browser nehmen je nach
        // Hersteller WebM/VP8 oder MP4 mit VP9 auf -- beides spielt das iPhone nicht zuverlaessig.
        // H.264/AAC in MP4 spielt ueberall; dazu stimmt danach auch die Laengenangabe.
        $istVideonachricht = str_starts_with((string)$file->file_name, self::VIDEONACHRICHT);
        $istHdr = !$istVideonachricht && self::istZehnBitOderHdr($pfad);
        // H.265/HEVC (neuere Handys, viele TikTok-Downloads, iPhone-.mov) spielen viele PC-Browser
        // nicht ab -- am 02.10.2026 nachtraeglich bei einem TikTok-Video gefunden. Wird wie HDR neu
        // kodiert, nur ohne Farbumrechnung (Josh: "bitte einbauen").
        $istHevc = !$istVideonachricht && !$istHdr && self::videoCodec($pfad) === 'hevc';
        $tempPfad = $pfad . '.faststart-tmp-' . uniqid();

        if ($istVideonachricht) {
            $befehl = self::videonachrichtBefehl($pfad, $tempPfad);
        } elseif ($istHdr) {
            $befehl = self::tonemapBefehl($pfad, $tempPfad);
        } elseif ($istHevc) {
            $befehl = self::h264Befehl($pfad, $tempPfad);
        } else {
            $befehl = self::kopierBefehl($pfad, $tempPfad);
        }

        exec($befehl, $ausgabe, $rueckgabecode);

        if ($rueckgabecode !== 0 || !is_file($tempPfad) || filesize($tempPfad) === 0) {
            Yii::warning(
                "nexus-video-faststart: Umwandlung fehlgeschlagen fuer file#{$file->id} (hdr=" . ($istHdr ? 'ja' : 'nein') . ", Code $rueckgabecode): " . implode("\n", $ausgabe),
                'nexus-video-faststart'
            );
            if (is_file($tempPfad)) {
                @unlink($tempPfad);
            }
            return;
        }

        if (!@rename($tempPfad, $pfad)) {
            Yii::warning("nexus-video-faststart: Konnte Datei nicht ersetzen fuer file#{$file->id}", 'nexus-video-faststart');
            @unlink($tempPfad);
            return;
        }

        @chmod($pfad, 0644);

        $neu = [
            'size' => filesize($pfad),
            'hash_sha1' => sha1_file($pfad),
        ];
        if ($istVideonachricht) {
            // Inhalt ist jetzt MP4 -- Name und Typ passend dazu, sonst bekaeme der Browser "webm" angesagt.
            $neu['file_name'] = preg_replace('/\.[A-Za-z0-9]+$/', '', (string)$file->file_name) . '.mp4';
            $neu['mime_type'] = 'video/mp4';
        } elseif (($istHevc || $istHdr) && $file->mime_type !== 'video/mp4') {
            // z. B. iPhone-.mov: Inhalt ist jetzt MP4 -- Typ und Endung passend dazu.
            $neu['file_name'] = preg_replace('/\.(mov|qt|m4v)$/i', '', (string)$file->file_name) . '.mp4';
            $neu['mime_type'] = 'video/mp4';
        }
        $file->updateAttributes($neu);

        Yii::info("nexus-video-faststart: Video file#{$file->id} umgewandelt (hdr=" . ($istHdr ? 'ja' : 'nein') . ', hevc=' . ($istHevc ? 'ja' : 'nein') . ', videonachricht=' . ($istVideonachricht ? 'ja' : 'nein') . ").", 'nexus-video-faststart');
    }

    /**
     * Prueft die erste Videospur auf 10-Bit-Farbtiefe (z.B. "yuv420p10le")
     * oder ein "10"-Profil (z.B. "High 10") -- beides zuverlaessige
     * Anzeichen fuer HDR-Aufnahmen, die Desktop-Browser nicht abspielen
     * koennen. Bei jedem Fehler (kein gueltiges Video, ffprobe schlaegt
     * fehl) lieber "nein" annehmen und den schnellen, unveraendernden Weg
     * nehmen als eine harmlose Datei unnoetig neu zu kodieren.
     */
    /** Name des Video-Codecs der ersten Videospur ("h264", "hevc", ...); leer bei Fehler. */
    private static function videoCodec(string $pfad): string
    {
        exec(sprintf(
            '%s -v error -select_streams v:0 -show_entries stream=codec_name -of csv=p=0 %s 2>&1',
            escapeshellarg(self::programm('ffprobe')),
            escapeshellarg($pfad)
        ), $ausgabe, $rueckgabecode);
        return $rueckgabecode === 0 ? strtolower(trim((string)($ausgabe[0] ?? ''))) : '';
    }

    /**
     * H.265 -> H.264 (High, 8 Bit) + AAC. Die laengere Seite hoechstens 1920 px: 4K-Handyvideos
     * wuerden sonst riesig und das Hochladen dauerte noch laenger. "veryfast" + CRF 23 wie beim
     * nachtraeglich reparierten TikTok-Video (17 MB, 1:43 Min. -> 46 s Rechenzeit).
     */
    private static function h264Befehl(string $pfad, string $tempPfad): string
    {
        return sprintf(
            'timeout 900 %s -y -i %s -vf %s -c:v libx264 -profile:v high -pix_fmt yuv420p -preset veryfast -crf 23 -c:a aac -b:a 128k -movflags +faststart -f mp4 %s 2>&1',
            escapeshellarg(self::programm('ffmpeg')),
            escapeshellarg($pfad),
            escapeshellarg("scale='if(gt(iw,ih),min(1920,iw),-2)':'if(gt(iw,ih),-2,min(1920,ih))'"),
            escapeshellarg($tempPfad)
        );
    }

    private static function istZehnBitOderHdr(string $pfad): bool
    {
        $befehl = sprintf(
            '%s -v error -select_streams v:0 -show_entries stream=pix_fmt,profile -of csv=p=0 %s 2>&1',
            escapeshellarg(self::programm('ffprobe')),
            escapeshellarg($pfad)
        );
        exec($befehl, $ausgabe, $rueckgabecode);
        if ($rueckgabecode !== 0 || empty($ausgabe)) {
            return false;
        }

        $zeile = strtolower(implode(' ', $ausgabe));
        return str_contains($zeile, '10le') || str_contains($zeile, '10be') || str_contains($zeile, 'high 10') || str_contains($zeile, 'main 10');
    }

    /**
     * Videonachricht: hoechstens 480 px breit, H.264 (Main) + AAC mono, Inhaltsverzeichnis vorn.
     * Der Ton wird auf eine normale Lautstaerke gebracht (loudnorm, -16 LUFS wie bei Sprach-Apps):
     * Browser nehmen Kameraton oft sehr leise auf -- Josh, 02.10.2026: "Ton sehr leise, obwohl voll
     * aufgedreht".
     * "veryfast" haelt die Wartezeit beim Hochladen kurz (3 Minuten ≈ wenige Sekunden Rechenzeit
     * bei dieser kleinen Bildgroesse); CRF 26 reicht fuer Gesichter im kleinen Kreis.
     */
    private static function videonachrichtBefehl(string $pfad, string $tempPfad): string
    {
        return sprintf(
            'timeout 300 %s -y -i %s -vf %s -af %s -c:v libx264 -profile:v main -pix_fmt yuv420p -preset veryfast -crf 26 -c:a aac -b:a 64k -ac 1 -ar 48000 -movflags +faststart -f mp4 %s 2>&1',
            escapeshellarg(self::programm('ffmpeg')),
            escapeshellarg($pfad),
            escapeshellarg("scale='min(480,iw)':-2"),
            escapeshellarg('loudnorm=I=-16:TP=-1.5:LRA=11'),
            escapeshellarg($tempPfad)
        );
    }

    private static function kopierBefehl(string $pfad, string $tempPfad): string
    {
        return sprintf(
            'timeout 120 %s -y -i %s -c copy -movflags +faststart -f mp4 %s 2>&1',
            escapeshellarg(self::programm('ffmpeg')),
            escapeshellarg($pfad),
            escapeshellarg($tempPfad)
        );
    }

    /**
     * Tonemapping (statt reinem Abschneiden der Farbtiefe) rechnet die
     * hellen HDR-Lichter sinnvoll in den kleineren SDR-Wertebereich um --
     * ein einfaches "auf 8 Bit stutzen" liesse helle Stellen ausgefressen
     * und flau wirken. CRF 20 als guter Kompromiss zwischen Qualitaet und
     * Dateigroesse, an einem echten Testvideo (106s) visuell geprueft.
     */
    private static function tonemapBefehl(string $pfad, string $tempPfad): string
    {
        return sprintf(
            'timeout 600 %s -y -i %s -vf %s -c:v libx264 -profile:v high -pix_fmt yuv420p -preset veryfast -crf 20 -c:a copy -movflags +faststart -f mp4 %s 2>&1',
            escapeshellarg(self::programm('ffmpeg')),
            escapeshellarg($pfad),
            escapeshellarg('zscale=t=linear:npl=100,tonemap=hable:desat=0,zscale=p=bt709:t=bt709:m=bt709,format=yuv420p'),
            escapeshellarg($tempPfad)
        );
    }
}
