<?php

namespace humhub\modules\nexusTranslate;

use humhub\helpers\Html;
use humhub\modules\nexusTranslate\models\TranslationCache;
use Throwable;
use Yii;

/**
 * Kalendereintraege in der Sprache des Lesers (Josh, 26.09.2026: "Kalendereintraege werden nicht
 * uebersetzt").
 *
 * - TITEL erscheinen an vielen Stellen (Stream-Kopf, "Naechste Termine", Kalenderansicht als JSON,
 *   Aktivitaeten). Sie werden deshalb wie Kreisnamen behandelt: beim Speichern einmal uebersetzt
 *   und ueber die Austauschliste von KreisUebersetzung beim Ausliefern ersetzt.
 * - BESCHREIBUNG im Stream: automatische Uebersetzung wie bei Beitraegen (TranslateLink, Typ "cal").
 * - Wiederkehrende Termine: HumHub legt fuer Wiederholungen eigene Eintraege an
 *   (parent_event_id = Serienanfang). Uebersetzt und gespeichert wird nur EINMAL je Serie.
 * - Die Sprache wird an Titel + Beschreibung zusammen erkannt (Titel allein sind zu kurz);
 *   ist sie nicht sicher erkennbar, gilt Deutsch -- die Sprache fast aller Termine.
 */
class TerminUebersetzung
{
    public const KLASSE = 'humhub\modules\calendar\models\CalendarEntry';
    /** Kuerzere Titel werden nicht ausgetauscht -- sie kaemen sonst auch mitten in fremden Texten vor. */
    private const MIN_TITEL = 10;

    public static function istTermin($record): bool
    {
        return is_object($record) && is_a($record, self::KLASSE);
    }

    /** Id des Serienanfangs (bei Einzelterminen der Termin selbst). */
    public static function wurzelId($termin): int
    {
        return (int)($termin->parent_event_id ?: $termin->id);
    }

    public static function quelle($termin): string
    {
        return SprachErkennung::erkenne($termin->title . "\n" . $termin->description) ?? 'de';
    }

    /** Uebersetzt den Titel eines Serienanfangs/Einzeltermins in alle anderen Sprachen. */
    public static function aktualisiere($termin): void
    {
        if ($termin->parent_event_id || mb_strlen(trim((string)$termin->title)) < self::MIN_TITEL) {
            return;
        }
        $quelle = self::quelle($termin);
        foreach (array_diff(SprachErkennung::SPRACHEN, [$quelle]) as $ziel) {
            // Das Glossar kommt automatisch dazu (Module::uebersetzen) -- "Treffen Kreis 2" -> "Círculo 2".
            UebersetzungsSpeicher::markdown('calt', (int)$termin->id, trim((string)$termin->title), $ziel,
                'This is a short event title: output only the translated title.');
        }
        KreisUebersetzung::cacheLeeren();
    }

    public static function sicherAktualisieren($termin): void
    {
        try {
            self::aktualisiere($termin);
        } catch (Throwable $e) {
            // Eine fehlgeschlagene Uebersetzung darf das Speichern eines Termins nie verhindern.
            Yii::error('Termin-Uebersetzung fehlgeschlagen: ' . $e->getMessage(), 'nexus-translate');
        }
    }

    /** Austauschliste Titel -> Uebersetzung fuer eine Sprache (roh und HTML-kodiert). */
    public static function tabelle(string $code): array
    {
        if (!class_exists(self::KLASSE)) {
            return [];
        }
        $tabelle = [];
        $uebersetzt = TranslationCache::find()->where(['content_type' => 'calt_md', 'language' => $code])->indexBy('content_id')->all();
        if ($uebersetzt === []) {
            return [];
        }
        foreach ((self::KLASSE)::find()->where(['id' => array_keys($uebersetzt)])->all() as $termin) {
            $original = trim((string)$termin->title);
            $eintrag = $uebersetzt[$termin->id];
            // Nur gueltige Uebersetzungen: wurde der Titel geaendert, lieber das Original zeigen.
            if (mb_strlen($original) < self::MIN_TITEL || $eintrag->quelle_hash !== UebersetzungsSpeicher::pruefsumme($original)) {
                continue;
            }
            $tabelle[$original] = $eintrag->translated_text;
            $tabelle[Html::encode($original)] = Html::encode($eintrag->translated_text);
        }
        return $tabelle;
    }
}
