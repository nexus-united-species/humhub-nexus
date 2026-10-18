<?php

namespace nexus\modules\meeting\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\user\models\User;
use nexus\modules\meeting\Module;
use Yii;

/**
 * Einstellungen (Verwaltung -> Module -> Konfigurieren), nur Systemadmins:
 * - kleines Meeting-Fenster an/aus
 * - wer eine "offene Tuer" oeffnen darf (Benutzernamen, mit Komma getrennt)
 */
class ConfigController extends Controller
{
    public function actionIndex()
    {
        /** @var Module $modul */
        $modul = $this->module;
        $unbekannt = [];
        if (Yii::$app->request->isPost) {
            $modul->settings->set('fenster', Yii::$app->request->post('fenster') === '1' ? '1' : '0');
            $ids = [];
            foreach (preg_split('/[\s,;]+/', (string)Yii::$app->request->post('tuer_personen'), -1, PREG_SPLIT_NO_EMPTY) as $name) {
                $mensch = User::findOne(['username' => $name, 'status' => User::STATUS_ENABLED]);
                if ($mensch === null) {
                    $unbekannt[] = $name;
                } else {
                    $ids[] = (int)$mensch->id;
                }
            }
            if ($unbekannt === []) {
                $modul->setzeTuerPersonen($ids);
                $this->view->saved();
                return $this->redirect(['index']);
            }
        }
        $namen = array_map(fn(User $u) => $u->username, User::findAll(['id' => $modul->tuerPersonen()]));
        return $this->render('index', [
            'fensterAn' => $modul->fensterAn(),
            'tuerPersonen' => $unbekannt !== [] ? (string)Yii::$app->request->post('tuer_personen') : implode(', ', $namen),
            'unbekannt' => $unbekannt,
        ]);
    }
}
