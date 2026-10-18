<?php

namespace nexus\modules\themen\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\space\models\Space;
use humhub\modules\topic\permissions\ManageTopics;
use humhub\modules\topic\widgets\TopicPicker;
use nexus\modules\themen\Module;
use Yii;
use yii\web\Response;

/**
 * Themen eines Kreises fuer das Skript (Leiste ueber dem Feed, Auswahl beim Schreiben).
 * Wer den Kreis nicht sehen darf, bekommt hier nichts (prueft ContentContainerController).
 */
class LeisteController extends ContentContainerController
{
    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $kreis = $this->contentContainer;
        if (!$kreis instanceof Space || !TopicPicker::showTopicPicker($kreis)) {
            return ['themen' => []];
        }
        $themen = $this->module->themen($kreis);
        $allgemein = $this->module->allgemein($kreis, $themen);
        if ($allgemein !== null && !in_array($allgemein, $themen, true)) {
            $themen = $this->module->themen($kreis);   // gerade angelegt
        }
        return [
            'themen' => array_map(fn($t) => ['id' => (int)$t->id, 'name' => (string)$t->name], $themen),
            'vorgabe' => $allgemein?->id,
            'verwalten' => $kreis->getPermissionManager()->can(ManageTopics::class) ? $kreis->createUrl('/topic/manage') : null,
        ];
    }
}
