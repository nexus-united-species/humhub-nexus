<?php

namespace nexus\modules\supporterBridge\controllers;

use nexus\modules\supporterBridge\models\forms\ManualSupporterForm;
use nexus\modules\supporterBridge\models\Supporter;
use nexus\modules\supporterBridge\models\SupporterEvent;
use nexus\modules\supporterBridge\services\KofiWebhookService;
use Yii;
use humhub\components\access\ControllerAccess;
use humhub\modules\admin\components\Controller;
use yii\web\NotFoundHttpException;

/**
 * Sehr einfache Admin-Oberflaeche -- eine Liste, ein paar Formulare.
 * Bewusst ohne eigenes Menu-Widget oder AJAX, um die Oberflaeche klein
 * zu halten (Vorgabe: "keine unnoetig komplexe Oberflaeche").
 */
class AdminController extends Controller
{
    /**
     * Admin-Grundklasse von HumHub (Verwaltungsbereich, Admin-Menue). Deren Standardregel waere die
     * Berechtigung \"Einstellungen verwalten\" -- hier bewusst enger: nur Systemadministratoren.
     */
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_ADMIN_ONLY]];
    }

    public function actionIndex()
    {
        $supporters = Supporter::find()->orderBy(['updated_at' => SORT_DESC])->all();
        $events = SupporterEvent::find()->orderBy(['created_at' => SORT_DESC])->limit(30)->all();

        return $this->render('index', [
            'supporters' => $supporters,
            'events' => $events,
            'spaceId' => (int)getenv('SUPPORTER_SPACE_ID'),
        ]);
    }

    public function actionGrant(int $id)
    {
        $this->requirePost();
        $supporter = $this->findSupporter($id);

        $supporter->status = Supporter::STATUS_ACTIVE;
        $supporter->save(false);
        $this->service()->zugangGewaehren($supporter->humhub_user_id);

        Yii::$app->session->setFlash('success', 'Zugang freigeschaltet.');
        return $this->redirect(['index']);
    }

    public function actionGrantLifetime(int $id)
    {
        $this->requirePost();
        $supporter = $this->findSupporter($id);

        $supporter->status = Supporter::STATUS_LIFETIME;
        $supporter->expires_at = null;
        $supporter->save(false);
        $this->service()->zugangGewaehren($supporter->humhub_user_id);
        SupporterEvent::protokollieren(SupporterEvent::MANUAL_ACCESS_GRANTED, $supporter->humhub_user_id, 'lifetime');

        Yii::$app->session->setFlash('success', 'Dauerhafter Zugang vergeben.');
        return $this->redirect(['index']);
    }

    public function actionSetExpiry(int $id)
    {
        $this->requirePost();
        $supporter = $this->findSupporter($id);

        $datum = Yii::$app->request->post('expires_at');
        $supporter->expires_at = $datum ? date('Y-m-d H:i:s', strtotime($datum)) : null;

        if ($supporter->humhub_user_id && !in_array($supporter->status, [Supporter::STATUS_MANUAL, Supporter::STATUS_LIFETIME], true)) {
            $supporter->status = Supporter::STATUS_ACTIVE;
            $supporter->save(false);
            $this->service()->zugangGewaehren($supporter->humhub_user_id);
        } else {
            $supporter->save(false);
        }

        Yii::$app->session->setFlash('success', 'Ablaufdatum gesetzt.');
        return $this->redirect(['index']);
    }

    public function actionRevoke(int $id)
    {
        $this->requirePost();
        $supporter = $this->findSupporter($id);
        $grund = trim((string)Yii::$app->request->post('note', ''));

        $supporter->status = Supporter::STATUS_REVOKED;
        if ($grund !== '') {
            $supporter->note = $grund;
        }
        $supporter->save(false);

        if ($supporter->humhub_user_id) {
            $this->service()->zugangEntziehen($supporter->humhub_user_id, $grund ?: 'manuell durch Admin');
        }

        Yii::$app->session->setFlash('success', 'Zugang entzogen.');
        return $this->redirect(['index']);
    }

    public function actionManualCreate()
    {
        $model = new ManualSupporterForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $user = $model->getUser();
            $note = trim((string)$model->note);
            $email = strtolower(trim((string)$user->email));

            $supporter = Supporter::findOne(['humhub_user_id' => $user->id])
                ?? Supporter::findOne(['ko_fi_email' => $email])
                ?? new Supporter([
                    'ko_fi_email' => $email,
                    'support_type' => Supporter::SUPPORT_TYPE_ONE_TIME,
                ]);
            $supporter->humhub_user_id = $user->id;
            $supporter->ko_fi_email = $email;
            $supporter->status = $model->status;
            $supporter->note = $note !== '' ? $note : $supporter->note;
            $supporter->started_at ??= date('Y-m-d H:i:s');
            $supporter->expires_at = ($model->status === Supporter::STATUS_ACTIVE && $model->expires_at)
                ? date('Y-m-d H:i:s', strtotime($model->expires_at))
                : null;
            $supporter->save(false);

            SupporterEvent::protokollieren(SupporterEvent::MANUAL_ACCESS_GRANTED, $user->id, $note ?: 'manuell angelegt');
            $this->service()->zugangGewaehren($user->id);

            Yii::$app->session->setFlash('success', 'Manueller Unterstuetzer angelegt und freigeschaltet.');
            return $this->redirect(['index']);
        }

        return $this->render('manual-create', ['model' => $model]);
    }

    private function findSupporter(int $id): Supporter
    {
        $supporter = Supporter::findOne($id);
        if ($supporter === null) {
            throw new NotFoundHttpException('Unterstuetzer nicht gefunden.');
        }

        return $supporter;
    }

    private function service(): KofiWebhookService
    {
        // Mindestbetrag steht fest im Service (jede monatliche Unterstuetzung, Josh 28.09.2026).
        return new KofiWebhookService((int)getenv('SUPPORTER_SPACE_ID'));
    }

    private function requirePost(): void
    {
        if (!Yii::$app->request->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException();
        }
    }
}
