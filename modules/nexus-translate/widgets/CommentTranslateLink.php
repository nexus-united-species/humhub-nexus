<?php

namespace humhub\modules\nexusTranslate\widgets;

use humhub\components\Widget;
use humhub\helpers\Html;
use humhub\modules\comment\models\Comment;
use humhub\modules\nexusTranslate\assets\Assets;
use humhub\modules\nexusTranslate\SprachErkennung;

/**
 * "Uebersetzen" fuer einzelne Kommentare.
 *
 * Kommentare haengen anders im System als Beitraege (ContentAddonActiveRecord
 * statt ContentActiveRecord) und haben deshalb eine eigene Anschlussstelle
 * (CommentEntryLinks statt WallEntryControls) und eine eigene, einfachere
 * Basis-Widgetklasse -- siehe TranslateLink.php fuer die Beitrags-Fassung.
 */
class CommentTranslateLink extends Widget
{
    public $object;

    public function run()
    {
        if (!($this->object instanceof Comment) || trim((string)$this->object->message) === '') {
            return '';
        }

        Assets::register($this->getView());

        return Html::a(
            '<i class="fa fa-language" aria-hidden="true"></i> Translate',
            '#',
            ['data-nexus-comment-id' => $this->object->id] + self::autoAttribute((string)$this->object->message)
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
