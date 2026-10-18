<?php

namespace nexus\modules\wissen\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\wissen\assets\WissenAsset;
use nexus\modules\wissen\models\SammlungEintrag;
use nexus\modules\wissen\Module;
use nexus\modules\wissen\services\Themen;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * "Wissensbereich anlegen" im Menue oben rechts eines Kreises: Besitzer und Admins des Kreises (und
 * Systemadmins) legen dort selbst eine Sammlung an. Sie beginnt leer und unsichtbar -- bis die
 * Redaktion Kategorien und Artikel angelegt und freigegeben hat.
 */
class EinrichtenController extends ContentContainerController
{
    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!$this->contentContainer instanceof Space) {
            throw new NotFoundHttpException();
        }
        if (!self::darfAnlegen(Yii::$app->user->getIdentity(), $this->contentContainer)) {
            throw new ForbiddenHttpException();
        }
        WissenAsset::register($this->view);
        return true;
    }

    public static function darfAnlegen($mensch, Space $kreis): bool
    {
        return $mensch instanceof User && ($mensch->isSystemAdmin() || $kreis->isAdmin((int)$mensch->id));
    }

    public function actionIndex()
    {
        /** @var Space $kreis */
        $kreis = $this->contentContainer;
        $eintrag = new SammlungEintrag(['kreis_id' => $kreis->id, 'symbol' => Themen::VORGABE_SYMBOL]);
        if (Yii::$app->request->isPost) {
            $r = Yii::$app->request;
            $symbol = (string)$r->post('symbol');
            $eintrag->setAttributes([
                'name' => trim((string)$r->post('name')),
                'beschreibung' => trim((string)$r->post('beschreibung')) ?: null,
                'hinweis' => trim((string)$r->post('hinweis')) ?: null,
                'symbol' => in_array($symbol, Themen::SYMBOLE, true) ? $symbol : Themen::VORGABE_SYMBOL,
            ], false);
            $eintrag->kennung = $this->module->freieKennung((string)$eintrag->name);
            $eintrag->created_at = date('Y-m-d H:i:s');
            $eintrag->created_by = (int)Yii::$app->user->id;
            if ($eintrag->save()) {
                $sammlung = $this->module->sammlung($eintrag->kennung);
                return $this->redirect($sammlung !== null ? $sammlung->url('redaktion/themen') : $kreis->getUrl());
            }
        }
        return $this->render('index', [
            'eintrag' => $eintrag,
            'aktion' => $kreis->createUrl('/nexus-wissen/einrichten/index'),
            'kreisName' => (string)$kreis->name,
            'zurueck' => $kreis->getUrl(),
            'loeschenUrl' => null,
        ]);
    }
}
