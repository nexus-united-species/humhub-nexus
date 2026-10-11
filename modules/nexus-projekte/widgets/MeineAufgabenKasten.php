<?php

namespace nexus\modules\projekte\widgets;

use humhub\components\Widget;
use humhub\modules\user\models\User;
use nexus\modules\projekte\assets\ProjekteAsset;
use nexus\modules\projekte\services\MeineAufgaben;
use Yii;

/** Kasten "Meine Aufgaben" auf der Startseite -- erscheint nur, wenn etwas offen ist. */
class MeineAufgabenKasten extends Widget
{
    private const ANZAHL = 5;

    public function run()
    {
        $ich = Yii::$app->user->getIdentity();
        if (!$ich instanceof User) {
            return '';
        }
        $eintraege = MeineAufgaben::fuer($ich);
        if ($eintraege === []) {
            return '';
        }
        ProjekteAsset::register($this->view);
        return $this->render('meineAufgabenKasten', [
            'eintraege' => array_slice($eintraege, 0, self::ANZAHL),
            'mehr' => max(0, count($eintraege) - self::ANZAHL),
        ]);
    }
}
