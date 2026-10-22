<?php

namespace nexus\modules\projekte\services;

use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Datei;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Option;
use nexus\modules\projekte\models\Stimme;
use Yii;
use yii\web\UploadedFile;

/**
 * Bild-Auswahl: mehrere Entwuerfe zu einer Kachel, Abstimmung im Konsent je Entwurf (Wunsch aus dem
 * Kalender-Projekt, Oktober 2026: "mehrere Bilder anlegen und dann eine Abstimmung, sodass wir Konsent haben").
 *
 * Ablauf: In "Offen"/"In Arbeit" stellt jedes Kreismitglied Entwuerfe ein. "Zur Abstimmung geben" startet
 * die Runde. Zu jedem Entwurf gibt jede Person Zustimmung, Bedenken oder einen schwerwiegenden Einwand
 * (die beiden letzten mit Begruendung). In Frage kommen nur Entwuerfe ohne Einwand und mit mindestens einer
 * Zustimmung; davon gewinnt der mit den meisten Zustimmungen, bei Gleichstand waehlt die Leitung. Hat kein
 * Entwurf das geschafft, geht die Kachel zurueck ins Sammeln.
 */
class Auswahl
{
    /** art => [Text, Symbol, Farbklasse, Begruendung Pflicht] -- dieselben Texte wie beim Konsent */
    public const ARTEN = [
        'zustimmung' => ['opt_zustimmung', 'check', 'nx-pj-gruen', false],
        'bedenken' => ['opt_bedenken', 'exclamation-circle', 'nx-pj-gelb', true],
        'einwand' => ['opt_einwand', 'hand-paper-o', 'nx-pj-rot', true],
    ];

    public const MAX_TITEL = 120;

    /** @return Option[] in der Reihenfolge des Einstellens (A, B, C …) */
    public static function optionen(Karte $karte): array
    {
        return Option::find()->where(['karte_id' => $karte->id])->with(['datei.kreisDatei.baseFile', 'autor'])->orderBy('id')->all();
    }

    public static function buchstabe(int $index): string
    {
        return $index < 26 ? chr(65 + $index) : (string)($index + 1);
    }

    /** @return array<int, string> Option-ID => Buchstabe */
    public static function buchstaben(array $optionen): array
    {
        $liste = [];
        foreach (array_values($optionen) as $i => $o) {
            $liste[(int)$o->id] = self::buchstabe($i);
        }
        return $liste;
    }

    /** @return array<int, Stimme[]> Option-ID => Stimmen der laufenden Runde */
    public static function stimmen(Karte $karte): array
    {
        $liste = [];
        foreach (Stimme::find()->where(['karte_id' => $karte->id, 'runde' => $karte->runde])->andWhere(['>', 'option_id', 0])
                     ->with('autor')->orderBy('id')->all() as $s) {
            $liste[(int)$s->option_id][] = $s;
        }
        return $liste;
    }

    /** Wie viele Menschen in dieser Runde zu mindestens einem Entwurf etwas gesagt haben */
    public static function teilnehmer(Karte $karte): int
    {
        return (int)Stimme::find()->select('user_id')->distinct()
            ->where(['karte_id' => $karte->id, 'runde' => $karte->runde])->andWhere(['>', 'option_id', 0])->count();
    }

    /**
     * Stand, wenn die Runde jetzt endet.
     * @param Option[] $optionen
     * @return array{zahlen: array<int, array<string, int>>, waehlbar: int[], sieger: int[]}
     */
    public static function auswertung(Karte $karte, array $optionen): array
    {
        $stimmen = self::stimmen($karte);
        $zahlen = [];
        $waehlbar = [];
        foreach ($optionen as $o) {
            $z = array_fill_keys(array_keys(self::ARTEN), 0);
            foreach ($stimmen[(int)$o->id] ?? [] as $s) {
                if (isset($z[$s->art])) {
                    $z[$s->art]++;
                }
            }
            $zahlen[(int)$o->id] = $z;
            if ($z['einwand'] === 0 && $z['zustimmung'] > 0) {
                $waehlbar[] = (int)$o->id;
            }
        }
        $beste = $waehlbar === [] ? 0 : max(array_map(fn($id) => $zahlen[$id]['zustimmung'], $waehlbar));
        $sieger = array_values(array_filter($waehlbar, fn($id) => $zahlen[$id]['zustimmung'] === $beste));
        return ['zahlen' => $zahlen, 'waehlbar' => $waehlbar, 'sieger' => $sieger];
    }

    /** Kurzer Stand fuer Ergebnis und Nachrichten, z. B. "5 × Zustimmung, 1 × Bedenken, 0 × Einwand" */
    public static function zahlenText(array $z): string
    {
        return sprintf('%d × %s, %d × %s, %d × %s',
            $z['zustimmung'], Texte::t('opt_zustimmung'), $z['bedenken'], Texte::t('opt_bedenken'), $z['einwand'], Texte::t('opt_einwand'));
    }

    /** Nur echte Bilder -- am Inhalt geprueft, nicht an der Angabe des Browsers */
    public static function istBild(UploadedFile $datei): bool
    {
        $info = @getimagesize($datei->tempName);
        return is_array($info) && in_array($info[2] ?? 0, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true);
    }

    /**
     * Einen Entwurf einstellen: das Bild wird eine Datei der Kachel (eigene Fassung), dazu die Option.
     * @return array{0: Option|null, 1: string}  [Entwurf, Fehlermeldung]
     */
    public static function einstellen(Karte $karte, UploadedFile $datei, string $titel, User $ich): array
    {
        if (!self::istBild($datei)) {
            return [null, Texte::t('auswahl_nur_bilder')];
        }
        if ((int)Option::find()->where(['karte_id' => $karte->id])->count() >= Option::MAX_JE_KARTE) {
            return [null, Texte::t('auswahl_voll')];
        }
        $titel = mb_substr(trim($titel), 0, self::MAX_TITEL);
        if ($titel === '') {
            $titel = mb_substr(pathinfo($datei->name, PATHINFO_FILENAME), 0, self::MAX_TITEL) ?: Texte::t('auswahl_entwurf');
        }
        [$fassung, $fehler] = Dateien::hochladen($karte, [$datei], $titel, $ich);
        if ($fassung === 0) {
            return [null, implode('; ', $fehler)];
        }
        $gespeichert = Datei::find()->where(['karte_id' => $karte->id, 'fassung' => $fassung])->orderBy(['id' => SORT_DESC])->one();
        $option = new Option([
            'karte_id' => $karte->id, 'datei_id' => $gespeichert->id, 'titel' => $titel,
            'gewaehlt' => 0, 'created_by' => $ich->id, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $option->save(false);
        return [$option, ''];
    }

    /** Ergebnis festhalten: der Entwurf wird zum Bild der Kachel, die Kachel ist fertig */
    public static function festhalten(Karte $karte, Option $gewaehlt, array $zahlen, string $buchstabe): void
    {
        Option::updateAll(['gewaehlt' => 0], ['karte_id' => $karte->id]);
        $gewaehlt->updateAttributes(['gewaehlt' => 1]);
        $karte->ergebnis = 'angenommen';
        // Deutsch wie die Nachrichten (Nachrichten.php), egal in welcher Sprache die Leitung das Portal nutzt
        $sprache = Yii::$app->language;
        Yii::$app->language = 'de';
        try {
            $karte->ergebnis_text = sprintf('%s %s „%s“ – %s', Texte::t('auswahl_entwurf'), $buchstabe, $gewaehlt->titel, self::zahlenText($zahlen));
        } finally {
            Yii::$app->language = $sprache;
        }
        $karte->ergebnis_am = date('Y-m-d H:i:s');
        $karte->spalte = 'fertig';
        $karte->save(false);
    }

    /** Wieder geoeffnet: kein Entwurf gilt mehr als gewaehlt */
    public static function zuruecksetzen(Karte $karte): void
    {
        Option::updateAll(['gewaehlt' => 0], ['karte_id' => $karte->id]);
        $karte->ergebnis = $karte->ergebnis_text = $karte->ergebnis_am = null;
    }

    public static function gewaehlt(array $optionen): ?Option
    {
        foreach ($optionen as $o) {
            if ($o->gewaehlt) {
                return $o;
            }
        }
        return null;
    }
}
