<?php

namespace nexus\modules\projekte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\space\models\Space;
use cebe\markdown\GithubMarkdown;
use humhub\modules\user\models\User;
use nexus\modules\projekte\assets\ProjekteAsset;
use nexus\modules\projekte\models\Antwort;
use nexus\modules\projekte\models\Datei;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\models\Schritt;
use nexus\modules\projekte\models\Stimme;
use nexus\modules\projekte\Module;
use nexus\modules\projekte\services\Auswahl;
use nexus\modules\projekte\services\Dateien;
use nexus\modules\projekte\services\Entscheidung;
use nexus\modules\projekte\services\Nachrichten;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;
use Yii;
use yii\helpers\HtmlPurifier;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Projekte eines Kreises: Uebersicht, Tafel mit Kacheln, Formulare und alle Knoepfe.
 * Rechte: services/Rechte.php. Jede Aktion prueft, dass Projekt und Kachel zu DIESEM Kreis gehoeren.
 */
class ProjektController extends ContentContainerController
{
    use KreisZugriff;

    public const FLASH = 'nexus-projekte';

    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['speichern', 'loeschen', 'mitmachen', 'karte-speichern', 'karte-loeschen',
                'uebernehmen', 'verschieben', 'schritt', 'stimme', 'antwort', 'dateien', 'datei-loeschen', 'fassung-loeschen']],
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

    // ── Ansehen ────────────────────────────────────────────────────────────

    public function actionIndex()
    {
        $kreis = $this->kreis();
        $projekte = Projekt::find()->where(['space_id' => $kreis->id])
            ->orderBy(['status' => SORT_ASC, 'frist' => SORT_ASC, 'id' => SORT_ASC])->all();
        $ich = $this->ich();
        $zahlen = [];
        foreach ($projekte as $p) {
            $zahlen[$p->id] = $p->fortschritt() + $this->offenFuerMich($p, $ich);
        }
        return $this->render('index', [
            'kreis' => $kreis,
            'aktive' => array_values(array_filter($projekte, fn($p) => $p->status === 'aktiv')),
            'abgeschlossene' => array_values(array_filter($projekte, fn($p) => $p->status !== 'aktiv')),
            'zahlen' => $zahlen,
            'darfAnlegen' => Rechte::darfProjektAnlegen($kreis, $ich),
        ]);
    }

    public function actionAnsicht(int $p, int $k = 0)
    {
        $projekt = $this->projekt($p);
        $karten = Karte::find()->where(['projekt_id' => $projekt->id])->with(['verantwortlich', 'schritte'])
            ->orderBy(['sortierung' => SORT_ASC, 'id' => SORT_ASC])->all();
        $gewaehlt = null;
        foreach ($karten as $karte) {
            $karte->populateRelation('projekt', $projekt);
            if ((int)$karte->id === $k) {
                $gewaehlt = $karte;
            }
        }
        return $this->render('ansicht', [
            'kreis' => $this->kreis(),
            'projekt' => $projekt,
            'karten' => $karten,
            'stimmenZahl' => $this->stimmenZahl($karten),
            'entwuerfe' => $this->entwuerfe($karten),
            'gewaehlt' => $gewaehlt,
            'ich' => $this->ich(),
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
        ]);
    }

    // ── Projekt anlegen, bearbeiten, mitmachen ──────────────────────────────

    public function actionFormular(int $p = 0)
    {
        $projekt = $p > 0 ? $this->projekt($p) : new Projekt(['space_id' => $this->kreis()->id, 'status' => 'aktiv']);
        $this->pruefe($projekt->isNewRecord ? Rechte::darfProjektAnlegen($this->kreis(), $this->ich()) : Rechte::darfLeiten($projekt, $this->ich()));
        return $this->render('formular', ['kreis' => $this->kreis(), 'projekt' => $projekt, 'fehler' => null]);
    }

    public function actionSpeichern(int $p = 0)
    {
        $kreis = $this->kreis();
        $projekt = $p > 0 ? $this->projekt($p) : new Projekt(['space_id' => $kreis->id, 'created_by' => $this->ich()->id]);
        $this->pruefe($projekt->isNewRecord ? Rechte::darfProjektAnlegen($kreis, $this->ich()) : Rechte::darfLeiten($projekt, $this->ich()));
        $r = Yii::$app->request;
        $projekt->titel = trim((string)$r->post('titel'));
        $projekt->ziel = trim((string)$r->post('ziel')) ?: null;
        $projekt->frist = trim((string)$r->post('frist')) ?: null;
        $projekt->status = $r->post('status') === 'abgeschlossen' ? 'abgeschlossen' : 'aktiv';
        $projekt->regel = Entscheidung::regel((string)$r->post('regel'));
        $projekt->frist_tage = (int)$r->post('frist_tage', 14);
        $projekt->weitergabe = trim((string)$r->post('weitergabe')) ?: null;
        if (!$projekt->save()) {
            return $this->render('formular', ['kreis' => $kreis, 'projekt' => $projekt, 'fehler' => implode(' ', $projekt->getErrorSummary(true))]);
        }
        // Ohne Auswahl leitet, wer das Projekt anlegt bzw. gerade bearbeitet
        $leitung = array_map(fn($u) => (int)$u->id, $this->personen((array)$r->post('leitung', [])));
        $projekt->setzeLeitung($leitung !== [] ? $leitung : [(int)$this->ich()->id]);
        Yii::$app->session->setFlash(self::FLASH, Texte::t('gespeichert'));
        return $this->redirect($projekt->url());
    }

    public function actionLoeschen(int $p)
    {
        $projekt = $this->projekt($p);
        $this->pruefe(Rechte::istKreisAdmin($this->kreis(), $this->ich()));
        $projekt->delete();
        return $this->redirect($this->kreis()->createUrl('/nexus-projekte/projekt/index'));
    }

    public function actionMitmachen(int $p)
    {
        $projekt = $this->projekt($p);
        $this->pruefe(Rechte::istMitglied($this->kreis(), $this->ich()));
        $projekt->setzeMitmacher((int)$this->ich()->id, Yii::$app->request->post('an') === '1');
        return $this->redirect($projekt->url());
    }

    // ── Kacheln ────────────────────────────────────────────────────────────

    public function actionKarte(int $p, int $k = 0)
    {
        $projekt = $this->projekt($p);
        $karte = $k > 0 ? $this->karte($projekt, $k) : new Karte(['projekt_id' => $projekt->id, 'spalte' => 'offen']);
        if ($karte->istVorschlag()) {
            return $this->redirect($this->kreis()->createUrl('/nexus-projekte/vorschlag/formular', ['p' => $projekt->id, 'k' => $karte->id]));
        }
        $this->pruefe($karte->isNewRecord ? Rechte::darfLeiten($projekt, $this->ich()) : Rechte::darfKarteBearbeiten($karte, $this->ich()));
        if ($karte->isNewRecord) {
            // Erst die Art waehlen, dann erscheint das passende Formular (Aufgabe / Bild-Auswahl) bzw. der Vorschlag
            $art = (string)Yii::$app->request->get('art');
            if (!in_array($art, ['aufgabe', 'auswahl'], true)) {
                return $this->render('karte_art', ['kreis' => $this->kreis(), 'projekt' => $projekt]);
            }
            $karte->art = $art;
        }
        return $this->render('karte', [
            'kreis' => $this->kreis(),
            'projekt' => $projekt,
            'karte' => $karte,
            'darfZuweisen' => Rechte::darfLeiten($projekt, $this->ich()),
            'fehler' => null,
        ]);
    }

    public function actionKarteSpeichern(int $p, int $k = 0)
    {
        $projekt = $this->projekt($p);
        $ich = $this->ich();
        $karte = $k > 0 ? $this->karte($projekt, $k) : new Karte(['projekt_id' => $projekt->id, 'spalte' => 'offen', 'created_by' => $ich->id]);
        $karte->populateRelation('projekt', $projekt);
        if ($karte->istVorschlag()) {
            throw new NotFoundHttpException();
        }
        $this->pruefe($karte->isNewRecord ? Rechte::darfLeiten($projekt, $ich) : Rechte::darfKarteBearbeiten($karte, $ich));
        $r = Yii::$app->request;
        $vorher = ['verantwortlich' => (int)$karte->verantwortlich_id, 'helfer' => $karte->isNewRecord ? [] : array_map(fn($u) => (int)$u->id, $karte->helfer())];

        $karte->titel = trim((string)$r->post('titel'));
        $karte->beschreibung = trim((string)$r->post('beschreibung')) ?: null;
        $karte->frist = trim((string)$r->post('frist')) ?: null;
        $karte->setzeLinks($this->linksAus((string)$r->post('links')));
        $darfZuweisen = Rechte::darfLeiten($projekt, $ich);
        // Aufgabe oder Bild-Auswahl -- eine Auswahl mit Entwuerfen bleibt eine, sonst haengen diese in der Luft
        $art = (string)$r->post('art');
        if ($darfZuweisen && in_array($art, ['aufgabe', 'auswahl'], true)
            && ($art === 'auswahl' || !$karte->istAuswahl() || Auswahl::optionen($karte) === [])) {
            $karte->art = $art;
        }
        if ($darfZuweisen) {
            $v = $this->personen((array)$r->post('verantwortlich', []))[0] ?? null;
            $karte->verantwortlich_id = $v?->id;
            if ($karte->spalte === 'offen' && $v !== null) {
                $karte->spalte = 'arbeit';
            }
        }
        if ($karte->isNewRecord) {
            $karte->sortierung = (int)Karte::find()->where(['projekt_id' => $projekt->id])->max('sortierung') + 1;
        }
        if (!$karte->save()) {
            return $this->render('karte', ['kreis' => $this->kreis(), 'projekt' => $projekt, 'karte' => $karte,
                'darfZuweisen' => $darfZuweisen, 'fehler' => implode(' ', $karte->getErrorSummary(true))]);
        }
        $this->speichereSchritte($karte, (string)$r->post('schritte'));
        $dateiMeldung = $this->speichereDateien($karte, $ich);
        if ($darfZuweisen) {
            $helfer = $this->personen((array)$r->post('helfer', []));
            $karte->setzeHelfer(array_map(fn($u) => (int)$u->id, $helfer));
            $this->meldeZuweisungen($karte, $vorher, $helfer);
        }
        Yii::$app->session->setFlash(self::FLASH, trim(Texte::t('gespeichert') . ' ' . $dateiMeldung));
        // Zurueck auf die Tafel, ohne eine Kachel aufzuschlagen: sonst steht dort immer der Text der zuletzt gespeicherten
        return $this->redirect($projekt->url());
    }

    /** Dateien, die schon im Kachel-Formular ausgewaehlt wurden (Fassung 1); leer, wenn nichts hochgeladen wurde. */
    private function speichereDateien(Karte $karte, User $ich): string
    {
        $dateien = UploadedFile::getInstancesByName('dateien');
        if ($dateien === [] || $karte->istAuswahl() || !Dateien::hochladbar($this->kreis())) {
            return '';
        }
        [$fassung, $fehler] = Dateien::hochladen($karte, $dateien, '', $ich);
        $meldung = $fassung > 0 ? sprintf(Texte::t('entwuerfe_ok'), $fassung) : '';
        if ($fehler !== []) {
            $meldung .= ($meldung !== '' ? ' ' : '') . sprintf(Texte::t('datei_fehler'), implode('; ', $fehler));
        }
        return $meldung;
    }

    /** Markdown- oder Textdatei einer Kachel bzw. eines Vorschlags im Portal lesen (ohne Herunterladen). */
    public function actionDateiLesen(int $k, int $d)
    {
        $karte = $this->karteImKreis($k);
        $datei = Datei::findOne(['id' => $d, 'karte_id' => $karte->id]) ?? throw new NotFoundHttpException();
        $info = $datei->info();
        if ($info === null || !$info['lesbar']) {
            throw new NotFoundHttpException();
        }
        $html = null;
        if ($info['groesse'] <= 1024 * 1024) {
            $pfad = $datei->kreisDatei->baseFile->getStore()->get();
            $text = (string)@file_get_contents($pfad);
            $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
            $html = preg_match('/\.txt$/i', $info['name'])
                ? '<pre class="nx-pj-lesen-text">' . \humhub\helpers\Html::encode($text) . '</pre>'
                : HtmlPurifier::process((new GithubMarkdown())->parse($text));
        }
        return $this->render('datei_lesen', ['kreis' => $this->kreis(), 'karte' => $karte, 'info' => $info, 'html' => $html]);
    }

    public function actionKarteLoeschen(int $k)
    {
        $karte = $this->karteImKreis($k);
        $this->pruefe(Rechte::darfLeiten($karte->projekt, $this->ich()));
        $projekt = $karte->projekt;
        $karte->delete();
        return $this->redirect($projekt->url());
    }

    public function actionUebernehmen(int $k)
    {
        $karte = $this->karteImKreis($k);
        $ich = $this->ich();
        $this->pruefe(Rechte::darfUebernehmen($karte, $ich));
        $karte->verantwortlich_id = $ich->id;
        if ($karte->spalte === 'offen') {
            $karte->spalte = 'arbeit';
        }
        $karte->save(false);
        $karte->setzeHelfer(array_map(fn($u) => (int)$u->id, $karte->helfer()));
        Nachrichten::uebernommen($karte, $ich);
        Yii::$app->session->setFlash(self::FLASH, Texte::t('uebernommen'));
        return $this->redirect($karte->url());
    }

    public function actionVerschieben(int $k)
    {
        $karte = $this->karteImKreis($k);
        $ziel = (string)Yii::$app->request->post('ziel');
        if (!in_array($ziel, Karte::SPALTEN, true) || $ziel === $karte->spalte) {
            throw new NotFoundHttpException();
        }
        $this->pruefe(Rechte::darfVerschieben($karte, $ziel, $this->ich()));
        if ($ziel === 'abstimmung' && $karte->istAuswahl() && Auswahl::optionen($karte) === []) {
            Yii::$app->session->setFlash(self::FLASH, Texte::t('auswahl_leer'));
            return $this->redirect($karte->url());
        }
        if ($karte->spalte === 'fertig' && $karte->istAuswahl()) {
            Auswahl::zuruecksetzen($karte);
        }
        if ($karte->spalte === 'fertig' && $karte->istVorschlag()) {
            // Wieder geoeffnet: das alte Ergebnis gilt nicht mehr
            $karte->ergebnis = $karte->ergebnis_text = $karte->ergebnis_am = null;
            $karte->weitergabe_status = $karte->weitergabe_am = $karte->umgesetzt = $karte->umgesetzt_am = null;
        }
        $karte->spalte = $ziel;
        if ($ziel === 'abstimmung') {
            $bis = trim((string)Yii::$app->request->post('abstimmung_bis'));
            $karte->abstimmung_bis = preg_match('/^\d{4}-\d{2}-\d{2}$/', $bis) && $bis >= date('Y-m-d')
                ? $bis : date('Y-m-d', strtotime('+' . Entscheidung::rundenTage($karte) . ' days'));
            $karte->runde++;
        }
        $karte->save(false);
        if ($ziel === 'abstimmung') {
            Nachrichten::abstimmungImKreis($karte);
            Yii::$app->session->setFlash(self::FLASH, Texte::t('abstimmung_hinweis'));
        }
        return $this->redirect($karte->url());
    }

    public function actionSchritt(int $k, int $s)
    {
        $karte = $this->karteImKreis($k);
        $this->pruefe(Rechte::darfAbhaken($karte, $this->ich()));
        $schritt = Schritt::findOne(['id' => $s, 'karte_id' => $karte->id]);
        if ($schritt === null) {
            throw new NotFoundHttpException();
        }
        $schritt->erledigt = $schritt->erledigt ? 0 : 1;
        $schritt->save(false);
        $karte->updateAttributes(['updated_at' => date('Y-m-d H:i:s')]);
        return $this->redirect($karte->url());
    }

    /**
     * Entwuerfe und Bilder an einer Kachel (Rueckmeldung 18.10.2026: "Wo stelle ich die Entwuerfe ein, die
     * begutachtet werden sollen?"). Gleiche Ablage wie bei Vorschlaegen: Dateibereich des Kreises unter
     * Projekte/<Projekt>/<Kachel>, jedes Hochladen eine neue Fassung. Wer an der Kachel beteiligt ist
     * (verantwortlich, hilft mit, Leitung), darf hochladen.
     */
    public function actionDateien(int $k)
    {
        $karte = $this->karteImKreis($k);
        $ich = $this->ich();
        $this->pruefe(!$karte->istVorschlag() && $karte->spalte !== 'fertig' && Rechte::darfAbhaken($karte, $ich)
            && Dateien::hochladbar($this->kreis()));
        $dateien = UploadedFile::getInstancesByName('dateien');
        if ($dateien === []) {
            Yii::$app->session->setFlash(self::FLASH, Texte::t('entwuerfe_keine'));
            return $this->redirect($karte->url());
        }
        [$fassung, $fehler] = Dateien::hochladen($karte, $dateien, (string)Yii::$app->request->post('fassung_hinweis'), $ich);
        $karte->updateAttributes(['updated_at' => date('Y-m-d H:i:s')]);
        $meldung = $fassung > 0 ? sprintf(Texte::t('entwuerfe_ok'), $fassung) : '';
        if ($fehler !== []) {
            $meldung .= ($meldung !== '' ? ' ' : '') . sprintf(Texte::t('datei_fehler'), implode('; ', $fehler));
        }
        Yii::$app->session->setFlash(self::FLASH, $meldung);
        // Das Hochladen mit Fortschrittsanzeige (projekte.js) schickt die Dateien per XHR und fragt JSON ab; die Meldung
        // steht schon in der Sitzung und erscheint, wenn die Seite danach neu geladen wird.
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['ok' => $fassung > 0, 'url' => $karte->url(true), 'fehler' => $fehler];
        }
        return $this->redirect($karte->url());
    }

    /** Eine hochgeladene Datei einer Kachel loeschen (Rechte::darfDateiLoeschen). */
    public function actionDateiLoeschen(int $k, int $d)
    {
        $karte = $this->karteImKreis($k);
        $datei = Datei::findOne(['id' => $d, 'karte_id' => $karte->id]) ?? throw new NotFoundHttpException();
        $this->pruefe(Rechte::darfDateiLoeschen($karte, $datei, $this->ich()));
        Dateien::loeschen($datei);
        $karte->updateAttributes(['updated_at' => date('Y-m-d H:i:s')]);
        Yii::$app->session->setFlash(self::FLASH, Texte::t('datei_geloescht'));
        return $this->redirect($karte->url());
    }

    /** Alle Dateien einer Fassung loeschen -- nur die, die die Person loeschen darf (die uebrigen bleiben). */
    public function actionFassungLoeschen(int $k, int $f)
    {
        $karte = $this->karteImKreis($k);
        $ich = $this->ich();
        $anzahl = 0;
        foreach (Datei::findAll(['karte_id' => $karte->id, 'fassung' => $f]) as $datei) {
            if (Rechte::darfDateiLoeschen($karte, $datei, $ich)) {
                Dateien::loeschen($datei);
                $anzahl++;
            }
        }
        if ($anzahl === 0) {
            throw new NotFoundHttpException();
        }
        $karte->updateAttributes(['updated_at' => date('Y-m-d H:i:s')]);
        Yii::$app->session->setFlash(self::FLASH, sprintf(Texte::t('fassung_geloescht'), $f, $anzahl));
        return $this->redirect($karte->url());
    }

    public function actionStimme(int $k)
    {
        $karte = $this->karteImKreis($k);
        $ich = $this->ich();
        $this->pruefe(Rechte::darfAbstimmen($karte, $ich));
        $regel = Entscheidung::regelFuer($karte);
        $art = (string)Yii::$app->request->post('art');
        if (!Entscheidung::istGueltig($regel, $art)) {
            throw new NotFoundHttpException();
        }
        $text = trim((string)Yii::$app->request->post('text'));
        if (Entscheidung::brauchtBegruendung($regel, $art) && mb_strlen($text) < Stimme::MIN_ZEICHEN) {
            Yii::$app->session->setFlash(self::FLASH, Texte::t('zu_kurz'));
            return $this->redirect($karte->url());
        }
        $stimme = Stimme::findOne(['karte_id' => $karte->id, 'runde' => $karte->runde, 'user_id' => $ich->id, 'option_id' => 0])
            ?? new Stimme(['karte_id' => $karte->id, 'runde' => $karte->runde, 'user_id' => $ich->id, 'option_id' => 0]);
        $vorher = $stimme->isNewRecord ? null : [$stimme->art, $stimme->text];
        $stimme->art = $art;
        // Auch bei Zustimmung darf man ein Wort dazuschreiben
        $stimme->text = $text !== '' ? mb_substr($text, 0, Stimme::MAX_ZEICHEN) : null;
        $stimme->created_at = date('Y-m-d H:i:s');
        $stimme->save(false);
        if (Entscheidung::brauchtBegruendung($regel, $art) && $vorher !== [$stimme->art, $stimme->text]) {
            Nachrichten::rueckmeldungMitText($karte, $stimme, $ich);
        }
        Yii::$app->session->setFlash(self::FLASH, Texte::t('stimme_danke'));
        return $this->redirect($karte->url());
    }

    /** Antwort auf eine Rueckmeldung (Stimme mit Text) dieser Kachel -- jedes Kreismitglied, in jeder Runde */
    public function actionAntwort(int $k, int $s)
    {
        $karte = $this->karteImKreis($k);
        $ich = $this->ich();
        $this->pruefe(Rechte::darfAntworten($karte, $ich));
        $stimme = Stimme::findOne(['id' => $s, 'karte_id' => $karte->id]);
        if ($stimme === null) {
            throw new NotFoundHttpException();
        }
        $text = trim((string)Yii::$app->request->post('text'));
        if (mb_strlen($text) < Antwort::MIN_ZEICHEN) {
            Yii::$app->session->setFlash(self::FLASH, Texte::t('antwort_zu_kurz'));
            return $this->redirect($karte->url());
        }
        $antwort = new Antwort(['stimme_id' => $stimme->id, 'user_id' => $ich->id, 'text' => mb_substr($text, 0, Antwort::MAX_ZEICHEN), 'created_at' => date('Y-m-d H:i:s')]);
        $antwort->save(false);
        $karte->updateAttributes(['updated_at' => date('Y-m-d H:i:s')]);
        Nachrichten::antwort($karte, $stimme, $antwort, $ich);
        Yii::$app->session->setFlash(self::FLASH, Texte::t('antwort_danke'));
        return $this->redirect($karte->url());
    }

    // ── Hilfen ─────────────────────────────────────────────────────────────

    /** Teilschritte aus dem Textfeld: bekannte behalten ihren Haken, neue kommen dazu, fehlende fallen weg */
    public static function speichereSchritte(Karte $karte, string $text): void
    {
        $zeilen = array_slice(array_values(array_filter(array_map(fn($z) => mb_substr(trim($z), 0, 255), preg_split('/\R/', $text)))), 0, Schritt::MAX);
        $vorhanden = [];
        foreach (Schritt::find()->where(['karte_id' => $karte->id])->all() as $s) {
            $vorhanden[$s->text][] = $s;
        }
        foreach ($zeilen as $i => $zeile) {
            $schritt = isset($vorhanden[$zeile]) && $vorhanden[$zeile] !== [] ? array_shift($vorhanden[$zeile]) : new Schritt(['karte_id' => $karte->id, 'text' => $zeile, 'erledigt' => 0]);
            $schritt->sortierung = $i;
            $schritt->save(false);
        }
        foreach ($vorhanden as $rest) {
            foreach ($rest as $s) {
                $s->delete();
            }
        }
    }

    /** @param User[] $helfer */
    private function meldeZuweisungen(Karte $karte, array $vorher, array $helfer): void
    {
        $ich = $this->ich();
        if ($karte->verantwortlich_id && (int)$karte->verantwortlich_id !== $vorher['verantwortlich'] && $karte->verantwortlich) {
            Nachrichten::zugewiesen($karte, $karte->verantwortlich, 'verantwortlich', $ich);
        }
        foreach ($helfer as $u) {
            if (!in_array((int)$u->id, $vorher['helfer'], true) && (int)$u->id !== (int)$karte->verantwortlich_id) {
                Nachrichten::zugewiesen($karte, $u, 'helfer', $ich);
            }
        }
    }

    /** @return array{warten: int, ueberfaellig: int} */
    private function offenFuerMich(Projekt $p, User $ich): array
    {
        $abstimmung = Karte::find()->where(['projekt_id' => $p->id, 'spalte' => 'abstimmung'])
            ->andWhere(['or', ['verantwortlich_id' => null], ['!=', 'verantwortlich_id', $ich->id]])->all();
        $warten = 0;
        foreach ($abstimmung as $k) {
            if (!Stimme::find()->where(['karte_id' => $k->id, 'runde' => $k->runde, 'user_id' => $ich->id])->exists()) {
                $warten++;
            }
        }
        $ueberfaellig = (int)Karte::find()->where(['projekt_id' => $p->id])->andWhere(['!=', 'spalte', 'fertig'])
            ->andWhere(['<', 'frist', date('Y-m-d')])->count();
        return ['warten' => $warten, 'ueberfaellig' => $ueberfaellig];
    }

    /**
     * @param Karte[] $karten
     * @return array<int, array<string, int>> Kachel => art => Anzahl in der laufenden Runde
     */
    /**
     * Bild-Auswahl auf der Tafel: wie viele Entwuerfe, wie viele haben mitgewaehlt, welcher wurde es
     * @return array<int, array{zahl: int, teilnehmer: int, gewaehlt: string}>
     */
    private function entwuerfe(array $karten): array
    {
        $liste = [];
        foreach ($karten as $k) {
            if (!$k->istAuswahl()) {
                continue;
            }
            $optionen = Auswahl::optionen($k);
            $gewaehlt = Auswahl::gewaehlt($optionen);
            $liste[$k->id] = [
                'zahl' => count($optionen),
                'teilnehmer' => $k->spalte === 'abstimmung' ? Auswahl::teilnehmer($k) : 0,
                'gewaehlt' => $gewaehlt ? Auswahl::buchstaben($optionen)[(int)$gewaehlt->id] : '',
            ];
        }
        return $liste;
    }

    private function stimmenZahl(array $karten): array
    {
        $zahl = [];
        foreach ($karten as $k) {
            if ($k->spalte === 'abstimmung' && !$k->istAuswahl()) {
                $zahl[$k->id] = Entscheidung::zahlen($k);
            }
        }
        return $zahl;
    }
}
