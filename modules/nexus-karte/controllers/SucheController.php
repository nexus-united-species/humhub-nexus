<?php

namespace nexus\modules\karte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use nexus\modules\karte\models\Suchender;
use nexus\modules\karte\services\Ortssuche;
use nexus\modules\karte\services\Texte;
use Yii;

/**
 * "Ich suche Mitstreiter in meiner Region": Jedes Mitglied pflegt nur seinen EIGENEN Eintrag
 * (die Zeile haengt an der eigenen Konto-Nummer, nie an einer mitgeschickten).
 */
class SucheController extends Controller
{
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function actionIndex(string $q = '')
    {
        $treffer = [];
        $sucheOk = true;
        if (trim($q) !== '') {
            [$treffer, $sucheOk] = Ortssuche::suche($q);
        }
        return $this->render('index', [
            'eintrag' => $this->meinEintrag(),
            'suchText' => $q,
            'treffer' => $treffer,
            'sucheOk' => $sucheOk,
        ]);
    }

    public function actionSpeichern()
    {
        $this->forcePostRequest();
        $r = Yii::$app->request;
        $eintrag = $this->meinEintrag() ?? new Suchender(['user_id' => (int)Yii::$app->user->id]);
        $lat = $r->post('lat');
        $lng = $r->post('lng');
        $eintrag->ort = (string)$r->post('ort');
        $eintrag->nachricht = (string)$r->post('nachricht');
        if (is_numeric($lat) && is_numeric($lng)) {
            $eintrag->lat = $lat;
            $eintrag->lng = $lng;
        }
        $ok = $eintrag->save();
        Yii::$app->session->setFlash($ok ? 'nexus-karte-ok' : 'nexus-karte-fehler', Texte::t($ok ? 's_gespeichert' : 's_fehler'));
        return $this->redirect($ok ? ['/nexus-karte/karte/index'] : ['index']);
    }

    /** Nur den Text aendern -- der Ort bleibt. */
    public function actionNachricht()
    {
        $this->forcePostRequest();
        $eintrag = $this->meinEintrag();
        if ($eintrag !== null) {
            $eintrag->nachricht = (string)Yii::$app->request->post('nachricht');
            $ok = $eintrag->save();
            Yii::$app->session->setFlash($ok ? 'nexus-karte-ok' : 'nexus-karte-fehler', Texte::t($ok ? 's_gespeichert' : 's_fehler'));
        }
        return $this->redirect(['index']);
    }

    public function actionLoeschen()
    {
        $this->forcePostRequest();
        Suchender::deleteAll(['user_id' => (int)Yii::$app->user->id]);
        Yii::$app->session->setFlash('nexus-karte-ok', Texte::t('s_geloescht'));
        return $this->redirect(['index']);
    }

    private function meinEintrag(): ?Suchender
    {
        return Suchender::findOne(['user_id' => (int)Yii::$app->user->id]);
    }
}
