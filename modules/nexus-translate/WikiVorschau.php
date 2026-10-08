<?php

namespace humhub\modules\nexusTranslate;

use humhub\libs\Helpers;

/**
 * Wiki-Seiten im Stream (z. B. die angehefteten Anleitungen im Willkommen-Kreis) zeigen nur
 * die ersten 500 Zeichen. Uebersetzt wird genau dieser Ausschnitt -- gleiche Kuerzung wie
 * humhub\modules\wiki\widgets\WallEntry::renderContent(), damit Hinweis und Umschalter zum
 * sichtbaren Text passen und nicht die ganze (lange) Seite uebersetzt werden muss.
 * (Josh, 25.09.2026: "viele Beitraege wurden nicht uebersetzt" -- es waren Wiki-Seiten.)
 */
class WikiVorschau
{
    public const KLASSE = 'humhub\modules\wiki\models\WikiPage';

    public static function istWiki($record): bool
    {
        return is_object($record) && is_a($record, self::KLASSE);
    }

    public static function text($seite): string
    {
        $inhalt = (string)($seite->latestRevision->content ?? '');
        if ($inhalt === '') {
            return '';
        }
        $inhalt = Helpers::truncateText($inhalt, 500);
        return preg_replace('/!?\[.+?\]\([^\)]*?\.\.\.$/', '...', $inhalt);
    }

    /**
     * Titel der Wiki-Seiten, die links in der Kreis-Navigation haengen ("im Menue anzeigen"),
     * fuer die Ersetzungsliste von KreisUebersetzung (Josh, 28.09.2026: Seite war uebersetzt,
     * der Menue-Eintrag nicht -- den baut HumHub selbst, an unserem Skript vorbei). Nur
     * Menue-Seiten, nicht alle Wiki-Titel: kurze allgemeine Titel wuerden sonst ueberall im
     * Portal ersetzt. Quelle ist die gespeicherte Titel-Uebersetzung ("wikit"), nur wenn sie
     * zum aktuellen Titel passt.
     */
    public static function menueTitelTabelle(string $code): array
    {
        if (!class_exists(self::KLASSE)) {
            return [];
        }
        $uebersetzt = models\TranslationCache::find()->where(['content_type' => 'wikit_md', 'language' => $code])->indexBy('content_id')->all();
        if ($uebersetzt === []) {
            return [];
        }
        $tabelle = [];
        foreach ((self::KLASSE)::find()->where(['id' => array_keys($uebersetzt), 'is_container_menu' => 1])->all() as $seite) {
            $original = trim((string)$seite->title);
            $eintrag = $uebersetzt[$seite->id];
            if (mb_strlen($original) < 8 || $eintrag->quelle_hash !== UebersetzungsSpeicher::pruefsumme($original)) {
                continue;
            }
            $tabelle[$original] = $eintrag->translated_text;
            $tabelle[\humhub\helpers\Html::encode($original)] = \humhub\helpers\Html::encode($eintrag->translated_text);
        }
        return $tabelle;
    }
}
