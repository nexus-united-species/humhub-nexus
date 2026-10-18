<?php

namespace nexus\modules\themen\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use humhub\modules\topic\models\Topic;
use humhub\modules\topic\permissions\ManageTopics;
use humhub\modules\topic\widgets\TopicPicker;
use nexus\modules\themen\Module;
use Yii;
use yii\web\NotFoundHttpException;
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

    /** Aktuelles Thema eines Beitrags -- fuer die Auswahl im Bearbeiten-Formular (nur wer ihn bearbeiten darf). */
    public function actionBeitrag(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $beitrag = Post::findOne($id);
        if ($beitrag === null || (int)$beitrag->content->contentcontainer_id !== (int)$this->contentContainer->contentcontainer_id
            || !$beitrag->content->canEdit()) {
            throw new NotFoundHttpException();
        }
        $thema = Topic::find()->innerJoin('content_tag_relation r', 'r.tag_id = content_tag.id')
            ->where(['r.content_id' => $beitrag->content->id])->orderBy(['content_tag.sort_order' => SORT_ASC])->one();
        return ['thema' => $thema?->id];
    }
}
