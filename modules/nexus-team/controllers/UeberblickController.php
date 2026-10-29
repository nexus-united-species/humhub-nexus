<?php

namespace nexus\modules\team\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use nexus\modules\team\assets\TeamAsset;
use nexus\modules\team\Module;
use nexus\modules\team\services\Daten;
use nexus\modules\team\services\Texte;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/** Die Uebersichtsseite fuer das Community-Team (Mitglieder der Team-Gruppe und Systemadmins). */
class UeberblickController extends Controller
{
    /** So weit darf man zurueckblaettern (Wochen) */
    public const MAX_WOCHEN = 260;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['begruesst', 'zuruecknehmen', 'woche-begruesst']],
        ];
    }

    private function nurTeam(): void
    {
        if (!Module::instanz()->istTeam(Yii::$app->user->getIdentity())) {
            throw new NotFoundHttpException();
        }
    }

    private static function woche(string $name): int
    {
        return max(0, min(self::MAX_WOCHEN, (int)Yii::$app->request->get($name, 0)));
    }

    public function actionIndex()
    {
        $this->nurTeam();
        TeamAsset::register($this->view);
        $this->view->title = Texte::t('titel');
        $ich = Yii::$app->user->getIdentity();
        $istAdmin = (bool)$ich->isSystemAdmin();
        $neueWoche = self::woche('neue');
        $offenWoche = self::woche('offen');
        $uebersicht = Daten::neueUebersicht();
        $neue = Daten::neueMitglieder($neueWoche);
        $meldungen = Daten::meldungen($istAdmin);
        $fragen = Daten::offeneBeitraege($offenWoche);
        $support = Daten::supportAnfragen();
        $ruhige = Daten::ruhigeKreise(30);
        $unbegruesstWoche = count(array_filter($neue, fn($m) => $m['begruesst'] === null));
        $aeltesteWoche = (int)floor((time() - $uebersicht['aelteste']) / (7 * 86400));

        return $this->render('index', [
            'ich' => $ich,
            'neue' => $neue,
            'neueWoche' => $neueWoche,
            'offenWoche' => $offenWoche,
            'uebersicht' => $uebersicht,
            'aeltesteWoche' => $aeltesteWoche,
            'unbegruesstWoche' => $unbegruesstWoche,
            'meldungen' => $meldungen,
            'fragen' => $fragen,
            'support' => $support,
            'ruhige' => $ruhige,
        ]);
    }

    public function actionBegruesst()
    {
        $this->nurTeam();
        $id = (int)Yii::$app->request->post('id');
        if ($id <= 0) {
            throw new NotFoundHttpException();
        }
        Daten::markiereBegruesst($id, (int)Yii::$app->user->id);
        return $this->zurueck();
    }

    public function actionZuruecknehmen()
    {
        $this->nurTeam();
        $id = (int)Yii::$app->request->post('id');
        if ($id <= 0) {
            throw new NotFoundHttpException();
        }
        Daten::nimmBegruessungZurueck($id);
        return $this->zurueck();
    }

    /** Alle Mitglieder einer Woche auf einmal als begruesst eintragen (z. B. fuer die Zeit vor dem Team-Werkzeug). */
    public function actionWocheBegruesst()
    {
        $this->nurTeam();
        $woche = max(0, min(self::MAX_WOCHEN, (int)Yii::$app->request->post('woche')));
        Daten::markiereWocheBegruesst($woche, (int)Yii::$app->user->id);
        return $this->zurueck();
    }

    /** Zurueck auf dieselbe Wochen-Ansicht, in der geklickt wurde. */
    private function zurueck()
    {
        $r = Yii::$app->request;
        $neue = max(0, min(self::MAX_WOCHEN, (int)$r->post('neue', 0)));
        $offen = max(0, min(self::MAX_WOCHEN, (int)$r->post('offen', 0)));
        return $this->redirect(['index', 'neue' => $neue, 'offen' => $offen]);
    }
}
