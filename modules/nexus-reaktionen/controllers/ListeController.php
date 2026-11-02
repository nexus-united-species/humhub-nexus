<?php

namespace nexus\modules\reaktionen\controllers;

use humhub\components\behaviors\AccessControl;
use humhub\components\Controller;
use humhub\models\RecordMap;
use humhub\modules\content\interfaces\ContentProvider;
use nexus\modules\reaktionen\services\Reaktionen;
use Throwable;
use Yii;

/**
 * Reaktionen vieler Beitraege/Kommentare einer Seite auf einmal (eine Anfrage statt hunderte). Das Skript fragt nach
 * der Kennung des Gefaellt-mir-Links ("R|recordId"), gespeichert sind die Reaktionen je Klasse und Nummer.
 * Nur Objekte mit Reaktionen werden auf Sichtbarkeit geprueft; wer sie nicht sehen darf, bekommt sie nicht.
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
        $objekte = [];
        foreach (array_slice($eingabe, 0, $max) as $eintrag) {
            $teile = explode('|', (string)$eintrag);
            if (count($teile) !== 2 || $teile[0] !== 'R' || (int)$teile[1] <= 0) {
                continue;
            }
            $objekt = RecordMap::getById((int)$teile[1], ContentProvider::class, false);
            if ($objekt !== null) {
                $objekte[(string)$eintrag] = $objekt;
            }
        }

        $paare = array_map(fn($o) => [get_class($o), (int)$o->getPrimaryKey()], array_values($objekte));
        $alle = Reaktionen::zusammenfassung($paare, (int)Yii::$app->user->id);
        $sichtbar = [];
        foreach ($objekte as $eintrag => $objekt) {
            $liste = $alle[get_class($objekt) . '|' . $objekt->getPrimaryKey()] ?? null;
            if ($liste !== null && $this->darfSehen($objekt)) {
                $sichtbar[$eintrag] = $liste;
            }
        }

        Yii::$app->response->format = 'json';
        return ['ok' => true, 'objekte' => (object)$sichtbar];
    }

    private function darfSehen($objekt): bool
    {
        try {
            return $objekt->content->canView();
        } catch (Throwable $e) {
            return false;
        }
    }
}
