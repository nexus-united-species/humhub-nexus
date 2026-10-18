<?php

namespace nexus\modules\wissen\controllers;

use humhub\components\access\ControllerAccess;
use nexus\modules\wissen\models\Artikel;
use nexus\modules\wissen\models\SammlungEintrag;
use nexus\modules\wissen\models\Thema;
use nexus\modules\wissen\models\Version;
use nexus\modules\wissen\services\Bilder;
use nexus\modules\wissen\services\Hochladen;
use nexus\modules\wissen\services\Themen;
use nexus\modules\wissen\services\Texte;
use RuntimeException;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Redaktion einer Sammlung im Portal (Systemadmins, Besitzer und Admins des Kreises):
 * - neue Artikel schreiben oder aus einer Datei uebernehmen -- immer zuerst als Entwurf
 * - Entwuerfe veroeffentlichen oder verwerfen
 * - Kategorien anlegen, umbenennen, verschieben, loeschen (nur leere)
 * - Name, Beschreibung, Symbol und Hinweis einer im Portal angelegten Sammlung
 */
class RedaktionController extends WissenController
{
    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['hochladen', 'anlegen', 'veroeffentlichen', 'verwerfen', 'thema', 'sammlung-loeschen', 'bild-hochladen']],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->nurBearbeitende();
        return true;
    }

    // ── Neue Artikel ───────────────────────────────────────────────────────

    public function actionNeu(string $thema = '')
    {
        $artikel = new Artikel(['sammlung' => $this->sammlung->kennung, 'thema' => $thema, 'titel' => '', 'inhalt' => '']);
        return $this->formular($artikel, [], [], 0);
    }

    /** Datei lesen und das Formular damit fuellen -- gespeichert wird erst mit "Als Entwurf speichern". */
    public function actionHochladen()
    {
        $datei = UploadedFile::getInstanceByName('datei');
        $thema = (string)Yii::$app->request->post('thema');
        if ($datei === null || $datei->error !== UPLOAD_ERR_OK) {
            Yii::$app->session->setFlash(self::FLASH_FEHLER, Texte::t('hochladen_zu_gross'));
            return $this->redirect($this->sammlung->url('redaktion/neu', ['thema' => $thema]));
        }
        try {
            $gelesen = Hochladen::lesen($datei->tempName, $datei->name, $this->sammlung);
        } catch (RuntimeException $e) {
            Yii::$app->session->setFlash(self::FLASH_FEHLER, Texte::t('hochladen_' . $e->getMessage()));
            return $this->redirect($this->sammlung->url('redaktion/neu', ['thema' => $thema]));
        }
        $artikel = new Artikel([
            'sammlung' => $this->sammlung->kennung,
            'thema' => $thema,
            'titel' => $gelesen['titel'],
            // Die erste Ueberschrift ist der Titel -- im Text wuerde sie doppelt stehen
            'inhalt' => trim((string)preg_replace('/\A#\s+' . preg_quote($gelesen['titel'], '/') . '\s*\n/u', '', $gelesen['inhalt'])),
        ]);
        return $this->formular($artikel, $gelesen['schlagworte'], $gelesen['suchwoerter'], $gelesen['bilder'], $datei->name, $gelesen['bilderOk']);
    }

    public function actionAnlegen()
    {
        $r = Yii::$app->request;
        $titel = trim((string)$r->post('titel'));
        $artikel = new Artikel([
            'sammlung' => $this->sammlung->kennung,
            'schluessel' => Artikel::PORTAL_PRAEFIX . date('Ymd-His') . '-' . bin2hex(random_bytes(4)),
            'titel' => $titel,
            'kurztitel' => Artikel::kurztitelAus($titel),
            'inhalt' => str_replace("\r\n", "\n", (string)$r->post('inhalt')),
            'thema' => $this->gueltigesThema(trim((string)$r->post('thema'))),
            'pruefdatum' => trim((string)$r->post('pruefdatum')) ?: null,
            'wiedervorlage' => trim((string)$r->post('wiedervorlage')) ?: null,
            'entwurf' => 1,
            'im_portal_geaendert' => 1,
            'created_by' => $this->ich()->id,
            'updated_by' => $this->ich()->id,
        ]);
        $artikel->slug = Artikel::freierSlug($this->sammlung->kennung, $titel !== '' ? $titel : 'artikel');
        $artikel->datei = mb_substr($artikel->slug . '.md', 0, 190);
        if (!$artikel->validate()) {
            Yii::$app->session->setFlash(self::FLASH_FEHLER, implode(' ', $artikel->getErrorSummary(true)));
            return $this->formular($artikel, explode(',', (string)$r->post('schlagworte')), explode(',', (string)$r->post('suchwoerter')), 0);
        }
        $artikel->save(false);
        $artikel->setzeWoerter('schlagwort', explode(',', (string)$r->post('schlagworte')));
        $artikel->setzeWoerter('suchwort', explode(',', (string)$r->post('suchwoerter')));
        $herkunft = trim((string)$r->post('herkunft'));
        Version::speichern($artikel, $herkunft !== '' ? 'Neu aus Datei: ' . $herkunft : 'Neu im Portal geschrieben', $this->ich()->id);
        Yii::$app->session->setFlash(self::FLASH, Texte::t('entwurf_gespeichert') . $this->schlagworteErgaenzen($artikel));
        return $this->redirect($artikel->url($this->sammlung));
    }

    public function actionVeroeffentlichen(string $a)
    {
        $artikel = $this->artikel($a);
        if ($artikel->entwurf) {
            $artikel->entwurf = 0;
            $artikel->updated_by = $this->ich()->id;
            $artikel->save(false);
            Version::speichern($artikel, 'Veröffentlicht', $this->ich()->id);
            Yii::$app->session->setFlash(self::FLASH, Texte::t('veroeffentlicht'));
        }
        return $this->redirect($artikel->url($this->sammlung));
    }

    /** Nur Entwuerfe -- Veroeffentlichtes wird nicht geloescht (Vorschlaege und Versionen haengen daran). */
    public function actionVerwerfen(string $a)
    {
        $artikel = $this->artikel($a);
        if (!$artikel->entwurf) {
            throw new NotFoundHttpException();
        }
        $artikel->delete();
        Yii::$app->session->setFlash(self::FLASH, Texte::t('entwurf_verworfen'));
        return $this->redirect($this->sammlung->url());
    }

    /** Bild aus dem Editor (Knopf, Einfuegen, Hineinziehen): speichern und den Markdown-Baustein zurueckgeben. */
    public function actionBildHochladen()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $datei = UploadedFile::getInstanceByName('bild');
        if ($datei === null || $datei->error !== UPLOAD_ERR_OK || $datei->size > Bilder::MAX_BYTES) {
            return ['fehler' => Texte::t('bild_zu_gross')];
        }
        try {
            $name = Bilder::speichern($this->sammlung, (string)file_get_contents($datei->tempName));
        } catch (RuntimeException $e) {
            Yii::error('nexus-wissen: Bilder-Ordner nicht beschreibbar: ' . Bilder::wurzel(), 'nexus-wissen');
            return ['fehler' => Texte::t('bild_fehler')];
        }
        if ($name === null) {
            return ['fehler' => Texte::t('bild_format')];
        }
        return ['markdown' => Bilder::markdown($name, (string)pathinfo($datei->name, PATHINFO_FILENAME))];
    }

    // ── Kategorien ─────────────────────────────────────────────────────────

    public function actionThemen()
    {
        $anzahl = Artikel::inSammlung($this->sammlung->kennung)->select(['thema', 'n' => 'COUNT(*)'])->groupBy('thema')->indexBy('thema')->column();
        return $this->render('themen', [
            'sammlung' => $this->sammlung,
            'themen' => $this->sammlung->themenEintraege(),
            'anzahl' => array_map('intval', $anzahl),
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
            'fehler' => Yii::$app->session->getFlash(self::FLASH_FEHLER),
        ]);
    }

    public function actionThema()
    {
        $r = Yii::$app->request;
        $kennung = $this->sammlung->kennung;
        $aktion = (string)$r->post('aktion');
        $name = trim((string)$r->post('name'));
        $symbol = (string)$r->post('symbol');
        $symbol = in_array($symbol, Themen::SYMBOLE, true) ? $symbol : Themen::VORGABE_SYMBOL;
        $eintraege = $this->sammlung->themenEintraege();   // legt die Kategorien beim ersten Mal an
        if ($aktion === 'neu') {
            if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
                return $this->zurueckZuThemen(null, Texte::t('thema_name_fehlt'));
            }
            $letzte = $eintraege === [] ? 0 : max(array_map(fn(Thema $t) => (int)$t->sortierung, $eintraege));
            $t = Themen::anlegen($kennung, Themen::freierCode($kennung, $name), [Texte::sprache() => $name], $symbol, $letzte + 10);
            return $this->zurueckZuThemen(sprintf(Texte::t('thema_angelegt'), $t->anzeigeName()));
        }
        $t = Thema::findOne(['id' => (int)$r->post('id'), 'sammlung' => $kennung]);
        if ($t === null) {
            throw new NotFoundHttpException();
        }
        if ($aktion === 'aendern') {
            if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
                return $this->zurueckZuThemen(null, Texte::t('thema_name_fehlt'));
            }
            $t->setzeName($name, Texte::sprache());
            $t->symbol = $symbol;
            $t->save(false);
            return $this->zurueckZuThemen(Texte::t('gespeichert'));
        }
        if ($aktion === 'hoch' || $aktion === 'runter') {
            $this->sammlung->verschiebeThema((int)$t->id, $aktion === 'hoch' ? -1 : 1);
            return $this->zurueckZuThemen(null);
        }
        if ($aktion === 'loeschen') {
            // Nie Artikel verlieren: nur leere Kategorien (auch ohne Entwuerfe)
            if (Artikel::inSammlung($kennung)->andWhere(['thema' => $t->code])->exists()) {
                return $this->zurueckZuThemen(null, Texte::t('thema_nicht_leer'));
            }
            $t->delete();
            return $this->zurueckZuThemen(Texte::t('thema_geloescht'));
        }
        throw new NotFoundHttpException();
    }

    // ── Einstellungen einer im Portal angelegten Sammlung ──────────────────

    public function actionEinstellungen()
    {
        $eintrag = $this->eigenerEintrag();
        if (Yii::$app->request->isPost) {
            $r = Yii::$app->request;
            $eintrag->name = trim((string)$r->post('name'));
            $eintrag->beschreibung = trim((string)$r->post('beschreibung')) ?: null;
            $eintrag->hinweis = trim((string)$r->post('hinweis')) ?: null;
            $symbol = (string)$r->post('symbol');
            $eintrag->symbol = in_array($symbol, Themen::SYMBOLE, true) ? $symbol : Themen::VORGABE_SYMBOL;
            if ($eintrag->save()) {
                Yii::$app->session->setFlash(self::FLASH, Texte::t('gespeichert'));
                return $this->redirect($this->sammlung->url());
            }
        }
        return $this->render('@nexus-wissen/views/einrichten/index', [
            'eintrag' => $eintrag,
            'aktion' => $this->sammlung->url('redaktion/einstellungen'),
            'kreisName' => (string)$this->sammlung->kreis()?->name,
            'zurueck' => $this->sammlung->url(),
            'loeschenUrl' => Artikel::inSammlung($this->sammlung->kennung)->exists() ? null : $this->sammlung->url('redaktion/sammlung-loeschen'),
        ]);
    }

    /** Nur eine leere, im Portal angelegte Sammlung -- Artikel gehen so nie verloren. */
    public function actionSammlungLoeschen()
    {
        $eintrag = $this->eigenerEintrag();
        if (Artikel::inSammlung($this->sammlung->kennung)->exists()) {
            throw new NotFoundHttpException();
        }
        $kreis = $this->sammlung->kreis();
        Thema::deleteAll(['sammlung' => $this->sammlung->kennung]);
        $this->module->settings->delete('freigegeben.' . $this->sammlung->kennung);
        $eintrag->delete();
        return $this->redirect($kreis !== null ? $kreis->createUrl('/space/space/home') : '/');
    }

    // ── Hilfen ─────────────────────────────────────────────────────────────

    private function formular(Artikel $artikel, array $schlagworte, array $suchwoerter, int $bilder, string $herkunft = '', int $bilderOk = 0)
    {
        return $this->render('@nexus-wissen/views/wissen/bearbeiten', [
            'sammlung' => $this->sammlung,
            'artikel' => $artikel,
            'neu' => true,
            'bilder' => $bilder,
            'bilderOk' => $bilderOk,
            'herkunft' => $herkunft,
            'themen' => $this->sammlung->themen(),
            'schlagworte' => implode(', ', array_filter(array_map('trim', $schlagworte))),
            'suchwoerter' => implode(', ', array_filter(array_map('trim', $suchwoerter))),
            'fehler' => Yii::$app->session->getFlash(self::FLASH_FEHLER),
        ]);
    }

    private function gueltigesThema(string $code): string
    {
        return $code !== '' && isset($this->sammlung->themen()[$code]) ? $code : '';
    }

    private function zurueckZuThemen(?string $meldung, ?string $fehler = null)
    {
        if ($meldung !== null) {
            Yii::$app->session->setFlash(self::FLASH, $meldung);
        }
        if ($fehler !== null) {
            Yii::$app->session->setFlash(self::FLASH_FEHLER, $fehler);
        }
        return $this->redirect($this->sammlung->url('redaktion/themen'));
    }

    private function eigenerEintrag(): SammlungEintrag
    {
        $eintrag = $this->sammlung->istEigen() ? SammlungEintrag::findOne(['kennung' => $this->sammlung->kennung]) : null;
        if ($eintrag === null) {
            throw new NotFoundHttpException();
        }
        return $eintrag;
    }
}
