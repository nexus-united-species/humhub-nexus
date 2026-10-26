<?php

namespace nexus\modules\finanzen\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\user\models\User;
use nexus\modules\finanzen\assets\FinanzenAsset;
use nexus\modules\finanzen\models\Ausgabe;
use nexus\modules\finanzen\models\Eingang;
use nexus\modules\finanzen\models\Kachel;
use nexus\modules\finanzen\models\Verteilung;
use nexus\modules\finanzen\Module;
use nexus\modules\finanzen\services\Buchung;
use nexus\modules\finanzen\services\Geld;
use nexus\modules\finanzen\services\Stand;
use RuntimeException;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Verwaltung fuer die Finanzverantwortlichen: Kacheln, Eingaenge, Verteilung, Ausgaben, Rueckblick.
 * Einstellungen (wer verantwortlich ist, Webseiten, Deckel) nur fuer Systemadmins.
 */
class VerwaltungController extends Controller
{
    public const FLASH = 'nexus-finanzen';

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['kachel-speichern', 'kachel-loeschen', 'eingang', 'eingang-loeschen', 'vorschlagen',
                'bestaetigen', 'verwerfen', 'ausgabe', 'ausgabe-freigeben', 'ausgabe-loeschen', 'rueckblick', 'einstellungen-speichern']],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $ich = $this->ich();
        $nurAdmin = in_array($action->id, ['einstellungen', 'einstellungen-speichern'], true);
        if ($nurAdmin ? !$ich->isSystemAdmin() : !Module::instanz()->istVerantwortlich($ich)) {
            throw new ForbiddenHttpException('Das dürfen nur die Finanzverantwortlichen.');
        }
        FinanzenAsset::register($this->view);
        $this->view->registerJs(file_get_contents(dirname(__DIR__) . '/resources/bestaetigen.js'));
        return true;
    }

    public function actionIndex(?string $monat = null)
    {
        $this->view->title = 'Finanzierung verwalten';
        $monat = Geld::monat($monat);
        $stand = new Stand();
        return $this->render('index', [
            'stand' => $stand,
            'monat' => $monat,
            'vorschlag' => $stand->vorschlag($monat),
            'offeneVerteilung' => Verteilung::find()->where(['status' => ['vorgeschlagen', 'wartet_zweite']])->orderBy(['id' => SORT_DESC])->one(),
            'verteilungen' => Verteilung::find()->where(['status' => 'gebucht'])->orderBy(['id' => SORT_DESC])->limit(12)->all(),
            'eingaenge' => Eingang::find()->orderBy(['datum' => SORT_DESC, 'id' => SORT_DESC])->limit(60)->all(),
            'ausgaben' => Ausgabe::find()->orderBy(['datum' => SORT_DESC, 'id' => SORT_DESC])->limit(60)->all(),
            'rueckblick' => (string)Yii::$app->db->createCommand('SELECT text FROM nexus_fin_rueckblick WHERE monat = :m', [':m' => $monat])->queryScalar(),
            'ich' => $this->ich(),
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
        ]);
    }

    // ── Kacheln ─────────────────────────────────────────────────────────────

    public function actionKachel(int $id = 0)
    {
        $this->view->title = 'Kachel';
        $k = $id > 0 ? $this->kachel($id) : new Kachel(['bereich' => 'grund', 'art' => 'laufend', 'einordnung' => 'geschaetzt', 'rang' => 100, 'oeffentlich' => 1]);
        return $this->render('kachel', ['k' => $k, 'fehler' => null, 'verantwortliche' => Module::instanz()->verantwortlicheMenschen()]);
    }

    public function actionKachelSpeichern(int $id = 0)
    {
        $k = $id > 0 ? $this->kachel($id) : new Kachel(['created_by' => $this->ich()->id]);
        $r = Yii::$app->request;
        $k->bereich = (string)$r->post('bereich');
        $k->gruppe = trim((string)$r->post('gruppe')) ?: null;
        $k->titel = trim((string)$r->post('titel'));
        $k->beschreibung = trim((string)$r->post('beschreibung')) ?: null;
        $k->art = (string)$r->post('art');
        $k->betrag = Geld::lesen((string)$r->post('betrag')) ?? 0;
        $k->einordnung = (string)$r->post('einordnung');
        $k->freigegeben = (string)$r->post('freigegeben') === '1' ? 1 : 0;
        $k->zusatz = (string)$r->post('zusatz') ?: null;
        $k->rang = max(0, (int)$r->post('rang'));
        $k->oeffentlich = (string)$r->post('oeffentlich') === '1' ? 1 : 0;
        $b = (int)$r->post('beguenstigt');
        $k->beguenstigt_id = $b > 0 && User::findOne($b) !== null ? $b : null;
        try {
            Buchung::kachelSpeichern($this->ich(), $k);
        } catch (RuntimeException $e) {
            return $this->render('kachel', ['k' => $k, 'fehler' => $e->getMessage(), 'verantwortliche' => Module::instanz()->verantwortlicheMenschen()]);
        }
        return $this->zurueck('Gespeichert ✓', '#kacheln');
    }

    public function actionKachelLoeschen(int $id)
    {
        return $this->versuche(fn() => Buchung::kachelLoeschen($this->ich(), $this->kachel($id)), 'Kachel gelöscht.', '#kacheln');
    }

    // ── Eingaenge ───────────────────────────────────────────────────────────

    public function actionEingang()
    {
        $r = Yii::$app->request;
        $betrag = Geld::lesen((string)$r->post('betrag'));
        return $this->versuche(function () use ($r, $betrag) {
            if ($betrag === null) {
                throw new RuntimeException('Den Betrag konnte ich nicht lesen – bitte z. B. „25“ oder „25,50“ eingeben.');
            }
            Buchung::eingang($this->ich(), (string)$r->post('datum'), $betrag, (string)$r->post('quelle'),
                (int)$r->post('zweck') ?: null, (string)$r->post('referenz'), (string)$r->post('notiz'));
        }, 'Eingang erfasst ✓', '#eingaenge');
    }

    public function actionEingangLoeschen(int $id)
    {
        $e = Eingang::findOne($id) ?? throw new NotFoundHttpException();
        return $this->versuche(fn() => Buchung::eingangLoeschen($this->ich(), $e), 'Eingang gelöscht.', '#eingaenge');
    }

    // ── Verteilung ──────────────────────────────────────────────────────────

    public function actionVorschlagen()
    {
        $monat = Geld::monat((string)Yii::$app->request->post('monat'));
        return $this->versuche(fn() => Buchung::vorschlagen($this->ich(), $monat), 'Vorschlag erstellt – bitte prüfen und bestätigen.', '#verteilung', $monat);
    }

    public function actionBestaetigen(int $id)
    {
        $v = Verteilung::findOne($id) ?? throw new NotFoundHttpException();
        $ergebnis = '';
        $antwort = $this->versuche(function () use ($v, &$ergebnis) {
            $ergebnis = Buchung::bestaetigen($this->ich(), $v);
        }, null, '#verteilung', $v->monat);
        if ($ergebnis !== '') {
            Yii::$app->session->setFlash(self::FLASH, $ergebnis === 'gebucht'
                ? 'Verteilung gebucht ✓'
                : 'Bestätigt. Weil darin Geld für deine eigene Vergütung steckt, muss jetzt eine zweite Person freigeben – sie hat eine Nachricht bekommen.');
        }
        return $antwort;
    }

    public function actionVerwerfen(int $id)
    {
        $v = Verteilung::findOne($id) ?? throw new NotFoundHttpException();
        return $this->versuche(fn() => Buchung::verwerfen($this->ich(), $v), 'Vorschlag verworfen.', '#verteilung', $v->monat);
    }

    // ── Ausgaben ────────────────────────────────────────────────────────────

    public function actionAusgabe()
    {
        $r = Yii::$app->request;
        $betrag = Geld::lesen((string)$r->post('betrag'));
        return $this->versuche(function () use ($r, $betrag) {
            if ($betrag === null) {
                throw new RuntimeException('Den Betrag konnte ich nicht lesen.');
            }
            Buchung::ausgabe($this->ich(), $this->kachel((int)$r->post('kachel')), (string)$r->post('datum'), $betrag,
                (string)$r->post('notiz'), (string)$r->post('beleg'));
        }, 'Ausgabe erfasst ✓', '#ausgaben');
    }

    public function actionAusgabeFreigeben(int $id)
    {
        $a = Ausgabe::findOne($id) ?? throw new NotFoundHttpException();
        return $this->versuche(fn() => Buchung::ausgabeFreigeben($this->ich(), $a), 'Auszahlung freigegeben ✓', '#ausgaben');
    }

    public function actionAusgabeLoeschen(int $id)
    {
        $a = Ausgabe::findOne($id) ?? throw new NotFoundHttpException();
        return $this->versuche(fn() => Buchung::ausgabeLoeschen($this->ich(), $a), 'Ausgabe gelöscht.', '#ausgaben');
    }

    // ── Rueckblick ──────────────────────────────────────────────────────────

    public function actionRueckblick()
    {
        $r = Yii::$app->request;
        $monat = Geld::monat((string)$r->post('monat'));
        $text = mb_substr(trim((string)$r->post('text')), 0, 10000);
        $db = Yii::$app->db;
        $db->createCommand()->delete('nexus_fin_rueckblick', ['monat' => $monat])->execute();
        if ($text !== '') {
            $db->createCommand()->insert('nexus_fin_rueckblick', ['monat' => $monat, 'text' => $text, 'von' => $this->ich()->id,
                'updated_at' => date('Y-m-d H:i:s')])->execute();
        }
        return $this->zurueck('Rückblick gespeichert ✓', '#rueckblick', $monat);
    }

    // ── Einstellungen (nur Systemadmins) ────────────────────────────────────

    public function actionEinstellungen()
    {
        $this->view->title = 'Einstellungen Finanzierung';
        $m = Module::instanz();
        return $this->render('einstellungen', [
            'verantwortliche' => implode("\n", $m->verantwortliche()),
            'webseiten' => implode("\n", $m->webseiten()),
            'deckel' => $m->entwicklungsDeckel(),
            'mithelfenUrl' => $m->mithelfenUrl(),
            'fuerAlle' => $m->fuerAlle(),
        ]);
    }

    public function actionEinstellungenSpeichern()
    {
        $r = Yii::$app->request;
        $s = Module::instanz()->settings;
        $namen = array_filter(array_map('trim', preg_split('/[\s,;]+/', (string)$r->post('verantwortliche'))));
        $gueltig = User::find()->select('username')->where(['username' => $namen, 'status' => User::STATUS_ENABLED])->column();
        $s->set('verantwortliche', implode("\n", $gueltig));
        $seiten = array_filter(array_map('trim', preg_split('/\s+/', (string)$r->post('webseiten'))), fn($u) => preg_match('#^https://[a-z0-9.-]+$#i', $u));
        $s->set('webseiten', implode("\n", $seiten));
        $s->set('fuerAlle', (string)$r->post('fuerAlle') === '1' ? '1' : '0');
        $url = trim((string)$r->post('mithelfenUrl'));
        $s->set('mithelfenUrl', preg_match('#^https://\S+$#i', $url) ? $url : '');
        $s->set('entwicklungsDeckel', (string)(Geld::lesen((string)$r->post('deckel')) ?? 0));
        $fehlend = array_diff($namen, $gueltig);
        Yii::$app->session->setFlash(self::FLASH, 'Einstellungen gespeichert ✓' . ($fehlend ? ' – unbekannt und weggelassen: ' . implode(', ', $fehlend) : ''));
        return $this->redirect(['index']);
    }

    // ── Hilfen ──────────────────────────────────────────────────────────────

    private function ich(): User
    {
        return Yii::$app->user->getIdentity();
    }

    private function kachel(int $id): Kachel
    {
        return Kachel::findOne($id) ?? throw new NotFoundHttpException();
    }

    private function versuche(callable $tun, ?string $ok, string $anker, ?string $monat = null)
    {
        try {
            $tun();
            if ($ok !== null) {
                Yii::$app->session->setFlash(self::FLASH, $ok);
            }
        } catch (RuntimeException $e) {
            Yii::$app->session->setFlash(self::FLASH, '⚠️ ' . $e->getMessage());
        }
        return $this->redirect(['index', 'monat' => $monat ?? Geld::monat(Yii::$app->request->post('monat')), '#' => ltrim($anker, '#')]);
    }

    private function zurueck(string $meldung, string $anker, ?string $monat = null)
    {
        Yii::$app->session->setFlash(self::FLASH, $meldung);
        return $this->redirect(['index', 'monat' => $monat ?? Geld::monat(Yii::$app->request->post('monat')), '#' => ltrim($anker, '#')]);
    }
}
