<?php

namespace nexus\modules\teilen\widgets;

use humhub\helpers\Html;
use humhub\modules\content\widgets\WallEntryControlLink;
use nexus\modules\teilen\models\Freigabe;
use nexus\modules\teilen\services\Texte;

/**
 * "Oeffentlich lesbar machen" / "Oeffentliche Freigabe aufheben" im "..."-Menue -- nur fuer
 * Verfasser und Admins (geprueft in Events::onControlsInit, und noch einmal beim Speichern).
 * Die Rueckfrage steht im Link selbst, damit das Skript keine eigenen Texte braucht.
 */
class FreigabeLink extends WallEntryControlLink
{
    public $record;

    public function run()
    {
        $contentId = (int)$this->record->content->id;
        $an = Freigabe::fuer($contentId) !== null;
        $label = '<i class="fa fa-globe" aria-hidden="true"></i> '
            . Html::encode(Texte::get($an ? 'menue_aus' : 'menue_an'));
        return '<li>' . Html::a($label, '#', [
            'class' => 'dropdown-item',
            'data-nexus-freigabe' => $contentId,
            'data-an' => $an ? '1' : '0',
            'data-frage-an' => Texte::get('frage_an'),
            'data-frage-aus' => Texte::get('frage_aus'),
            'data-label-an' => Texte::get('menue_an'),
            'data-label-aus' => Texte::get('menue_aus'),
        ]) . '</li>';
    }
}
