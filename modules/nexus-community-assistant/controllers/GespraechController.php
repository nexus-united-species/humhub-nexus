<?php

namespace nexus\modules\communityAssistant\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\user\models\User;
use nexus\modules\communityAssistant\models\Gespraech;
use nexus\modules\communityAssistant\models\Merkpunkt;
use nexus\modules\communityAssistant\services\GespraechAntwort;
use nexus\modules\communityAssistant\services\Gespraeche;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Seite "Nova – Gespraeche": eigene Gespraeche mit Nova, nur fuer den Menschen selbst sichtbar.
 * Jede Aktion prueft, dass das Gespraech dem angemeldeten Menschen gehoert.
 */
class GespraechController extends Controller
{
    public const FLASH = 'nexus-nova';

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['neu', 'senden', 'loeschen', 'vergessen']],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!Gespraeche::darfNutzen($this->ich())) {
            throw new ForbiddenHttpException(Gespraeche::t('kein_zugang'));
        }
        return true;
    }

    public function actionIndex(int $g = 0)
    {
        $ich = $this->ich();
        $gespraeche = Gespraech::find()->where(['user_id' => $ich->id])->orderBy(['updated_at' => SORT_DESC])->all();
        $aktiv = $g > 0 ? $this->gespraech($g) : null;
        return $this->render('index', [
            'gespraeche' => $gespraeche,
            'aktiv' => $aktiv,
            'merk' => Merkpunkt::find()->where(['user_id' => $ich->id])->orderBy('id')->all(),
            'rest' => max(0, Gespraeche::tageslimit() - Gespraeche::heuteGenutzt($ich)),
            'sperre' => Gespraeche::sperre($ich),
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
            // Vorlage fuer das Eingabefeld, z. B. der Verlauf aus dem Team-Postfach (nexus-projekte)
            'vorlage' => $aktiv !== null ? (string)Yii::$app->session->getFlash('nx-nova-vorlage', '') : '',
        ]);
    }

    public function actionNeu()
    {
        $art = (string)Yii::$app->request->post('art');
        if (!isset(Gespraeche::ARTEN[$art])) {
            throw new NotFoundHttpException();
        }
        $jetzt = date('Y-m-d H:i:s');
        $gespraech = new Gespraech(['user_id' => $this->ich()->id, 'titel' => '', 'art' => $art, 'created_at' => $jetzt, 'updated_at' => $jetzt]);
        $gespraech->save(false);
        return $this->redirect(['index', 'g' => $gespraech->id]);
    }

    public function actionSenden(int $g)
    {
        $gespraech = $this->gespraech($g);
        $fehler = GespraechAntwort::senden($gespraech, (string)Yii::$app->request->post('text'), $this->ich());
        if ($fehler !== null) {
            Yii::$app->session->setFlash(self::FLASH, $fehler);
        }
        return $this->redirect(['index', 'g' => $gespraech->id, '#' => 'nx-nova-ende']);
    }

    public function actionLoeschen(int $g)
    {
        $this->gespraech($g)->delete();
        return $this->redirect(['index']);
    }

    public function actionVergessen(int $m, int $g = 0)
    {
        $punkt = Merkpunkt::findOne(['id' => $m, 'user_id' => $this->ich()->id]);
        if ($punkt === null) {
            throw new NotFoundHttpException();
        }
        $punkt->delete();
        return $this->redirect(['index'] + ($g > 0 ? ['g' => $g] : []));
    }

    private function ich(): User
    {
        return Yii::$app->user->getIdentity();
    }

    private function gespraech(int $id): Gespraech
    {
        $gespraech = Gespraech::findOne(['id' => $id, 'user_id' => $this->ich()->id]);
        if ($gespraech === null) {
            throw new NotFoundHttpException();
        }
        return $gespraech;
    }
}
