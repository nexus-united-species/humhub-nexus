<?php

namespace nexus\modules\projekte\services;

use humhub\modules\cfiles\models\Folder;
use humhub\modules\content\models\Content;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Datei;
use nexus\modules\projekte\models\Karte;
use Throwable;
use Yii;
use yii\web\UploadedFile;

/**
 * Dateien an Vorschlaegen. Sie liegen im Dateibereich des Kreises unter "Projekte/<Projekt>/<Kachel>"
 * -- gleiche Leserechte wie der Kreis, auffindbar unter "Dateien" und in der Nextcloud-Spiegelung.
 * Jedes Hochladen ist eine neue Fassung; alte Fassungen bleiben, damit man sieht, was sich aendert.
 */
class Dateien
{
    public const ORDNER = 'Projekte';
    public const MAX_BYTES = 50 * 1024 * 1024;

    public static function verfuegbar(): bool
    {
        return class_exists(Folder::class) && Yii::$app->hasModule('cfiles');
    }

    /** Hochladen geht nur, wenn der Kreis den Dateibereich eingeschaltet hat */
    public static function hochladbar(Space $kreis): bool
    {
        return self::verfuegbar() && $kreis->moduleManager->isEnabled('cfiles');
    }

    /** @return array<int, Datei[]> Fassung => Dateien, neueste zuerst */
    public static function fassungen(Karte $karte): array
    {
        $liste = [];
        foreach (Datei::find()->where(['karte_id' => $karte->id])->with('kreisDatei.baseFile')->orderBy(['fassung' => SORT_DESC, 'id' => SORT_ASC])->all() as $d) {
            $liste[(int)$d->fassung][] = $d;
        }
        return $liste;
    }

    public static function naechsteFassung(Karte $karte): int
    {
        return (int)Datei::find()->where(['karte_id' => $karte->id])->max('fassung') + 1;
    }

    /**
     * @param UploadedFile[] $dateien
     * @return array{0: int, 1: string[]} [Fassung, Fehlermeldungen]
     */
    public static function hochladen(Karte $karte, array $dateien, string $hinweis, User $ich): array
    {
        $fehler = [];
        $fassung = self::naechsteFassung($karte);
        $ordner = self::ordner($karte);
        $gespeichert = 0;
        foreach (array_slice($dateien, 0, Datei::MAX_JE_FASSUNG) as $datei) {
            if ($datei->size > self::MAX_BYTES) {
                $fehler[] = $datei->name . ': ' . Texte::t('datei_zu_gross');
                continue;
            }
            // Eindeutiger Name je Fassung: sonst ersetzte das Dateimodul die aeltere Fassung
            $datei->name = 'F' . $fassung . ' - ' . self::sauber($datei->name, 200);
            try {
                $kreisDatei = $ordner->addUploadedFile($datei);
            } catch (Throwable $e) {
                $fehler[] = $datei->name . ': ' . $e->getMessage();
                continue;
            }
            if ($kreisDatei->isNewRecord || $kreisDatei->hasErrors()) {
                $fehler[] = $datei->name . ': ' . implode(' ', $kreisDatei->getErrorSummary(true));
                continue;
            }
            (new Datei([
                'karte_id' => $karte->id, 'cfile_id' => $kreisDatei->id, 'fassung' => $fassung,
                'hinweis' => mb_substr(trim($hinweis), 0, 255) ?: null, 'created_by' => $ich->id,
                'created_at' => date('Y-m-d H:i:s'),
            ]))->save(false);
            $gespeichert++;
        }
        return [$gespeichert > 0 ? $fassung : 0, $fehler];
    }

    /**
     * Eine Datei loeschen: der Eintrag an der Kachel UND die Datei im Dateibereich des Kreises (weich geloescht --
     * HumHub raeumt sie in der Nacht endgueltig weg, bis dahin laesst sie sich im Kreis aus dem Papierkorb holen).
     * Ein Fehler beim Dateibereich haelt das Entfernen an der Kachel nicht auf.
     */
    public static function loeschen(Datei $datei): void
    {
        $kreisDatei = $datei->kreisDatei;
        $datei->delete();
        if ($kreisDatei !== null && (int)$kreisDatei->content->state !== Content::STATE_DELETED) {
            try {
                $kreisDatei->content->delete();
            } catch (Throwable $e) {
                Yii::warning('Datei ' . $datei->id . ' im Dateibereich nicht geloescht: ' . $e->getMessage(), 'nexus-projekte');
            }
        }
    }

    private static function ordner(Karte $karte): Folder
    {
        $kreis = $karte->projekt->kreis;
        $ordner = Folder::getOrInitRoot($kreis);
        foreach ([self::ORDNER, self::sauber($karte->projekt->titel, 100), self::sauber('#' . $karte->id . ' ' . $karte->titel, 100)] as $name) {
            $gefunden = $ordner->findFolderByName($name);
            $ordner = $gefunden !== null ? self::lebendig($gefunden) : self::neuerOrdner($ordner, $name);
        }
        return $ordner;
    }

    /**
     * findFolderByName findet auch einen als geloescht markierten Ordner. Neue Dateien landeten dann darin --
     * und HumHub raeumt einmal am Tag alles Geloeschte endgueltig weg, samt Inhalt (PurgeDeletedContents).
     * So verschwanden am 23./24.10.2026 18 Entwuerfe der Kalender-Kacheln. Deshalb den Ordner wieder hervorholen.
     */
    private static function lebendig(Folder $ordner): Folder
    {
        if ((int)$ordner->content->state === Content::STATE_DELETED) {
            $ordner->content->updateAttributes(['state' => Content::STATE_PUBLISHED]);
        }
        return $ordner;
    }

    private static function neuerOrdner(Folder $eltern, string $name): Folder
    {
        $neu = $eltern->newFolder($name);
        $neu->silentContentCreation = true;
        if (!$neu->save()) {
            throw new \RuntimeException('Ordner ' . $name . ': ' . implode(' ', $neu->getErrorSummary(true)));
        }
        return $neu;
    }

    private static function sauber(string $name, int $max): string
    {
        $name = trim((string)preg_replace('#[/\\\\:*?"<>|\x00-\x1F]+#u', ' ', $name));
        return mb_substr((string)preg_replace('/\s+/u', ' ', $name), 0, $max) ?: 'Datei';
    }
}
