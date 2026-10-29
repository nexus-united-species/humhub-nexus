<?php

namespace nexus\modules\team\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use nexus\modules\team\assets\TeamAsset;
use nexus\modules\team\Module;
use nexus\modules\team\services\Daten;
use nexus\modules\team\services\Texte;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/** Die Uebersichtsseite fuer das Community-Team (Mitglieder der Team-Gruppe und Systemadmins). */
class UeberblickController extends Controller
{
    /** So weit zurueck zaehlen "neue Mitglieder" */
    public const NEUE_TAGE = 21;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['begruesst', 'zuruecknehmen']],
        ];
    }

    private function nurTeam(): void
    {
        if (!Module::instanz()->istTeam(Yii::$app->user->getIdentity())) {
            throw new NotFoundHttpException();
        }
    }

    public function actionIndex()
    {
        $this->nurTeam();
        TeamAsset::register($this->view);
        $this->view->title = Texte::t('titel');
        $ich = Yii::$app->user->getIdentity();
        $istAdmin = (bool)$ich->isSystemAdmin();
        $neue = Daten::neueMitglieder(self::NEUE_TAGE);
        $meldungen = Daten::meldungen($istAdmin);
        $fragen = Daten::beitraegeOhneAntwort(14, 2);
        $support = Daten::supportAnfragen();
        $ruhige = Daten::ruhigeKreise(30);
        $unbegruesst = count(array_filter($neue, fn($m) => $m['begruesst'] === null));

        return $this->render('index', [
            'ich' => $ich,
            'neue' => $neue,
            'neueTage' => self::NEUE_TAGE,
            'unbegruesst' => $unbegruesst,
            'meldungen' => $meldungen,
            'fragen' => $fragen,
            'support' => $support,
            'ruhige' => $ruhige,
        ]);
    }

    public function actionBegruesst()
    {
        $this->nurTeam();
        $id = (int)Yii::$app->request->post('id');
        if ($id <= 0) {
            throw new NotFoundHttpException();
        }
        Daten::markiereBegruesst($id, (int)Yii::$app->user->id);
        return $this->redirect(['index']);
    }

    public function actionZuruecknehmen()
    {
        $this->nurTeam();
        $id = (int)Yii::$app->request->post('id');
        if ($id <= 0) {
            throw new NotFoundHttpException();
        }
        Daten::nimmBegruessungZurueck($id);
        return $this->redirect(['index']);
    }
}
