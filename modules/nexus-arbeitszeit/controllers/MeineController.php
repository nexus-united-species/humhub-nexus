<?php

namespace nexus\modules\arbeitszeit\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use nexus\modules\arbeitszeit\models\Eintrag;
use nexus\modules\arbeitszeit\services\NextcloudBericht;
use nexus\modules\arbeitszeit\services\Texte;
use nexus\modules\arbeitszeit\services\ZeitService;
use Yii;
use yii\web\NotFoundHttpException;

/** Seite "Meine Arbeitsstunden": Formular + eigene Eintraege + Summen. Nur fuer Angemeldete. */
class MeineController extends Controller
{
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function actionIndex()
    {
        $userId = (int)Yii::$app->user->id;
        return $this->render('index', [
            'kreise' => ZeitService::kreiseFuer($userId),
            'summen' => ZeitService::summen($userId),
            'eintraege' => Eintrag::find()->where(['user_id' => $userId])->andWhere(['!=', 'status', Eintrag::STATUS_ENTWURF])
                ->orderBy(['datum' => SORT_DESC, 'id' => SORT_DESC])->limit(100)->all(),
            'fehler' => Yii::$app->session->getFlash('nexus-az-fehler', []),
            'alt' => Yii::$app->session->getFlash('nexus-az-alt', []),
        ]);
    }

    public function actionSpeichern()
    {
        $this->forcePostRequest();
        $r = Yii::$app->request;
        $spaceId = (int)$r->post('space_id');
        // Im Formular ist der Kreis Pflicht -- ohne ihn entstuende sonst ein Entwurf.
        [$eintrag, $fehler] = $spaceId > 0
            ? ZeitService::anlegen(
                Yii::$app->user->getIdentity(),
                (string)$r->post('stunden'),
                (string)$r->post('datum'),
                $spaceId,
                (string)$r->post('beschreibung'),
                'formular'
            )
            : [null, ['fehler_kreis']];
        if ($eintrag === null) {
            Yii::$app->session->setFlash('nexus-az-fehler', $fehler);
            Yii::$app->session->setFlash('nexus-az-alt', $r->post());
        } else {
            Yii::$app->session->setFlash('nexus-az-ok', Texte::t('gespeichert'));
        }
        return $this->redirect(['index']);
    }

    public function actionLoeschen(int $id)
    {
        $this->forcePostRequest();
        $eintrag = Eintrag::findOne(['id' => $id, 'user_id' => Yii::$app->user->id]);
        if ($eintrag === null) {
            throw new NotFoundHttpException();
        }
        // Nur was noch nicht geprueft ist -- Freigegebenes bleibt als Nachweis stehen.
        if ($eintrag->status === Eintrag::STATUS_OFFEN) {
            $eintrag->delete();
            NextcloudBericht::vormerken();
            Yii::$app->session->setFlash('nexus-az-ok', Texte::t('geloescht'));
        }
        return $this->redirect(['index']);
    }
}
