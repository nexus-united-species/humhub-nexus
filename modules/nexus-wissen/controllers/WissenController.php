<?php

namespace nexus\modules\wissen\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\wissen\assets\WissenAsset;
use nexus\modules\wissen\models\Artikel;
use nexus\modules\wissen\models\Version;
use nexus\modules\wissen\models\Vorschlag;
use nexus\modules\wissen\Module;
use nexus\modules\wissen\services\Darstellung;
use nexus\modules\wissen\services\Nachrichten;
use nexus\modules\wissen\services\Sammlung;
use nexus\modules\wissen\services\Suche;
use nexus\modules\wissen\services\Texte;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Seiten einer Wissenssammlung innerhalb ihres Kreises. Welche Sammlung, steht im Parameter "s".
 *
 * Lesen: alle angemeldeten Menschen (nach der Freigabe), vorher nur die Bearbeitenden.
 * Vorschlagen und "Im Kreis darueber sprechen": Mitglieder des Kreises.
 * Bearbeiten, Versionen wiederherstellen, Vorschlaege erledigen, Freigabe: die Bearbeitenden.
 */
class WissenController extends ContentContainerController
{
    private const FLASH = 'nexus-wissen';
    private const FLASH_FEHLER = 'nexus-wissen-fehler';

    /** @var Module */
    public $module;

    private Sammlung $sammlung;

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
        $sammlung = $this->module->sammlung((string)Yii::$app->request->get('s'));
        if (!$kreis instanceof Space || $sammlung === null || $sammlung->kreisId() !== (int)$kreis->id) {
            throw new NotFoundHttpException();
        }
        if (!$sammlung->darfLesen($this->ich())) {
            throw new NotFoundHttpException();
        }
        $this->sammlung = $sammlung;
        WissenAsset::register($this->view);
        return true;
    }

    // ── Lesen ──────────────────────────────────────────────────────────────

    public function actionIndex(string $q = '', string $thema = '', string $wort = '')
    {
        $s = $this->sammlung;
        $bestand = Suche::bestand($s->kennung);
        $jeThema = [];
        foreach ($bestand['artikel'] as $a) {
            $jeThema[$a['thema']] = ($jeThema[$a['thema']] ?? 0) + 1;
        }
        $liste = null;
        $ueberschrift = null;
        if (trim($q) !== '') {
            $liste = array_map(fn($t) => $t['artikel'], Suche::suche($s, $q));
        } elseif ($thema !== '' && isset($jeThema[$thema])) {
            $liste = array_values(array_filter($bestand['artikel'], fn($a) => $a['thema'] === $thema));
            $ueberschrift = $s->themaName($thema);
        } elseif (trim($wort) !== '') {
            $ids = array_flip(Suche::mitSchlagwort($s->kennung, $wort));
            $liste = array_values(array_filter($bestand['artikel'], fn($a) => isset($ids[$a['id']])));
            $ueberschrift = '#' . $wort;
        } elseif (array_keys($jeThema) === ['']) {
            // Sammlung ohne Themen: gleich alle Artikel zeigen
            $liste = array_values($bestand['artikel']);
        }
        $darfBearbeiten = $s->darfBearbeiten($this->ich());
        return $this->render('index', [
            'sammlung' => $s,
            'q' => $q,
            'liste' => $liste,
            'ueberschrift' => $ueberschrift,
            'anzahl' => count($bestand['artikel']),
            'themen' => array_intersect_key($s->themen(array_keys($jeThema)), array_filter($jeThema)),
            'jeThema' => $jeThema,
            'schlagworte' => Suche::haeufigeSchlagworte($s->kennung),
            'darfBearbeiten' => $darfBearbeiten,
            'offeneVorschlaege' => $darfBearbeiten ? (int)$this->vorschlaegeAbfrage('offen')->count() : 0,
        ]);
    }

    public function actionArtikel(string $a)
    {
        $artikel = $this->artikel($a);
        return $this->render('artikel', [
            'sammlung' => $this->sammlung,
            'artikel' => $artikel,
            'darstellung' => Darstellung::artikel($artikel, $this->sammlung),
            'schlagworte' => $artikel->woerter('schlagwort'),
            'verwandt' => Suche::verwandt($this->sammlung->kennung, $artikel->id),
            'darfBearbeiten' => $this->sammlung->darfBearbeiten($this->ich()),
            'darfVorschlagen' => $this->sammlung->darfVorschlagen($this->ich()),
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
        ]);
    }

    // ── Mitglieder des Kreises ─────────────────────────────────────────────

    public function actionVorschlag(string $a)
    {
        $artikel = $this->artikel($a);
        $ich = $this->ich();
        if (!$this->sammlung->darfVorschlagen($ich)) {
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
            Yii::$app->session->setFlash(self::FLASH, Texte::t('zu_kurz'));
            return $this->redirect($artikel->url($this->sammlung));
        }
        Nachrichten::neuerVorschlag($v, $artikel, $this->sammlung, $ich);
        Yii::$app->session->setFlash(self::FLASH, Texte::t('vorschlag_danke'));
        return $this->redirect($artikel->url($this->sammlung));
    }

    /** Erfahrungen und Gedanken gehoeren in den Kreis, nicht in den geprueften Artikeltext. */
    public function actionSprechen(string $a)
    {
        $artikel = $this->artikel($a);
        if (!$this->sammlung->darfVorschlagen($this->ich())) {
            throw new ForbiddenHttpException(Texte::t('nur_mitglieder'));
        }
        $text = trim((string)Yii::$app->request->post('text'));
        if (mb_strlen($text) < Vorschlag::MIN_ZEICHEN) {
            Yii::$app->session->setFlash(self::FLASH, Texte::t('zu_kurz'));
            return $this->redirect($artikel->url($this->sammlung));
        }
        $post = new Post($this->contentContainer);
        $post->message = $this->sammlung->emoji() . ' [' . $artikel->titel . '](' . $artikel->url($this->sammlung, true) . ")\n\n"
            . mb_substr($text, 0, Vorschlag::MAX_ZEICHEN);
        if (!$post->save()) {
            throw new ForbiddenHttpException(Texte::t('nur_mitglieder'));
        }
        return $this->redirect($post->content->getUrl());
    }

    // ── Bearbeitende ───────────────────────────────────────────────────────

    public function actionBearbeiten(string $a)
    {
        $this->nurBearbeitende();
        $artikel = $this->artikel($a);
        return $this->render('bearbeiten', [
            'sammlung' => $this->sammlung,
            'artikel' => $artikel,
            'themen' => $this->sammlung->themen(Artikel::inSammlung($this->sammlung->kennung)->select('thema')->distinct()->column()),
            'schlagworte' => implode(', ', $artikel->woerter('schlagwort')),
            'suchwoerter' => implode(', ', $artikel->woerter('suchwort')),
            'fehler' => Yii::$app->session->getFlash(self::FLASH_FEHLER),
        ]);
    }

    public function actionSpeichern(string $a)
    {
        $this->nurBearbeitende();
        $artikel = $this->artikel($a);
        $r = Yii::$app->request;
        $artikel->titel = trim((string)$r->post('titel'));
        $artikel->kurztitel = Artikel::kurztitelAus($artikel->titel);
        $artikel->inhalt = str_replace("\r\n", "\n", (string)$r->post('inhalt'));
        $artikel->thema = trim((string)$r->post('thema'));
        $artikel->pruefdatum = trim((string)$r->post('pruefdatum')) ?: null;
        $artikel->wiedervorlage = trim((string)$r->post('wiedervorlage')) ?: null;
        $artikel->im_portal_geaendert = 1;
        $artikel->updated_by = $this->ich()->id;
        if (!$artikel->validate()) {
            Yii::$app->session->setFlash(self::FLASH_FEHLER, implode(' ', $artikel->getErrorSummary(true)));
            return $this->redirect($this->sammlung->url('bearbeiten', ['a' => $artikel->slug]));
        }
        $artikel->save(false);
        $artikel->setzeWoerter('schlagwort', explode(',', (string)$r->post('schlagworte')));
        $artikel->setzeWoerter('suchwort', explode(',', (string)$r->post('suchwoerter')));
        Version::speichern($artikel, trim((string)$r->post('grund')) ?: 'Bearbeitung im Portal', $this->ich()->id);
        Yii::$app->session->setFlash(self::FLASH, Texte::t('gespeichert'));
        return $this->redirect($artikel->url($this->sammlung));
    }

    public function actionVersionen(string $a, int $zeige = 0)
    {
        $this->nurBearbeitende();
        $artikel = $this->artikel($a);
        $versionen = Version::find()->where(['artikel_id' => $artikel->id])->orderBy(['id' => SORT_DESC])->with('autor')->all();
        $gezeigt = $zeige > 0 ? Version::findOne(['id' => $zeige, 'artikel_id' => $artikel->id]) : null;
        return $this->render('versionen', [
            'sammlung' => $this->sammlung,
            'artikel' => $artikel,
            'versionen' => $versionen,
            'gezeigt' => $gezeigt,
            'gezeigtHtml' => $gezeigt ? Darstellung::nurText($gezeigt->inhalt, $this->sammlung) : null,
        ]);
    }

    public function actionWiederherstellen(string $a, int $version)
    {
        $this->nurBearbeitende();
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
        return $this->redirect($artikel->url($this->sammlung));
    }

    public function actionVorschlaege(string $status = 'offen')
    {
        $this->nurBearbeitende();
        $status = in_array($status, ['offen', 'uebernommen', 'abgelehnt'], true) ? $status : 'offen';
        return $this->render('vorschlaege', [
            'sammlung' => $this->sammlung,
            'status' => $status,
            'vorschlaege' => $this->vorschlaegeAbfrage($status)->with(['artikel', 'autor'])
                ->orderBy([Vorschlag::tableName() . '.id' => SORT_DESC])->limit(200)->all(),
        ]);
    }

    public function actionErledigen(int $id)
    {
        $this->nurBearbeitende();
        $v = $this->vorschlaegeAbfrage('offen')->andWhere([Vorschlag::tableName() . '.id' => $id])->one();
        if ($v === null || $v->artikel === null) {
            throw new NotFoundHttpException();
        }
        $v->status = Yii::$app->request->post('ergebnis') === 'uebernommen' ? 'uebernommen' : 'abgelehnt';
        $v->antwort = trim((string)Yii::$app->request->post('antwort')) ?: null;
        $v->erledigt_am = date('Y-m-d H:i:s');
        $v->erledigt_von = $this->ich()->id;
        $v->save(false);
        Nachrichten::entschieden($v, $v->artikel, $this->sammlung);
        return $this->redirect($this->sammlung->url('vorschlaege'));
    }

    public function actionFreigabe()
    {
        $this->nurBearbeitende();
        $this->sammlung->setzeFreigabe(Yii::$app->request->post('an') === '1');
        return $this->redirect($this->sammlung->url());
    }

    // ── Hilfen ─────────────────────────────────────────────────────────────

    private function ich(): ?User
    {
        $ich = Yii::$app->user->getIdentity();
        return $ich instanceof User ? $ich : null;
    }

    private function nurBearbeitende(): void
    {
        if (!$this->sammlung->darfBearbeiten($this->ich())) {
            throw new ForbiddenHttpException();
        }
    }

    private function artikel(string $slug): Artikel
    {
        $artikel = Artikel::findOne(['sammlung' => $this->sammlung->kennung, 'slug' => $slug]);
        if ($artikel === null) {
            throw new NotFoundHttpException();
        }
        return $artikel;
    }

    private function vorschlaegeAbfrage(string $status): \yii\db\ActiveQuery
    {
        return Vorschlag::find()->innerJoinWith('artikel a', false)
            ->where(['a.sammlung' => $this->sammlung->kennung, Vorschlag::tableName() . '.status' => $status]);
    }
}
