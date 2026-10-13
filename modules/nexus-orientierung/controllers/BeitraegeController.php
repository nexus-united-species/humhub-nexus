<?php

namespace nexus\modules\orientierung\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\user\models\User;
use nexus\modules\orientierung\Module;
use nexus\modules\orientierung\services\Beitraege;
use nexus\modules\orientierung\services\Texte;
use Yii;
use yii\web\NotFoundHttpException;

/** "Beitraege & Kommentare" im Profil eines Menschen */
class BeitraegeController extends ContentContainerController
{
    private const MIN_PRO_SEITE = 10;

    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function actionIndex(int $seite = 1)
    {
        $person = $this->contentContainer;
        if (!$person instanceof User) {
            throw new NotFoundHttpException();
        }
        $ich = Yii::$app->user->getIdentity();
        $seite = max(1, $seite);
        $titel = (int)$person->id === (int)$ich->id ? Texte::t('titel_ich') : Texte::t('titel', ['{name}' => $person->displayName]);
        $this->view->title = $titel;
        return $this->render('index', [
            'person' => $person,
            'titel' => $titel,
            'seite' => $seite,
            'daten' => Beitraege::liste($person, $ich, $seite, max(self::MIN_PRO_SEITE, (int)$this->module->proSeite)),
        ]);
    }
}
