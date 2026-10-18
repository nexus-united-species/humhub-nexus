<?php

namespace nexus\modules\meeting\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\user\models\User;
use nexus\modules\meeting\models\Tuer;
use nexus\modules\meeting\Module;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Offene Tuer oeffnen, schliessen und am Leben halten (vom Skript nexus.meeting.js).
 * Oeffnen duerfen nur die Menschen, die in den Einstellungen des Moduls stehen.
 */
class TuerController extends Controller
{
    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['oeffnen', 'schliessen', 'lebenszeichen']],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return true;
    }

    public function actionOeffnen()
    {
        $ich = $this->ich();
        if (!$this->module->darfTuerOeffnen($ich)) {
            throw new ForbiddenHttpException();
        }
        $jetzt = date('Y-m-d H:i:s');
        $tuer = Tuer::findOne(['user_id' => $ich->id]) ?? new Tuer(['user_id' => $ich->id]);
        $tuer->raum = Tuer::neuerRaum($ich);
        $tuer->warteraum = Yii::$app->request->post('warteraum') === '1' ? 1 : 0;
        $tuer->geoeffnet_am = $jetzt;
        $tuer->lebenszeichen_am = $jetzt;
        $tuer->save(false);
        return ['offen' => true, 'raum' => $tuer->raum, 'warteraum' => (bool)$tuer->warteraum];
    }

    public function actionSchliessen()
    {
        Tuer::deleteAll(['user_id' => $this->ich()->id]);
        return ['offen' => false];
    }

    /** Antwortet mit offen=false, wenn die Tuer inzwischen zu ist (Hoechstdauer) -- dann zeigt das Skript es an. */
    public function actionLebenszeichen()
    {
        $tuer = Tuer::findOne(['user_id' => $this->ich()->id]);
        if ($tuer === null || $tuer->istAbgelaufen()) {
            $tuer?->delete();
            return ['offen' => false];
        }
        $tuer->lebenszeichen_am = date('Y-m-d H:i:s');
        $tuer->save(false, ['lebenszeichen_am']);
        return ['offen' => true];
    }

    private function ich(): User
    {
        $ich = Yii::$app->user->getIdentity();
        if (!$ich instanceof User) {
            throw new ForbiddenHttpException();
        }
        return $ich;
    }
}
