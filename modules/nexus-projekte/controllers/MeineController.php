<?php

namespace nexus\modules\projekte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\user\models\User;
use nexus\modules\projekte\assets\ProjekteAsset;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\Module;
use nexus\modules\projekte\services\MeineAufgaben;
use nexus\modules\projekte\services\Texte;
use Yii;

/** "Meine Aufgaben": alles, was mich betrifft, aus allen Kreisen. */
class MeineController extends Controller
{
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function actionIndex()
    {
        /** @var User $ich */
        $ich = Yii::$app->user->getIdentity();
        ProjekteAsset::register($this->view);
        $this->view->title = Texte::t('meine_aufgaben');
        $geleitet = array_filter(
            Projekt::find()->where(['leitung_id' => $ich->id, 'status' => 'aktiv'])->with('kreis')->orderBy('titel')->all(),
            fn(Projekt $p) => $p->kreis !== null && Module::istAn($p->kreis)
        );
        return $this->render('index', [
            'eintraege' => MeineAufgaben::fuer($ich),
            'geleitet' => array_values($geleitet),
        ]);
    }
}
