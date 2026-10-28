<?php

namespace nexus\modules\reaktionen\controllers;

use humhub\components\behaviors\AccessControl;
use humhub\modules\content\components\ContentAddonController;
use nexus\modules\reaktionen\services\Reaktionen;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;

/**
 * Eine Reaktion setzen. Die Basisklasse laedt Beitrag/Kommentar aus contentModel + contentId und prueft, dass der
 * Mensch ihn sehen darf -- dieselbe Pruefung wie beim Gefaellt-mir-Link.
 */
class ReaktionController extends ContentAddonController
{
    public function behaviors()
    {
        return [
            'acl' => ['class' => AccessControl::class],
        ];
    }

    public function actionSetzen()
    {
        $this->forcePostRequest();
        $likeModul = Yii::$app->getModule('like');
        if ($likeModul === null || !$likeModul->canLike($this->parentContent)) {
            throw new ForbiddenHttpException();
        }
        $emoji = (string)Yii::$app->request->post('emoji', '');
        if (!Reaktionen::erlaubt($emoji)) {
            throw new HttpException(400, 'Unbekanntes Emoji.');
        }

        $was = Reaktionen::setzen((int)Yii::$app->user->id, $this->contentModel, (int)$this->contentId, $emoji);
        $key = $this->contentModel . '|' . $this->contentId;
        $alle = Reaktionen::zusammenfassung([[$this->contentModel, (int)$this->contentId]], (int)Yii::$app->user->id);

        Yii::$app->response->format = 'json';
        return ['ok' => true, 'was' => $was, 'key' => $key, 'reaktionen' => $alle[$key] ?? []];
    }
}
