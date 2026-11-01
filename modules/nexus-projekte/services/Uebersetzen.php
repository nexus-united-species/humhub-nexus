<?php

namespace nexus\modules\projekte\services;

use nexus\modules\projekte\models\Antwort;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Stimme;
use Yii;

/**
 * Inhalte von Projekten und lesbare Dateien in die Sprache der lesenden Person uebersetzen, nur auf Knopfdruck.
 * Nutzt Zugang und Speicher von nexus-translate: jeder Text je Sprache nur einmal, nach einer Aenderung neu.
 * Ohne dieses Modul erscheint kein Knopf. Textaenderungen eines Vorschlags (vorher/nachher) bleiben bewusst
 * im Original: dort kommt es auf den genauen Wortlaut an.
 */
class Uebersetzen
{
    private const ERKENNUNG = 'humhub\modules\nexusTranslate\SprachErkennung';
    private const SPEICHER = 'humhub\modules\nexusTranslate\UebersetzungsSpeicher';

    /** Gemessen: 40.000 Zeichen brauchten 75 s, 76.000 Zeichen scheiterten auch nach 350 s und drei Versuchen. */
    public const MAX_ZEICHEN_DATEI = 50000;

    private const KURZ = 'This is a short label from a project board: output only the translation.';

    public static function moeglich(): bool
    {
        return class_exists(self::SPEICHER) && Yii::$app->hasModule('nexus-translate') && self::ziel() !== null;
    }

    public static function ziel(): ?string
    {
        return class_exists(self::ERKENNUNG) ? (self::ERKENNUNG)::leser() : null;
    }

    /** Attribut fuer ein Element, dessen Text der Knopf austauscht. */
    public static function attr(string $art, int $id): string
    {
        return ' data-nx-pj-tr="' . $art . ':' . $id . '"';
    }

    /**
     * Lohnt sich der Knopf? Ja, wenn ein Text sicher in einer anderen Sprache geschrieben ist. Kurze Texte erkennt die
     * Sprachprobe nicht; sind alle so kurz, gilt die Annahme, dass sie deutsch sind (wie die meisten Inhalte im Portal).
     *
     * @param array<int, string|null> $texte
     */
    public static function noetig(array $texte): bool
    {
        if (!self::moeglich()) {
            return false;
        }
        $ziel = self::ziel();
        $vorhanden = $eigene = false;
        foreach ($texte as $text) {
            if (trim((string)$text) === '') {
                continue;
            }
            $vorhanden = true;
            $von = (self::ERKENNUNG)::erkenne($text);
            if ($von !== null && $von !== $ziel) {
                return true;
            }
            $eigene = $eigene || $von === $ziel;
        }
        return $vorhanden && !$eigene && $ziel !== 'de';
    }

    /** @return array<int, string|null> alle uebersetzbaren Texte einer Kachel */
    public static function texteDerKarte(Karte $karte): array
    {
        $stimmen = Stimme::find()->select(['id', 'text'])->where(['karte_id' => $karte->id])->andWhere(['not', ['text' => null]])->asArray()->all();
        return array_merge(
            [$karte->titel, $karte->beschreibung, $karte->zusammenfassung, $karte->begruendung],
            array_map(fn($s) => $s->text, $karte->schritte),
            array_column($stimmen, 'text'),
            Antwort::find()->select('text')->where(['stimme_id' => array_column($stimmen, 'id')])->column()
        );
    }

    /** Liefert den Text in der Sprache der lesenden Person; ist er schon in dieser Sprache, ohne KI-Aufruf unveraendert. */
    public static function text(string $typ, int $id, string $text, bool $kurz = false): string
    {
        $ziel = self::ziel();
        if ($ziel === null || trim($text) === '' || (self::ERKENNUNG)::erkenne($text) === $ziel) {
            return $text;
        }
        return (self::SPEICHER)::markdown($typ, $id, $text, $ziel, $kurz ? self::KURZ : '');
    }
}
