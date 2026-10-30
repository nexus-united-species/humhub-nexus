<?php

namespace nexus\modules\projekte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\space\models\Space;
use nexus\modules\projekte\assets\ProjekteAsset;
use nexus\modules\projekte\models\Aenderung;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\Module;
use nexus\modules\projekte\services\Dateien;
use nexus\modules\projekte\services\Entscheidung;
use nexus\modules\projekte\services\Nachrichten;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Vorschlaege: einbringen, ueberarbeiten (neue Fassung), entscheiden, weitergeben, Beschlussliste.
 *
 * Ein Vorschlag ist eine Kachel der Art "vorschlag" auf der Projekt-Tafel. Wer ihn einbringt, ist
 * verantwortlich und gibt ihn zur Abstimmung; ueber ihn wird nach der Regel des Projekts entschieden
 * (services/Entscheidung.php). Aendert sich der Vorschlag waehrend der Runde, beginnt eine neue Runde --
 * Stellungnahmen gelten immer genau der Fassung, die man gesehen hat.
 */
class VorschlagController extends ContentContainerController
{
    use KreisZugriff;

    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['speichern', 'auswerten', 'zurueckziehen', 'weitergabe']],
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

    // ── Einbringen und ueberarbeiten ─────────────────────────────────────────

    public function actionFormular(int $p, int $k = 0)
    {
        $projekt = $this->projekt($p);
        $karte = $k > 0 ? $this->vorschlag($projekt, $k) : $this->neuerVorschlag($projekt);
        $this->pruefe($karte->isNewRecord ? Rechte::darfVorschlagen($projekt, $this->ich()) : Rechte::darfVorschlagBearbeiten($karte, $this->ich()));
        $aenderungen = array_map(fn(Aenderung $a) => ['stelle' => $a->stelle, 'alt' => (string)$a->alt, 'neu' => (string)$a->neu], $karte->aenderungen);
        return $this->formular($projekt, $karte, $aenderungen, null);
    }

    public function actionSpeichern(int $p, int $k = 0)
    {
        $projekt = $this->projekt($p);
        $ich = $this->ich();
        $karte = $k > 0 ? $this->vorschlag($projekt, $k) : $this->neuerVorschlag($projekt);
        $this->pruefe($karte->isNewRecord ? Rechte::darfVorschlagen($projekt, $ich) : Rechte::darfVorschlagBearbeiten($karte, $ich));
        $r = Yii::$app->request;
        $vorher = $karte->isNewRecord ? null : $this->fingerabdruck($karte, $this->alsListe($karte->aenderungen));

        $karte->titel = trim((string)$r->post('titel'));
        $karte->zusammenfassung = trim((string)$r->post('zusammenfassung')) ?: null;
        $karte->begruendung = trim((string)$r->post('begruendung')) ?: null;
        $karte->setzeLinks($this->linksAus((string)$r->post('links')));
        $aenderungen = $this->aenderungenAus();
        $fehler = $this->pruefeAenderungen($aenderungen);
        if ($fehler !== null || !$karte->validate()) {
            return $this->formular($projekt, $karte, $aenderungen, $fehler ?? implode(' ', $karte->getErrorSummary(true)));
        }
        if ($karte->isNewRecord) {
            $karte->sortierung = (int)Karte::find()->where(['projekt_id' => $projekt->id])->max('sortierung') + 1;
        }
        $karte->save(false);
        $this->speichereAenderungen($karte, $aenderungen);
        ProjektController::speichereSchritte($karte, (string)$r->post('schritte'));

        [$fassung, $uploadFehler] = Dateien::hochladbar($this->kreis())
            ? Dateien::hochladen($karte, UploadedFile::getInstancesByName('dateien'), (string)$r->post('fassung_hinweis'), $ich)
            : [0, []];
        $meldung = Texte::t('gespeichert');
        $geaendert = $vorher !== null && ($fassung > 0 || $vorher !== $this->fingerabdruck($karte, $aenderungen));
        if ($geaendert && $karte->spalte === 'abstimmung') {
            $karte->runde++;
            $karte->save(false);
            Nachrichten::neueRunde($karte, $ich);
            $meldung = Texte::t('neue_runde');
        }
        if ($uploadFehler !== []) {
            $meldung .= ' ' . sprintf(Texte::t('datei_fehler'), implode('; ', $uploadFehler));
        }
        Yii::$app->session->setFlash(ProjektController::FLASH, $meldung);
        return $this->redirect($karte->url());
    }

    // ── Entscheiden ──────────────────────────────────────────────────────────

    public function actionAuswerten(int $k)
    {
        $karte = $this->vorschlagImKreis($k);
        $ich = $this->ich();
        $this->pruefe(Rechte::darfEntscheiden($karte, $ich) && $karte->spalte === 'abstimmung');
        $wahl = (string)Yii::$app->request->post('entscheidung');
        if ($wahl === 'ueberarbeiten') {
            $karte->spalte = 'arbeit';
            $karte->save(false);
            Yii::$app->session->setFlash(ProjektController::FLASH, Texte::t('gespeichert'));
            return $this->redirect($karte->url());
        }
        if (!in_array($wahl, ['annehmen', 'ablehnen'], true)) {
            throw new NotFoundHttpException();
        }
        $auswertung = Entscheidung::auswertung($karte);
        // Konsent und Mehrheit sind verbindlich: gegen das Ergebnis der Runde kann niemand "annehmen"
        if ($wahl === 'annehmen' && $auswertung['ergebnis'] !== 'angenommen' && Entscheidung::regelFuer($karte) !== 'einfach') {
            Yii::$app->session->setFlash(ProjektController::FLASH, Texte::t('einwand_blockiert'));
            return $this->redirect($karte->url());
        }
        $karte->ergebnis = $wahl === 'annehmen' ? 'angenommen' : 'abgelehnt';
        $karte->ergebnis_text = $auswertung['text'];
        $karte->ergebnis_am = date('Y-m-d H:i:s');
        $karte->spalte = 'fertig';
        $karte->save(false);
        Nachrichten::ergebnis($karte, $ich);
        Yii::$app->session->setFlash(ProjektController::FLASH, Texte::t('gespeichert'));
        return $this->redirect($karte->url());
    }

    public function actionZurueckziehen(int $k)
    {
        $karte = $this->vorschlagImKreis($k);
        $ich = $this->ich();
        $this->pruefe(Rechte::darfZurueckziehen($karte, $ich));
        $warInRunde = $karte->spalte === 'abstimmung';
        $karte->ergebnis = 'zurueckgezogen';
        $karte->ergebnis_text = null;
        $karte->ergebnis_am = date('Y-m-d H:i:s');
        $karte->spalte = 'fertig';
        $karte->save(false);
        if ($warInRunde) {
            Nachrichten::ergebnis($karte, $ich);
        }
        return $this->redirect($karte->url());
    }

    /** Weitergabe (z. B. an die DAO) und Umsetzung vermerken */
    public function actionWeitergabe(int $k)
    {
        $karte = $this->vorschlagImKreis($k);
        $this->pruefe(Rechte::darfEntscheiden($karte, $this->ich()) && $karte->ergebnis === 'angenommen');
        $r = Yii::$app->request;
        $status = (string)$r->post('weitergabe_status');
        $status = in_array($status, Karte::WEITERGABE, true) ? $status : null;
        if ($status !== $karte->weitergabe_status) {
            $karte->weitergabe_status = $status;
            $karte->weitergabe_am = $status === null ? null : date('Y-m-d');
        }
        $umgesetzt = mb_substr(trim((string)$r->post('umgesetzt')), 0, 190) ?: null;
        if ($umgesetzt !== $karte->umgesetzt) {
            $karte->umgesetzt = $umgesetzt;
            $karte->umgesetzt_am = $umgesetzt === null ? null : date('Y-m-d');
        }
        $karte->save(false);
        Yii::$app->session->setFlash(ProjektController::FLASH, Texte::t('gespeichert'));
        return $this->redirect($karte->url());
    }

    // ── Weitergeben und nachlesen ────────────────────────────────────────────

    /** Beschlussvorlage: druckbare Seite (PDF ueber den Browser) oder als Word-Datei */
    public function actionVorlage(int $k, string $format = 'html')
    {
        $karte = $this->vorschlagImKreis($k);
        $word = $format === 'word';
        $inhalt = $this->renderPartial('vorlage', [
            'kreis' => $this->kreis(),
            'karte' => $karte,
            'stimmen' => $karte->stimmen(),
            'fassungen' => Dateien::verfuegbar() ? Dateien::fassungen($karte) : [],
            'word' => $word,
        ]);
        $antwort = Yii::$app->response;
        $antwort->format = Response::FORMAT_RAW;
        if ($word) {
            $name = trim((string)preg_replace('/[^\p{L}\p{N} ._-]+/u', ' ', Texte::t('beschlussvorlage') . ' - ' . $karte->titel));
            $antwort->headers->set('Content-Type', 'application/msword; charset=UTF-8');
            $antwort->headers->set('Content-Disposition', 'attachment; filename="' . mb_substr($name, 0, 120) . '.doc"');
        } else {
            $antwort->headers->set('Content-Type', 'text/html; charset=UTF-8');
        }
        return $inhalt;
    }

    public function actionBeschluesse(int $p)
    {
        $projekt = $this->projekt($p);
        $vorschlaege = Karte::find()->where(['projekt_id' => $projekt->id, 'art' => 'vorschlag'])
            ->with('verantwortlich')->orderBy(['ergebnis_am' => SORT_DESC, 'id' => SORT_DESC])->all();
        foreach ($vorschlaege as $v) {
            $v->populateRelation('projekt', $projekt);
        }
        return $this->render('beschluesse', [
            'kreis' => $this->kreis(),
            'projekt' => $projekt,
            'entschieden' => array_values(array_filter($vorschlaege, fn($v) => $v->ergebnis !== null)),
            'laufend' => array_values(array_filter($vorschlaege, fn($v) => $v->ergebnis === null)),
        ]);
    }

    // ── Hilfen ─────────────────────────────────────────────────────────────

    private function neuerVorschlag(Projekt $projekt): Karte
    {
        $ich = $this->ich();
        $karte = new Karte(['projekt_id' => $projekt->id, 'art' => 'vorschlag', 'spalte' => 'arbeit',
            'verantwortlich_id' => $ich->id, 'created_by' => $ich->id, 'runde' => 0]);
        $karte->populateRelation('projekt', $projekt);
        return $karte;
    }

    private function vorschlag(Projekt $projekt, int $id): Karte
    {
        $karte = $this->karte($projekt, $id);
        if (!$karte->istVorschlag()) {
            throw new NotFoundHttpException();
        }
        return $karte;
    }

    private function vorschlagImKreis(int $id): Karte
    {
        $karte = $this->karteImKreis($id);
        if (!$karte->istVorschlag()) {
            throw new NotFoundHttpException();
        }
        return $karte;
    }

    private function formular(Projekt $projekt, Karte $karte, array $aenderungen, ?string $fehler)
    {
        return $this->render('formular', [
            'kreis' => $this->kreis(),
            'projekt' => $projekt,
            'karte' => $karte,
            'aenderungen' => $aenderungen,
            'fassungen' => !$karte->isNewRecord && Dateien::verfuegbar() ? Dateien::fassungen($karte) : [],
            'dateienMoeglich' => Dateien::hochladbar($this->kreis()),
            'fehler' => $fehler,
        ]);
    }

    /** @return array<int, array{stelle: string, alt: string, neu: string}> leere Zeilen fallen weg */
    private function aenderungenAus(): array
    {
        $r = Yii::$app->request;
        $stellen = (array)$r->post('aenderung_stelle', []);
        $alt = (array)$r->post('aenderung_alt', []);
        $neu = (array)$r->post('aenderung_neu', []);
        $liste = [];
        foreach ($stellen as $i => $stelle) {
            $zeile = ['stelle' => trim((string)$stelle), 'alt' => trim((string)($alt[$i] ?? '')), 'neu' => trim((string)($neu[$i] ?? ''))];
            if ($zeile['stelle'] !== '' || $zeile['alt'] !== '' || $zeile['neu'] !== '') {
                $liste[] = $zeile;
            }
        }
        return $liste;
    }

    private function pruefeAenderungen(array $aenderungen): ?string
    {
        if (count($aenderungen) > Aenderung::MAX) {
            return Texte::t('textaenderungen') . ': max. ' . Aenderung::MAX;
        }
        foreach ($aenderungen as $a) {
            if ($a['stelle'] === '' || mb_strlen($a['stelle']) > 255) {
                return Texte::t('stelle') . ': ' . Texte::t('stelle_hilfe');
            }
            if ($a['alt'] === $a['neu'] || mb_strlen($a['alt']) > Aenderung::MAX_TEXT || mb_strlen($a['neu']) > Aenderung::MAX_TEXT) {
                return Texte::t('textaenderungen') . ' – ' . $a['stelle'];
            }
        }
        return null;
    }

    private function speichereAenderungen(Karte $karte, array $aenderungen): void
    {
        Aenderung::deleteAll(['karte_id' => $karte->id]);
        foreach ($aenderungen as $i => $a) {
            (new Aenderung(['karte_id' => $karte->id, 'stelle' => $a['stelle'], 'alt' => $a['alt'] ?: null,
                'neu' => $a['neu'] ?: null, 'sortierung' => $i]))->save(false);
        }
        unset($karte->aenderungen);
    }

    /** @param Aenderung[] $aenderungen */
    private function alsListe(array $aenderungen): array
    {
        return array_map(fn(Aenderung $a) => ['stelle' => $a->stelle, 'alt' => (string)$a->alt, 'neu' => (string)$a->neu], $aenderungen);
    }

    /** Woran man erkennt, dass sich der Inhalt geaendert hat: Titel, Zusammenfassung, Begruendung, Textaenderungen */
    private function fingerabdruck(Karte $karte, array $aenderungen): string
    {
        return md5(json_encode([$karte->titel, (string)$karte->zusammenfassung, (string)$karte->begruendung, $aenderungen], JSON_UNESCAPED_UNICODE));
    }
}
