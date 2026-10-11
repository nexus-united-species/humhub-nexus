<?php

namespace nexus\modules\projekte\widgets;

use humhub\components\Widget;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\assets\ProjekteAsset;
use nexus\modules\projekte\services\MeineAufgaben;
use Yii;

/**
 * Kasten "Meine Aufgaben" -- auf der Startseite (alle Kreise) und rechts in der Seitenleiste eines
 * Kreises (nur dieser Kreis). Erscheint nur, wenn etwas offen ist.
 */
class MeineAufgabenKasten extends Widget
{
    private const ANZAHL = 5;

    /** @var Space|null nur Aufgaben aus diesem Kreis zeigen */
    public $kreis = null;

    public function run()
    {
        $ich = Yii::$app->user->getIdentity();
        if (!$ich instanceof User) {
            return '';
        }
        $eintraege = MeineAufgaben::fuer($ich);
        if ($this->kreis instanceof Space) {
            $kreisId = (int)$this->kreis->id;
            $eintraege = array_values(array_filter($eintraege, fn($e) => (int)$e['karte']->projekt->space_id === $kreisId));
        }
        if ($eintraege === []) {
            return '';
        }
        ProjekteAsset::register($this->view);
        return $this->render('meineAufgabenKasten', [
            'eintraege' => array_slice($eintraege, 0, self::ANZAHL),
            'mehr' => max(0, count($eintraege) - self::ANZAHL),
            'imKreis' => $this->kreis instanceof Space,
        ]);
    }
}
