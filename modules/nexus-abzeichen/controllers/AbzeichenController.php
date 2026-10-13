<?php

namespace nexus\modules\abzeichen\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use nexus\modules\abzeichen\Module;
use nexus\modules\abzeichen\services\Aktivzeit;
use nexus\modules\abzeichen\services\Bekenntnisse;
use nexus\modules\abzeichen\services\Teilen;
use nexus\modules\abzeichen\services\Texte;
use Yii;
use yii\web\NotFoundHttpException;

/** Abzeichen ansehen, sich bekennen, wieder loesen -- und die eigenen Momente. */
class AbzeichenController extends Controller
{
    private const FLASH = 'nexus-abzeichen';

    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['bekennen', 'teilen']],
        ];
    }

    public function actionIndex()
    {
        $ich = (int)Yii::$app->user->id;
        $this->view->title = Texte::t('abzeichen');
        return $this->render('index', [
            'abzeichen' => $this->module->alleAbzeichen(),
            'meine' => Bekenntnisse::von($ich),
            'momente' => $this->module->alleMomente(),
            'zaehler' => Aktivzeit::zaehler($ich),
            'geteilt' => Aktivzeit::geteilt($ich),
            'kreis' => Teilen::kreis(),
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
        ]);
    }

    public function actionAnsehen(string $a)
    {
        $abz = $this->abzeichen($a);
        $this->view->title = $abz->name();
        return $this->render('ansehen', [
            'abz' => $abz,
            'zeige' => Bekenntnisse::hat((int)Yii::$app->user->id, $abz->kennung),
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
        ]);
    }

    /** Eine Feier freiwillig im Kreis teilen -- aus der Feier (Antwort als JSON) oder von der Abzeichen-Seite */
    public function actionTeilen(string $m)
    {
        $moment = null;
        foreach ($this->module->alleMomente() as $kandidat) {
            if ($kandidat->kennung === $m) {
                $moment = $kandidat;
            }
        }
        if ($moment === null) {
            throw new NotFoundHttpException();
        }
        $url = Teilen::teilen(Yii::$app->user->getIdentity(), $moment, (string)Yii::$app->request->post('notiz', ''));
        if (Yii::$app->request->isAjax) {
            return $this->asJson(['ok' => $url !== null, 'url' => $url]);
        }
        Yii::$app->session->setFlash(self::FLASH, Texte::t($url !== null ? 'geteilt_ok' : 'teilen_fehler'));
        return $this->redirect(['index']);
    }

    public function actionBekennen(string $a)
    {
        $abz = $this->abzeichen($a);
        $an = Yii::$app->request->post('an') === '1';
        Bekenntnisse::setzen((int)Yii::$app->user->id, $abz->kennung, $an);
        Yii::$app->session->setFlash(self::FLASH, Texte::t($an ? 'bekannt' : 'geloest'));
        return $this->redirect(['ansehen', 'a' => $abz->kennung]);
    }

    private function abzeichen(string $kennung)
    {
        $abz = $this->module->abzeichen($kennung);
        if ($abz === null) {
            throw new NotFoundHttpException();
        }
        return $abz;
    }
}
