<?php

namespace nexus\modules\reaktionen\controllers;

use humhub\components\behaviors\AccessControl;
use humhub\components\Controller;
use nexus\modules\reaktionen\services\Nachrichten;
use nexus\modules\reaktionen\services\Reaktionen;
use Yii;
use yii\web\ForbiddenHttpException;

/** Reaktionen und Antwort-Bezuege in privaten Unterhaltungen -- nur fuer deren Teilnehmer. */
class NachrichtController extends Controller
{
    public function behaviors()
    {
        return [
            'acl' => ['class' => AccessControl::class],
        ];
    }

    public function actionListe()
    {
        $this->forcePostRequest();
        $max = max(1, (int)Reaktionen::modul()->maxObjekte) * 2;
        $ids = array_slice((array)Yii::$app->request->post('eintraege', []), 0, $max);

        Yii::$app->response->format = 'json';
        return ['ok' => true, 'objekte' => (object)Nachrichten::liste($ids, (int)Yii::$app->user->id)];
    }

    public function actionSetzen()
    {
        $this->forcePostRequest();
        $erg = Nachrichten::reagieren(
            (int)Yii::$app->user->id,
            (int)Yii::$app->request->post('eintrag', 0),
            (string)Yii::$app->request->post('emoji', '')
        );
        if ($erg === null) {
            throw new ForbiddenHttpException();
        }

        Yii::$app->response->format = 'json';
        return ['ok' => true] + $erg;
    }
}
