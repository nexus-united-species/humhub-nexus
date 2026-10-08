<?php

namespace humhub\modules\nexusTranslate\widgets;

use humhub\components\Widget;
use humhub\helpers\Html;
use humhub\modules\nexusTranslate\assets\Assets;
use humhub\modules\nexusTranslate\SprachErkennung;
use humhub\modules\nexusTranslate\TerminUebersetzung;
use humhub\modules\nexusTranslate\WikiVorschau;
use humhub\modules\post\models\Post;

/**
 * "Uebersetzen" fuer Beitraege -- als direkt sichtbarer Link unter dem
 * Beitrag (wie bei Kommentaren), NICHT im "..."-Menue.
 *
 * War urspruenglich als WallEntryControlLink gebaut (siehe Events.php-
 * Historie) und landete dadurch im versteckten "..."-Dropdown eines
 * Beitrags -- demselben Menue wie Bearbeiten/Loeschen/Melden. Lesende
 * Mitglieder haben dort nicht danach gesucht (anders als bei Kommentaren,
 * wo der Link schon immer sichtbar direkt daneben stand), siehe Josh'
 * Rueckmeldung vom 04.09.2026. Jetzt an WallEntryLinks angeschlossen, der
 * sichtbaren Link-Zeile unter jedem Beitrag (wie "Gefaellt mir" /
 * "Kommentieren") -- exakt dieselbe, einfache Bauweise wie
 * CommentTranslateLink.
 */
class TranslateLink extends Widget
{
    public $record;

    public function run()
    {
        // Wiki-Seiten (seit 25.09.2026): nur automatisch, und nur wenn die Vorschau in einer
        // anderen Sprache ist -- ein eigener "Translate"-Knopf ueber dem Ausschnitt waere verwirrend,
        // die ganze Seite laesst sich per Klick auf "Weiterlesen" im Wiki lesen.
        // Kalendertermin (seit 26.09.2026): Beschreibung automatisch; der Titel wird schon
        // serverseitig ausgetauscht (TerminUebersetzung). Sprache an Titel + Beschreibung erkannt.
        if (TerminUebersetzung::istTermin($this->record)) {
            $beschreibung = trim((string)$this->record->description);
            $auto = $beschreibung === '' ? [] : self::autoAttribute($this->record->title . "\n" . $beschreibung);
            if ($auto === []) {
                return '';
            }
            Assets::register($this->getView());
            return Html::a(
                '<i class="fa fa-language" aria-hidden="true"></i> Translate',
                '#',
                ['data-nexus-cal-id' => $this->record->id] + $auto
            );
        }

        if (WikiVorschau::istWiki($this->record)) {
            $auto = self::autoAttribute(WikiVorschau::text($this->record));
            if ($auto === []) {
                return '';
            }
            Assets::register($this->getView());
            return Html::a(
                '<i class="fa fa-language" aria-hidden="true"></i> Translate',
                '#',
                ['data-nexus-wiki-id' => $this->record->id] + $auto
            );
        }

        if (!($this->record instanceof Post) || trim((string)$this->record->message) === '') {
            return '';
        }

        Assets::register($this->getView());

        return Html::a(
            '<i class="fa fa-language" aria-hidden="true"></i> Translate',
            '#',
            ['data-nexus-post-id' => $this->record->id] + self::autoAttribute((string)$this->record->message)
        );
    }

    /**
     * Ist der Text in einer anderen Sprache als der des Lesers, markiert der Link den
     * Beitrag fuer die automatische Uebersetzung (nexus.translate.js holt sie dann).
     */
    public static function autoAttribute(string $text): array
    {
        $von = SprachErkennung::autoQuelle($text);
        if ($von === null) {
            return [];
        }
        $b = SprachErkennung::beschriftung($von);
        return [
            'data-nexus-auto' => $von,
            'data-nexus-hinweis' => $b['hinweis'],
            'data-nexus-original' => $b['original'],
            'data-nexus-uebersetzung' => $b['uebersetzung'],
        ];
    }
}
