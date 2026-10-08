<?php

namespace nexus\modules\meeting\controllers;

use humhub\modules\admin\components\Controller;
use nexus\modules\meeting\Module;
use Yii;

/** Schalter "kleines Meeting-Fenster an/aus" (Verwaltung -> Module -> Konfigurieren). Nur Systemadmins. */
class ConfigController extends Controller
{
    public function actionIndex()
    {
        /** @var Module $modul */
        $modul = $this->module;
        if (Yii::$app->request->isPost) {
            $modul->settings->set('fenster', Yii::$app->request->post('fenster') === '1' ? '1' : '0');
            $this->view->saved();
            return $this->redirect(['index']);
        }
        return $this->render('index', ['fensterAn' => $modul->fensterAn()]);
    }
}
