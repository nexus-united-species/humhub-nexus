<?php

namespace nexus\modules\teilen\widgets;

use humhub\components\Widget;
use humhub\helpers\Html;
use nexus\modules\teilen\models\Freigabe;
use nexus\modules\teilen\services\Texte;

/**
 * "Teilen" in der sichtbaren Zeile unter einem Beitrag/einer Wiki-Seite. Der Text zum Teilen
 * wird erst beim Klick geholt (TeilenController::actionInfo) -- beim Anzeigen des Streams
 * entsteht so keine einzige Uebersetzung. Ein Globus zeigt: dieser Inhalt ist oeffentlich lesbar.
 */
class TeilenLink extends Widget
{
    public $record;

    public function run()
    {
        $contentId = (int)$this->record->content->id;
        $link = Html::a(
            '<i class="fa fa-share-alt" aria-hidden="true"></i> ' . Html::encode(Texte::get('teilen')),
            '#',
            ['data-nexus-teilen' => $contentId, 'rel' => 'nofollow']
        );
        if (Freigabe::fuer($contentId) === null) {
            return $link;
        }
        $hinweis = Texte::get('oeffentlich_markierung');
        return $link . ' <i class="fa fa-globe nexus-teilen-globus" data-nexus-globus="' . $contentId
            . '" title="' . Html::encode($hinweis) . '" aria-label="' . Html::encode($hinweis) . '"></i>';
    }
}
