<?php

namespace nexus\modules\reaktionen\controllers;

use humhub\components\behaviors\AccessControl;
use humhub\components\Controller;
use humhub\helpers\DataTypeHelper;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentAddonActiveRecord;
use nexus\modules\reaktionen\services\Reaktionen;
use Throwable;
use Yii;

/**
 * Reaktionen vieler Beitraege/Kommentare einer Seite auf einmal (eine Anfrage statt hunderte). Nur Objekte mit
 * Reaktionen werden auf Sichtbarkeit geprueft; wer sie nicht sehen darf, bekommt sie nicht.
 */
class ListeController extends Controller
{
    public function behaviors()
    {
        return [
            'acl' => ['class' => AccessControl::class],
        ];
    }

    public function actionIndex()
    {
        $this->forcePostRequest();
        $max = max(1, (int)Reaktionen::modul()->maxObjekte);
        $eingabe = (array)Yii::$app->request->post('objekte', []);
        $paare = [];
        foreach (array_slice($eingabe, 0, $max) as $eintrag) {
            $teile = explode('|', (string)$eintrag);
            if (count($teile) !== 2 || (int)$teile[1] <= 0) {
                continue;
            }
            $klasse = DataTypeHelper::matchClassType($teile[0], [ContentAddonActiveRecord::class, ContentActiveRecord::class]);
            if ($klasse !== null) {
                $paare[] = [$teile[0], (int)$teile[1]];
            }
        }

        $alle = Reaktionen::zusammenfassung($paare, (int)Yii::$app->user->id);
        $sichtbar = [];
        foreach ($alle as $key => $liste) {
            if ($this->darfSehen($key)) {
                $sichtbar[$key] = $liste;
            }
        }

        Yii::$app->response->format = 'json';
        return ['ok' => true, 'objekte' => (object)$sichtbar];
    }

    private function darfSehen(string $key): bool
    {
        try {
            [$model, $id] = explode('|', $key);
            /** @var ContentAddonActiveRecord|ContentActiveRecord|null $objekt */
            $objekt = $model::findOne(['id' => (int)$id]);
            if ($objekt === null) {
                return false;
            }
            $inhalt = $objekt instanceof ContentAddonActiveRecord ? $objekt->getSource() : $objekt;
            return $inhalt !== null && $inhalt->content->canView();
        } catch (Throwable $e) {
            return false;
        }
    }
}
