<?php

namespace nexus\modules\projekte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\assets\ProjekteAsset;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\models\Schritt;
use nexus\modules\projekte\models\Stimme;
use nexus\modules\projekte\Module;
use nexus\modules\projekte\services\Nachrichten;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Projekte eines Kreises: Uebersicht, Tafel mit Kacheln, Formulare und alle Knoepfe.
 * Rechte: services/Rechte.php. Jede Aktion prueft, dass Projekt und Kachel zu DIESEM Kreis gehoeren.
 */
class ProjektController extends ContentContainerController
{
    private const FLASH = 'nexus-projekte';

    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['speichern', 'loeschen', 'mitmachen', 'karte-speichern', 'karte-loeschen',
                'uebernehmen', 'verschieben', 'schritt', 'stimme']],
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
        $this->pruefe($karte->isNewRecord ? Rechte::darfLeiten($projekt, $this->ich()) : Rechte::darfKarteBearbeiten($karte, $this->ich()));
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
        $this->pruefe($karte->isNewRecord ? Rechte::darfLeiten($projekt, $ich) : Rechte::darfKarteBearbeiten($karte, $ich));
        $r = Yii::$app->request;
        $vorher = ['verantwortlich' => (int)$karte->verantwortlich_id, 'helfer' => $karte->isNewRecord ? [] : array_map(fn($u) => (int)$u->id, $karte->helfer())];

        $karte->titel = trim((string)$r->post('titel'));
        $karte->beschreibung = trim((string)$r->post('beschreibung')) ?: null;
        $karte->frist = trim((string)$r->post('frist')) ?: null;
        $karte->setzeLinks($this->linksAus((string)$r->post('links')));
        $darfZuweisen = Rechte::darfLeiten($projekt, $ich);
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
        if ($darfZuweisen) {
            $helfer = $this->personen((array)$r->post('helfer', []));
            $karte->setzeHelfer(array_map(fn($u) => (int)$u->id, $helfer));
            $this->meldeZuweisungen($karte, $vorher, $helfer);
        }
        Yii::$app->session->setFlash(self::FLASH, Texte::t('gespeichert'));
        return $this->redirect($karte->url());
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
        $karte->spalte = $ziel;
        if ($ziel === 'abstimmung') {
            $bis = trim((string)Yii::$app->request->post('abstimmung_bis'));
            $karte->abstimmung_bis = preg_match('/^\d{4}-\d{2}-\d{2}$/', $bis) && $bis >= date('Y-m-d')
                ? $bis : date('Y-m-d', strtotime('+' . (int)$this->module->abstimmungTage . ' days'));
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

    public function actionStimme(int $k)
    {
        $karte = $this->karteImKreis($k);
        $ich = $this->ich();
        $this->pruefe(Rechte::darfAbstimmen($karte, $ich));
        $art = Yii::$app->request->post('art') === 'aenderung' ? 'aenderung' : 'ok';
        $text = trim((string)Yii::$app->request->post('text'));
        if ($art === 'aenderung' && mb_strlen($text) < Stimme::MIN_ZEICHEN) {
            Yii::$app->session->setFlash(self::FLASH, Texte::t('zu_kurz'));
            return $this->redirect($karte->url());
        }
        $stimme = Stimme::findOne(['karte_id' => $karte->id, 'runde' => $karte->runde, 'user_id' => $ich->id])
            ?? new Stimme(['karte_id' => $karte->id, 'runde' => $karte->runde, 'user_id' => $ich->id]);
        $stimme->art = $art;
        $stimme->text = $art === 'aenderung' ? mb_substr($text, 0, Stimme::MAX_ZEICHEN) : null;
        $stimme->created_at = date('Y-m-d H:i:s');
        $stimme->save(false);
        if ($art === 'aenderung') {
            Nachrichten::aenderungswunsch($karte, $stimme, $ich);
        }
        Yii::$app->session->setFlash(self::FLASH, Texte::t('stimme_danke'));
        return $this->redirect($karte->url());
    }

    // ── Hilfen ─────────────────────────────────────────────────────────────

    private function kreis(): Space
    {
        return $this->contentContainer;
    }

    private function ich(): User
    {
        return Yii::$app->user->getIdentity();
    }

    private function pruefe(bool $erlaubt): void
    {
        if (!$erlaubt) {
            throw new ForbiddenHttpException(Texte::t('nur_mitglieder'));
        }
    }

    private function projekt(int $id): Projekt
    {
        $projekt = Projekt::findOne(['id' => $id, 'space_id' => $this->kreis()->id]);
        if ($projekt === null) {
            throw new NotFoundHttpException();
        }
        $projekt->populateRelation('kreis', $this->kreis());
        return $projekt;
    }

    private function karte(Projekt $projekt, int $id): Karte
    {
        $karte = Karte::findOne(['id' => $id, 'projekt_id' => $projekt->id]);
        if ($karte === null) {
            throw new NotFoundHttpException();
        }
        $karte->populateRelation('projekt', $projekt);
        return $karte;
    }

    private function karteImKreis(int $id): Karte
    {
        $karte = Karte::findOne(['id' => $id]);
        if ($karte === null) {
            throw new NotFoundHttpException();
        }
        return $this->karte($this->projekt((int)$karte->projekt_id), $id);
    }

    /**
     * Personen aus der Personenauswahl (GUIDs) -- nur Mitglieder dieses Kreises.
     * @return User[]
     */
    private function personen(array $guids): array
    {
        $guids = array_values(array_filter(array_map('strval', $guids)));
        if ($guids === []) {
            return [];
        }
        $kreis = $this->kreis();
        return array_values(array_filter(
            User::find()->where(['guid' => $guids, 'status' => User::STATUS_ENABLED])->all(),
            fn(User $u) => $kreis->isMember($u->id) || $u->isSystemAdmin()
        ));
    }

    /** "Titel | https://..." oder nur die Adresse, eine je Zeile */
    private function linksAus(string $text): array
    {
        $liste = [];
        foreach (preg_split('/\R/', $text) as $zeile) {
            $teile = array_map('trim', explode('|', $zeile, 2));
            if (count($teile) === 2) {
                $liste[] = ['titel' => $teile[0], 'url' => $teile[1]];
            } elseif ($teile[0] !== '') {
                $liste[] = ['titel' => '', 'url' => $teile[0]];
            }
        }
        return $liste;
    }

    /** Teilschritte aus dem Textfeld: bekannte behalten ihren Haken, neue kommen dazu, fehlende fallen weg */
    private function speichereSchritte(Karte $karte, string $text): void
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
     * @return array<int, array{ok: int, aenderung: int}>
     */
    private function stimmenZahl(array $karten): array
    {
        $zahl = [];
        foreach ($karten as $k) {
            if ($k->spalte === 'abstimmung') {
                $arten = Stimme::find()->select(['n' => 'COUNT(*)', 'art'])->where(['karte_id' => $k->id, 'runde' => $k->runde])->groupBy('art')->indexBy('art')->column();
                $zahl[$k->id] = ['ok' => (int)($arten['ok'] ?? 0), 'aenderung' => (int)($arten['aenderung'] ?? 0)];
            }
        }
        return $zahl;
    }
}
