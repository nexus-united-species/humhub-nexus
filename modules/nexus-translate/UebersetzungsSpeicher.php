<?php

namespace humhub\modules\nexusTranslate;

use humhub\modules\nexusTranslate\models\TranslationCache;
use Yii;

/**
 * Gemeinsamer Speicher fuer automatische Uebersetzungen (Beitraege, Kommentare, Wiki-Vorschauen,
 * Kalender-Titel und -Beschreibungen). Je Text und Sprache wird nur EINMAL uebersetzt; die
 * Pruefsumme des Originals erkennt Aenderungen, dann entsteht die Uebersetzung neu.
 * Vorher privat im TranslateController -- ausgelagert, damit auch das Speichern eines
 * Kalendereintrags (Events::onCalendarEntrySaved) dieselbe Stelle nutzt.
 */
class UebersetzungsSpeicher
{
    /**
     * Pruefsumme aus Original UND Glossar-Version: aendert sich das Glossar, gelten die
     * gespeicherten Uebersetzungen als veraltet und entstehen neu (siehe Glossar::VERSION).
     */
    public static function pruefsumme(string $text): string
    {
        return md5('glossar' . Glossar::VERSION . "\n" . $text);
    }

    /** Uebersetzung MIT Markdown; $typ hoechstens 7 Zeichen (Spalte content_type ist 10 lang, "_md" kommt dazu). */
    public static function markdown(string $typ, int $id, string $text, string $ziel, string $zusatz = ''): string
    {
        $schluessel = ['content_type' => $typ . '_md', 'content_id' => $id, 'language' => $ziel];
        $hash = self::pruefsumme($text);
        $eintrag = TranslationCache::findOne($schluessel);
        if ($eintrag !== null && $eintrag->quelle_hash === $hash) {
            return $eintrag->translated_text;
        }
        $uebersetzt = Yii::$app->getModule('nexus-translate')->uebersetzen($text, $ziel, $zusatz, true);
        // Manche Modelle packen die Antwort trotz Anweisung in einen Codeblock.
        $uebersetzt = preg_replace('/^```[a-z]*\s*\n|\n```\s*$/i', '', trim($uebersetzt));
        $eintrag ??= new TranslationCache($schluessel);
        $eintrag->translated_text = $uebersetzt;
        $eintrag->quelle_hash = $hash;
        $eintrag->created_at = date('Y-m-d H:i:s');
        try {
            $eintrag->save(false);
        } catch (\yii\db\Exception $e) {
            // Gleichzeitige Anfrage hat schon gespeichert -- Ergebnis trotzdem ausliefern.
            Yii::info('Auto-Uebersetzung war schon gespeichert: ' . $e->getMessage(), 'nexus-translate');
        }
        return $uebersetzt;
    }
}
