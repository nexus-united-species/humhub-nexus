<?php

namespace nexus\modules\karte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\space\models\Space;
use nexus\modules\karte\models\Standort;
use nexus\modules\karte\services\KartenDaten;
use nexus\modules\karte\services\Ortssuche;
use nexus\modules\karte\services\Texte;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Standorte pflegen. Zwei getrennte Rechte (siehe Module):
 * - "ist eine Gemeinschaft" (Zeile anlegen/entfernen): nur Systemadmins.
 * - den Ort einer Gemeinschaft setzen/aendern/entfernen: Systemadmins und die Kreis-Leitung.
 */
class VerwaltungController extends Controller
{
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function actionIndex(int $kreis = 0, int $space = 0, string $q = '')
    {
        $ich = Yii::$app->user->getIdentity();
        $verwaltbar = KartenDaten::verwaltbar($ich);
        if ($kreis > 0) {
            // Aufruf aus dem Zahnrad-Menue eines Kreises: nur dieser eine Kreis.
            $verwaltbar = array_values(array_filter($verwaltbar, fn(array $paar) => (int)$paar[0]->id === $kreis));
        }
        if ($verwaltbar === [] && !$ich->isSystemAdmin()) {
            throw new ForbiddenHttpException();
        }
        if ($kreis > 0 && $verwaltbar === []) {
            // Systemadmin in einem Kreis, der noch keine Gemeinschaft ist: gleich das Ortsfeld zeigen.
            // Die Zeile entsteht erst beim Eintragen des Orts (siehe standortZumAendern).
            $nochKeine = Space::findOne(['id' => $kreis, 'status' => Space::STATUS_ENABLED]);
            if ($nochKeine === null) {
                throw new NotFoundHttpException();
            }
            $verwaltbar = [[$nochKeine, new Standort(['space_id' => $nochKeine->id])]];
        }

        $treffer = [];
        $sucheOk = true;
        if ($space > 0 && trim($q) !== '') {
            $this->standortZumAendern($space);
            [$treffer, $sucheOk] = Ortssuche::suche($q);
        }

        $unmarkiert = [];
        if ($ich->isSystemAdmin() && $kreis === 0) {
            $markiert = Standort::find()->select('space_id')->column();
            $unmarkiert = Space::find()->where(['status' => Space::STATUS_ENABLED])
                ->andFilterWhere(['not in', 'id', $markiert])
                ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all();
        }

        return $this->render('index', [
            'verwaltbar' => $verwaltbar,
            'unmarkiert' => $unmarkiert,
            'istAdmin' => $ich->isSystemAdmin(),
            'nurKreis' => $kreis,
            'suchKreis' => $space,
            'suchText' => $q,
            'treffer' => $treffer,
            'sucheOk' => $sucheOk,
        ]);
    }

    public function actionSpeichern()
    {
        $this->forcePostRequest();
        $r = Yii::$app->request;
        $standort = $this->standortZumAendern((int)$r->post('space_id'));
        $lat = $r->post('lat');
        $lng = $r->post('lng');
        $standort->ort = (string)$r->post('ort');
        $standort->lat = $lat;
        $standort->lng = $lng;
        $standort->updated_by = (int)Yii::$app->user->id;
        $ok = is_numeric($lat) && is_numeric($lng) && trim($standort->ort) !== '' && $standort->save();
        Yii::$app->session->setFlash($ok ? 'nexus-karte-ok' : 'nexus-karte-fehler', Texte::t($ok ? 'v_gespeichert' : 'v_fehler'));
        return $this->zurueck();
    }

    public function actionOrtEntfernen()
    {
        $this->forcePostRequest();
        $standort = $this->standortZumAendern((int)Yii::$app->request->post('space_id'));
        $standort->ort = null;
        $standort->lat = null;
        $standort->lng = null;
        $standort->updated_by = (int)Yii::$app->user->id;
        $standort->save();
        Yii::$app->session->setFlash('nexus-karte-ok', Texte::t('v_ort_entfernt'));
        return $this->zurueck();
    }

    /** Nur Systemadmins: einen Kreis zur Gemeinschaft erklaeren. */
    public function actionMarkieren()
    {
        $this->forcePostRequest();
        $this->nurAdmin();
        $space = Space::findOne(['id' => (int)Yii::$app->request->post('space_id')]);
        if ($space === null) {
            throw new NotFoundHttpException();
        }
        if (Standort::findOne(['space_id' => $space->id]) === null) {
            $standort = new Standort(['space_id' => $space->id, 'updated_by' => (int)Yii::$app->user->id]);
            $standort->save();
        }
        Yii::$app->session->setFlash('nexus-karte-ok', Texte::t('a_markiert'));
        return $this->zurueck();
    }

    /** Nur Systemadmins: Markierung samt Ort entfernen. */
    public function actionEntmarkieren()
    {
        $this->forcePostRequest();
        $this->nurAdmin();
        Standort::deleteAll(['space_id' => (int)Yii::$app->request->post('space_id')]);
        Yii::$app->session->setFlash('nexus-karte-ok', Texte::t('a_entfernt'));
        return $this->zurueck();
    }

    /** Zurueck zur Liste -- oder zur Ein-Kreis-Ansicht, wenn man aus dem Zahnrad-Menue kam. */
    private function zurueck()
    {
        $kreis = (int)Yii::$app->request->post('kreis');
        return $this->redirect($kreis > 0 ? ['index', 'kreis' => $kreis] : ['index']);
    }

    private function nurAdmin(): void
    {
        if (!Yii::$app->user->getIdentity()->isSystemAdmin()) {
            throw new ForbiddenHttpException();
        }
    }

    /** Die Standort-Zeile eines Kreises -- nur wenn er eine Gemeinschaft ist (oder ich Systemadmin bin) und ich ihn aendern darf. */
    private function standortZumAendern(int $spaceId): Standort
    {
        $standort = Standort::findOne(['space_id' => $spaceId]);
        if ($standort === null && Yii::$app->user->getIdentity()->isSystemAdmin()) {
            // Nur ein Systemadmin macht einen Kreis zur Gemeinschaft -- hier durch das Eintragen des Orts.
            $standort = new Standort(['space_id' => $spaceId]);
        }
        $space = $standort?->space;
        if ($standort === null || $space === null) {
            throw new NotFoundHttpException();
        }
        if (!KartenDaten::darfOrtAendern(Yii::$app->user->getIdentity(), $space)) {
            throw new ForbiddenHttpException();
        }
        return $standort;
    }
}
