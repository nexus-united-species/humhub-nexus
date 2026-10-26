<?php

namespace nexus\modules\finanzen\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use nexus\modules\finanzen\assets\FinanzenAsset;
use nexus\modules\finanzen\Module;
use nexus\modules\finanzen\services\Geld;
use nexus\modules\finanzen\services\Stand;
use nexus\modules\finanzen\services\Texte;
use Yii;

/** Die Ampel fuer alle angemeldeten Mitglieder -- nur Summen, nie Namen von Unterstuetzern. */
class AmpelController extends Controller
{
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function actionIndex(?string $monat = null)
    {
        if (!Module::instanz()->darfSehen(Yii::$app->user->getIdentity())) {
            throw new \yii\web\NotFoundHttpException();
        }
        FinanzenAsset::register($this->view);
        $this->view->title = Texte::t('titel');
        $monat = Geld::monat($monat);
        $rueckblick = Yii::$app->db->createCommand('SELECT text FROM nexus_fin_rueckblick WHERE monat = :m', [':m' => $monat])->queryScalar();
        return $this->render('index', [
            'stand' => new Stand(),
            'monat' => $monat,
            'rueckblick' => $rueckblick ?: null,
            'darfVerwalten' => Module::instanz()->istVerantwortlich(Yii::$app->user->getIdentity()),
            'deckel' => Module::instanz()->entwicklungsDeckel(),
            'mithelfenUrl' => Module::instanz()->mithelfenUrl(),
            'fuerAlle' => Module::instanz()->fuerAlle(),
        ]);
    }
}
