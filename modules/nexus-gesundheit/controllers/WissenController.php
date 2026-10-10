<?php

namespace nexus\modules\gesundheit\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\gesundheit\assets\GesundheitAsset;
use nexus\modules\gesundheit\models\Artikel;
use nexus\modules\gesundheit\models\Version;
use nexus\modules\gesundheit\models\Vorschlag;
use nexus\modules\gesundheit\Module;
use nexus\modules\gesundheit\services\Darstellung;
use nexus\modules\gesundheit\services\Nachrichten;
use nexus\modules\gesundheit\services\Suche;
use nexus\modules\gesundheit\services\Texte;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Seiten des Gesundheitswissens innerhalb des Kreises (Einstellung "kreis").
 *
 * Lesen: alle angemeldeten Menschen (nach der Freigabe), vorher nur Systemadmins.
 * Vorschlagen und "Im Kreis darueber sprechen": Mitglieder des Kreises.
 * Bearbeiten, Versionen wiederherstellen, Vorschlaege erledigen, Freigabe: nur Systemadmins.
 */
class WissenController extends ContentContainerController
{
    /** @var Module */
    public $module;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['vorschlag', 'sprechen', 'speichern', 'wiederherstellen', 'erledigen', 'freigabe']],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $kreis = $this->contentContainer;
        if (!$kreis instanceof Space || (int)$kreis->id !== (int)$this->module->kreis) {
            throw new NotFoundHttpException();
        }
        if (!$this->module->darfLesen($this->ich())) {
            throw new NotFoundHttpException();
        }
        GesundheitAsset::register($this->view);
        return true;
    }

    // ── Lesen ──────────────────────────────────────────────────────────────

    public function actionIndex(string $q = '', string $thema = '', string $wort = '')
    {
        $bestand = Suche::bestand();
        $liste = null;
        $ueberschrift = null;
        if (trim($q) !== '') {
            $liste = array_map(fn($t) => $t['artikel'], Suche::suche($q));
        } elseif (isset(Artikel::THEMEN[$thema])) {
            $liste = array_values(array_filter($bestand['artikel'], fn($a) => $a['thema'] === $thema));
            $ueberschrift = Texte::thema($thema);
        } elseif (trim($wort) !== '') {
            $ids = array_flip(Suche::mitSchlagwort($wort));
            $liste = array_values(array_filter($bestand['artikel'], fn($a) => isset($ids[$a['id']])));
            $ueberschrift = '#' . $wort;
        }
        $jeThema = [];
        foreach ($bestand['artikel'] as $a) {
            $jeThema[$a['thema']] = ($jeThema[$a['thema']] ?? 0) + 1;
        }
        return $this->render('index', [
            'kreis' => $this->contentContainer,
            'q' => $q,
            'liste' => $liste,
            'ueberschrift' => $ueberschrift,
            'anzahl' => count($bestand['artikel']),
            'jeThema' => $jeThema,
            'schlagworte' => Suche::haeufigeSchlagworte(),
            'istAdmin' => Module::istAdmin($this->ich()),
            'freigegeben' => $this->module->freigegeben(),
            'offeneVorschlaege' => Module::istAdmin($this->ich()) ? (int)Vorschlag::find()->where(['status' => 'offen'])->count() : 0,
        ]);
    }

    public function actionArtikel(string $a)
    {
        $artikel = $this->artikel($a);
        $kreis = $this->contentContainer;
        return $this->render('artikel', [
            'kreis' => $kreis,
            'artikel' => $artikel,
            'darstellung' => Darstellung::artikel($artikel, $kreis),
            'schlagworte' => $artikel->woerter('schlagwort'),
            'verwandt' => Suche::verwandt($artikel->id),
            'istAdmin' => Module::istAdmin($this->ich()),
            'darfVorschlagen' => $this->module->darfVorschlagen($this->ich()),
            'meldung' => Yii::$app->session->getFlash('nexus-gesundheit'),
        ]);
    }

    // ── Mitglieder des Kreises ─────────────────────────────────────────────

    public function actionVorschlag(string $a)
    {
        $artikel = $this->artikel($a);
        $ich = $this->ich();
        if (!$this->module->darfVorschlagen($ich)) {
            throw new ForbiddenHttpException(Texte::t('nur_mitglieder'));
        }
        $v = new Vorschlag([
            'artikel_id' => $artikel->id,
            'art' => (string)Yii::$app->request->post('art'),
            'text' => trim((string)Yii::$app->request->post('text')),
            'status' => 'offen',
            'created_by' => $ich->id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if (!$v->save()) {
            Yii::$app->session->setFlash('nexus-gesundheit', Texte::t('zu_kurz'));
            return $this->redirect($artikel->url($this->contentContainer));
        }
        Nachrichten::neuerVorschlag($v, $artikel, $this->contentContainer, $ich);
        Yii::$app->session->setFlash('nexus-gesundheit', Texte::t('vorschlag_danke'));
        return $this->redirect($artikel->url($this->contentContainer));
    }

    /** Erfahrungen und Gedanken gehoeren in den Kreis, nicht in den geprueften Artikeltext. */
    public function actionSprechen(string $a)
    {
        $artikel = $this->artikel($a);
        $kreis = $this->contentContainer;
        if (!$this->module->darfVorschlagen($this->ich())) {
            throw new ForbiddenHttpException(Texte::t('nur_mitglieder'));
        }
        $text = trim((string)Yii::$app->request->post('text'));
        if (mb_strlen($text) < Vorschlag::MIN_ZEICHEN) {
            Yii::$app->session->setFlash('nexus-gesundheit', Texte::t('zu_kurz'));
            return $this->redirect($artikel->url($kreis));
        }
        $post = new Post($kreis);
        $post->message = '🩺 [' . $artikel->titel . '](' . $artikel->url($kreis, true) . ")\n\n" . mb_substr($text, 0, Vorschlag::MAX_ZEICHEN);
        if (!$post->save()) {
            throw new ForbiddenHttpException(Texte::t('nur_mitglieder'));
        }
        return $this->redirect($post->content->getUrl());
    }

    // ── Admins ─────────────────────────────────────────────────────────────

    public function actionBearbeiten(string $a)
    {
        $this->nurAdmin();
        $artikel = $this->artikel($a);
        return $this->render('bearbeiten', [
            'kreis' => $this->contentContainer,
            'artikel' => $artikel,
            'schlagworte' => implode(', ', $artikel->woerter('schlagwort')),
            'suchwoerter' => implode(', ', $artikel->woerter('suchwort')),
            'fehler' => Yii::$app->session->getFlash('nexus-gesundheit-fehler'),
        ]);
    }

    public function actionSpeichern(string $a)
    {
        $this->nurAdmin();
        $artikel = $this->artikel($a);
        $r = Yii::$app->request;
        $artikel->titel = trim((string)$r->post('titel'));
        $artikel->kurztitel = Artikel::kurztitelAus($artikel->titel);
        $artikel->inhalt = str_replace("\r\n", "\n", (string)$r->post('inhalt'));
        $artikel->thema = (string)$r->post('thema');
        $artikel->pruefdatum = trim((string)$r->post('pruefdatum')) ?: null;
        $artikel->wiedervorlage = trim((string)$r->post('wiedervorlage')) ?: null;
        $artikel->im_portal_geaendert = 1;
        $artikel->updated_by = $this->ich()->id;
        if (!$artikel->validate()) {
            Yii::$app->session->setFlash('nexus-gesundheit-fehler', implode(' ', $artikel->getErrorSummary(true)));
            return $this->redirect($this->contentContainer->createUrl('/nexus-gesundheit/wissen/bearbeiten', ['a' => $artikel->slug]));
        }
        $artikel->save(false);
        $artikel->setzeWoerter('schlagwort', explode(',', (string)$r->post('schlagworte')));
        $artikel->setzeWoerter('suchwort', explode(',', (string)$r->post('suchwoerter')));
        Version::speichern($artikel, trim((string)$r->post('grund')) ?: 'Bearbeitung im Portal', $this->ich()->id);
        Yii::$app->session->setFlash('nexus-gesundheit', Texte::t('gespeichert'));
        return $this->redirect($artikel->url($this->contentContainer));
    }

    public function actionVersionen(string $a, int $zeige = 0)
    {
        $this->nurAdmin();
        $artikel = $this->artikel($a);
        $versionen = Version::find()->where(['artikel_id' => $artikel->id])->orderBy(['id' => SORT_DESC])->with('autor')->all();
        $gezeigt = $zeige > 0 ? Version::findOne(['id' => $zeige, 'artikel_id' => $artikel->id]) : null;
        return $this->render('versionen', [
            'kreis' => $this->contentContainer,
            'artikel' => $artikel,
            'versionen' => $versionen,
            'gezeigt' => $gezeigt,
            'gezeigtHtml' => $gezeigt ? Darstellung::nurText($gezeigt->inhalt, $this->contentContainer) : null,
        ]);
    }

    public function actionWiederherstellen(string $a, int $version)
    {
        $this->nurAdmin();
        $artikel = $this->artikel($a);
        $v = Version::findOne(['id' => $version, 'artikel_id' => $artikel->id]);
        if ($v === null) {
            throw new NotFoundHttpException();
        }
        $artikel->titel = $v->titel;
        $artikel->kurztitel = Artikel::kurztitelAus($v->titel);
        $artikel->inhalt = $v->inhalt;
        $artikel->im_portal_geaendert = 1;
        $artikel->updated_by = $this->ich()->id;
        $artikel->save(false);
        Version::speichern($artikel, 'Wiederhergestellt: Fassung vom ' . $v->created_at, $this->ich()->id);
        return $this->redirect($artikel->url($this->contentContainer));
    }

    public function actionVorschlaege(string $status = 'offen')
    {
        $this->nurAdmin();
        $status = in_array($status, ['offen', 'uebernommen', 'abgelehnt'], true) ? $status : 'offen';
        return $this->render('vorschlaege', [
            'kreis' => $this->contentContainer,
            'status' => $status,
            'vorschlaege' => Vorschlag::find()->where(['status' => $status])->with(['artikel', 'autor'])->orderBy(['id' => SORT_DESC])->limit(200)->all(),
        ]);
    }

    public function actionErledigen(int $id)
    {
        $this->nurAdmin();
        $v = Vorschlag::findOne(['id' => $id, 'status' => 'offen']);
        if ($v === null || $v->artikel === null) {
            throw new NotFoundHttpException();
        }
        $v->status = Yii::$app->request->post('ergebnis') === 'uebernommen' ? 'uebernommen' : 'abgelehnt';
        $v->antwort = trim((string)Yii::$app->request->post('antwort')) ?: null;
        $v->erledigt_am = date('Y-m-d H:i:s');
        $v->erledigt_von = $this->ich()->id;
        $v->save(false);
        Nachrichten::entschieden($v, $v->artikel, $this->contentContainer);
        return $this->redirect($this->contentContainer->createUrl('/nexus-gesundheit/wissen/vorschlaege'));
    }

    public function actionFreigabe()
    {
        $this->nurAdmin();
        $this->module->settings->set('freigegeben', Yii::$app->request->post('an') === '1' ? '1' : '0');
        return $this->redirect($this->contentContainer->createUrl('/nexus-gesundheit/wissen/index'));
    }

    // ── Hilfen ─────────────────────────────────────────────────────────────

    private function ich(): ?User
    {
        $ich = Yii::$app->user->getIdentity();
        return $ich instanceof User ? $ich : null;
    }

    private function nurAdmin(): void
    {
        if (!Module::istAdmin($this->ich())) {
            throw new ForbiddenHttpException();
        }
    }

    private function artikel(string $slug): Artikel
    {
        $artikel = Artikel::findOne(['slug' => $slug]);
        if ($artikel === null) {
            throw new NotFoundHttpException();
        }
        return $artikel;
    }
}
