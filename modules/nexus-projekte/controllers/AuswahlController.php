<?php

namespace nexus\modules\projekte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\space\models\Space;
use nexus\modules\projekte\assets\ProjekteAsset;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Option;
use nexus\modules\projekte\models\Stimme;
use nexus\modules\projekte\Module;
use nexus\modules\projekte\services\Auswahl;
use nexus\modules\projekte\services\Dateien;
use nexus\modules\projekte\services\Nachrichten;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Bild-Auswahl (services/Auswahl.php): Entwuerfe einstellen und zuruecknehmen, je Entwurf Stellung nehmen,
 * Ergebnis festhalten. Alles nur per POST, jede Aktion prueft, dass Kachel und Entwurf zu diesem Kreis gehoeren.
 */
class AuswahlController extends ContentContainerController
{
    use KreisZugriff;

    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['hochladen', 'loeschen', 'stimme', 'festhalten', 'zurueck']],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!$this->contentContainer instanceof Space || !Module::istAn($this->contentContainer)) {
            throw new NotFoundHttpException();
        }
        ProjekteAsset::register($this->view);
        return true;
    }

    public function actionHochladen(int $k)
    {
        $karte = $this->karteImKreis($k);
        $ich = $this->ich();
        $this->pruefe(Rechte::darfEntwurfEinstellen($karte, $ich) && Dateien::hochladbar($this->kreis()));
        $datei = UploadedFile::getInstanceByName('datei');
        if ($datei === null) {
            return $this->zurKarte($karte, Texte::t('entwuerfe_keine'));
        }
        [$option, $fehler] = Auswahl::einstellen($karte, $datei, (string)Yii::$app->request->post('titel'), $ich);
        if ($option === null) {
            return $this->zurKarte($karte, sprintf(Texte::t('datei_fehler'), $fehler));
        }
        $karte->updateAttributes(['updated_at' => date('Y-m-d H:i:s')]);
        $buchstaben = Auswahl::buchstaben(Auswahl::optionen($karte));
        return $this->zurKarte($karte, sprintf(Texte::t('auswahl_eingestellt'), $buchstaben[(int)$option->id] ?? ''));
    }

    /** Entwurf zuruecknehmen -- die Datei bleibt im Dateibereich des Kreises */
    public function actionLoeschen(int $k, int $o)
    {
        $karte = $this->karteImKreis($k);
        $option = $this->option($karte, $o);
        $this->pruefe(Rechte::darfEntwurfLoeschen($karte, $option->created_by, $this->ich()));
        $option->delete();
        return $this->zurKarte($karte, Texte::t('auswahl_entfernt'));
    }

    public function actionStimme(int $k, int $o)
    {
        $karte = $this->karteImKreis($k);
        $option = $this->option($karte, $o);
        $ich = $this->ich();
        $this->pruefe(Rechte::darfAbstimmen($karte, $ich));
        $art = (string)Yii::$app->request->post('art');
        if (!isset(Auswahl::ARTEN[$art])) {
            throw new NotFoundHttpException();
        }
        $text = trim((string)Yii::$app->request->post('text'));
        $mitText = Auswahl::ARTEN[$art][3];
        if ($mitText && mb_strlen($text) < Stimme::MIN_ZEICHEN) {
            return $this->zurKarte($karte, Texte::t('zu_kurz'));
        }
        $schluessel = ['karte_id' => $karte->id, 'runde' => $karte->runde, 'user_id' => $ich->id, 'option_id' => $option->id];
        $stimme = Stimme::findOne($schluessel) ?? new Stimme($schluessel);
        $vorher = $stimme->isNewRecord ? null : [$stimme->art, $stimme->text];
        $stimme->art = $art;
        $stimme->text = $text !== '' ? mb_substr($text, 0, Stimme::MAX_ZEICHEN) : null;
        $stimme->created_at = date('Y-m-d H:i:s');
        $stimme->save(false);
        if ($mitText && $vorher !== [$stimme->art, $stimme->text]) {
            $buchstaben = Auswahl::buchstaben(Auswahl::optionen($karte));
            $entwurf = sprintf('%s %s „%s“', Texte::t('auswahl_entwurf'), $buchstaben[(int)$option->id] ?? '', $option->titel);
            Nachrichten::entwurfRueckmeldung($karte, $entwurf, $stimme, $ich);
        }
        return $this->zurKarte($karte, Texte::t('stimme_danke'));
    }

    /** Ergebnis festhalten -- nur ein Entwurf, der nach der Auswertung gewinnt (bei Gleichstand: einer davon) */
    public function actionFesthalten(int $k, int $o)
    {
        $karte = $this->karteImKreis($k);
        $option = $this->option($karte, $o);
        $ich = $this->ich();
        $this->pruefe(Rechte::darfAuswahlEntscheiden($karte, $ich));
        $optionen = Auswahl::optionen($karte);
        $auswertung = Auswahl::auswertung($karte, $optionen);
        if (!in_array((int)$option->id, $auswertung['sieger'], true)) {
            return $this->zurKarte($karte, Texte::t('auswahl_nicht_waehlbar'));
        }
        $buchstaben = Auswahl::buchstaben($optionen);
        Auswahl::festhalten($karte, $option, $auswertung['zahlen'][(int)$option->id], $buchstaben[(int)$option->id]);
        Nachrichten::auswahlErgebnis($karte, $ich);
        return $this->zurKarte($karte, Texte::t('auswahl_festgehalten'));
    }

    /** Kein Entwurf traegt (oder es braucht neue): zurueck ins Sammeln, die naechste Runde beginnt neu */
    public function actionZurueck(int $k)
    {
        $karte = $this->karteImKreis($k);
        $this->pruefe(Rechte::darfAuswahlEntscheiden($karte, $this->ich()));
        $karte->spalte = 'arbeit';
        $karte->save(false);
        return $this->zurKarte($karte, Texte::t('auswahl_zurueck_ok'));
    }

    private function option(Karte $karte, int $id): Option
    {
        $option = Option::findOne(['id' => $id, 'karte_id' => $karte->id]);
        if ($option === null) {
            throw new NotFoundHttpException();
        }
        return $option;
    }

    private function zurKarte(Karte $karte, string $meldung)
    {
        Yii::$app->session->setFlash(ProjektController::FLASH, $meldung);
        return $this->redirect($karte->url());
    }
}
