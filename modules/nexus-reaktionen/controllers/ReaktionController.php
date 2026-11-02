<?php

namespace nexus\modules\reaktionen\controllers;

use humhub\components\behaviors\AccessControl;
use humhub\components\Controller;
use humhub\models\RecordMap;
use humhub\modules\content\interfaces\ContentProvider;
use humhub\modules\like\services\LikeService;
use nexus\modules\reaktionen\services\Reaktionen;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

/**
 * Eine Reaktion setzen. Beitrag oder Kommentar kommen ueber dieselbe Kennung wie beim Gefaellt-mir-Link (recordId, im
 * Skript als contentModel=R), geprueft wird wie dort: sehen duerfen und reagieren duerfen.
 */
class ReaktionController extends Controller
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
        $satz = (int)Yii::$app->request->get('contentId');
        $ziel = Yii::$app->request->get('contentModel') === 'R' ? RecordMap::getById($satz, ContentProvider::class) : null;
        if ($ziel === null) {
            throw new NotFoundHttpException();
        }
        if (!$ziel->content->canView() || !(new LikeService($ziel))->canLike()) {
            throw new ForbiddenHttpException();
        }
        $emoji = (string)Yii::$app->request->post('emoji', '');
        if (!Reaktionen::erlaubt($emoji)) {
            throw new HttpException(400, 'Unbekanntes Emoji.');
        }

        $was = Reaktionen::setzen((int)Yii::$app->user->id, get_class($ziel), (int)$ziel->getPrimaryKey(), $emoji);
        $schluessel = get_class($ziel) . '|' . $ziel->getPrimaryKey();
        $alle = Reaktionen::zusammenfassung([[get_class($ziel), (int)$ziel->getPrimaryKey()]], (int)Yii::$app->user->id);

        Yii::$app->response->format = 'json';
        return ['ok' => true, 'was' => $was, 'key' => 'R|' . $satz, 'reaktionen' => $alle[$schluessel] ?? []];
    }
}
