<?php

namespace nexus\modules\teilen\widgets;

use humhub\components\Widget;
use humhub\helpers\Html;
use nexus\modules\teilen\models\Freigabe;
use nexus\modules\teilen\services\Texte;

/**
 * "🌐 Ins Schaufenster" -- sichtbar unter jedem Beitrag/jeder Wiki-Seite, aber NUR fuer Portal-Admins
 * (geprueft in Events, beim Speichern noch einmal). Ein Klick zeigt die Vorschau, wie der Inhalt
 * oeffentlich erscheint; steht er schon drin, nimmt ein Klick ihn wieder heraus.
 */
class SchaufensterLink extends Widget
{
    public $record;

    public function run()
    {
        $contentId = (int)$this->record->content->id;
        $freigabe = Freigabe::fuer($contentId);
        $drin = $freigabe !== null && $freigabe->imSchaufenster();
        return Html::a(
            '<i class="fa fa-globe" aria-hidden="true"></i> ' . Html::encode(Texte::get($drin ? 'sf_knopf_drin' : 'sf_knopf_an')),
            '#',
            [
                'class' => 'nexus-teilen-schaufenster' . ($drin ? ' drin' : ''),
                'data-nexus-schaufenster' => $contentId,
                'data-an' => $drin ? '1' : '0',
                'data-label-an' => Texte::get('sf_knopf_an'),
                'data-label-drin' => Texte::get('sf_knopf_drin'),
                'data-frage-raus' => Texte::get('sf_raus_frage'),
                'title' => Texte::get('sf_knopf_titel'),
                'rel' => 'nofollow',
            ]
        );
    }
}
