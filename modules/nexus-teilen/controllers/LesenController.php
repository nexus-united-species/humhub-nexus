<?php

namespace nexus\modules\teilen\controllers;

use humhub\components\Controller;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\widgets\richtext\converter\RichTextToHtmlConverter;
use humhub\modules\file\models\File;
use nexus\modules\teilen\models\Freigabe;
use nexus\modules\teilen\Module;
use nexus\modules\teilen\services\Inhalt;
use nexus\modules\teilen\services\Schaufenster;
use nexus\modules\teilen\services\Standbild;
use nexus\modules\teilen\services\Texte;
use Yii;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Die oeffentliche Leseseite -- auch fuer Gaeste. Zeigt GENAU einen freigegebenen Inhalt:
 * Text, Bilder und Anhaenge dieses Inhalts. Kein Name des Verfassers, keine Kommentare,
 * kein Kreis, keine Navigation ins Portal (Josh, 02.10.2026).
 * Dateien laufen ueber actionDatei: die normale Download-Adresse verlangt eine Anmeldung.
 */
class LesenController extends Controller
{
    public $layout = '@nexus-teilen/views/layouts/oeffentlich';

    /**
     * Nur diese Dateitypen zeigt der Browser direkt an; alles andere (HTML, SVG, ...) kommt als
     * Download. Sonst liefe eine angehaengte HTML-/SVG-Datei als Seite des Portals -- mit Zugriff
     * auf die Sitzung angemeldeter Besucher (Sicherheitspruefung 08.10.2026).
     */
    private const DIREKT_ANZEIGBAR = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'video/quicktime', 'video/ogg',
        'audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/webm', 'audio/wav', 'audio/x-wav',
        'application/pdf',
    ];

    public function actionIndex(string $t = '', string $sprache = '')
    {
        [$freigabe, $record] = $this->freigegeben($t);
        $this->kopfzeilen($record !== null && $freigabe->imSchaufenster());
        $seitenSprache = in_array($sprache, Module::SPRACHEN, true) ? $sprache : Texte::sprache();
        if ($record === null) {
            Yii::$app->response->statusCode = 404;
            return $this->render('weg', ['t' => Texte::alle($seitenSprache), 'sprache' => $seitenSprache]);
        }

        $fassung = Inhalt::fassung($record, $seitenSprache);
        $text = Inhalt::anonymisieren($fassung['text'], Texte::get('ein_mitglied', $seitenSprache));
        $html = self::oeffentlichesHtml($text, $record, $freigabe);
        $anhaenge = $this->anhaenge($record, $freigabe);
        $bild = $this->erstesBild($html, $anhaenge);
        $kurz = Inhalt::kurztext($text, 200);

        $this->view->params['nexusTeilen'] = array_merge($this->view->params['nexusTeilen'], [
            'sprache' => $seitenSprache,
            'titel' => Inhalt::ueberschrift($fassung['titel'], $text),
            'beschreibung' => $kurz,
            'bild' => $bild,
            'adresse' => $freigabe->adresse($seitenSprache),
            'sprachfassungen' => self::sprachfassungen(['/nexus-teilen/lesen/index', 't' => $freigabe->schluessel]),
        ]);

        return $this->render('index', [
            't' => Texte::alle($seitenSprache),
            'sprache' => $seitenSprache,
            'fassung' => $fassung,
            'original' => Inhalt::sprache($record),
            'html' => $html,
            'anhaenge' => $anhaenge,
            'datum' => (string)$record->content->created_at,
            'schluessel' => $freigabe->schluessel,
            'imPortal' => Yii::$app->user->isGuest ? null : Url::to(['/content/perma', 'id' => $record->content->id], true),
        ]);
    }

    /**
     * Die oeffentliche Seite "Aus der N.E.X.U.S.-Gemeinschaft": alle Inhalte, die ein Admin ins
     * Schaufenster gestellt hat, neueste zuerst, 12 je Seite.
     */
    public function actionSchaufenster(string $sprache = '', int $seite = 1)
    {
        $this->kopfzeilen(true);
        $sprache = in_array($sprache, Module::SPRACHEN, true) ? $sprache : Texte::sprache();
        $seite = max(1, $seite);
        $ab = ($seite - 1) * Schaufenster::JE_SEITE;
        $t = Texte::alle($sprache);
        $this->view->params['nexusTeilen'] = array_merge($this->view->params['nexusTeilen'], [
            'sprache' => $sprache,
            'titel' => $t['sf_titel'],
            'beschreibung' => $t['sf_intro'],
            'bild' => Url::to(Yii::$app->view->theme->getBaseUrl() . '/resources/img/nexus-logo-voll.jpg', true),
            'adresse' => Url::to(['/nexus-teilen/lesen/schaufenster', 'sprache' => $sprache, 'seite' => $seite > 1 ? $seite : null], true),
            'sprachfassungen' => self::sprachfassungen(['/nexus-teilen/lesen/schaufenster']),
        ]);
        return $this->render('schaufenster', [
            't' => $t,
            'sprache' => $sprache,
            'seite' => $seite,
            'karte' => $seite === 1 ? $this->gemeinschaftsKarte($sprache) : null,
            'karten' => Schaufenster::karten($sprache, Schaufenster::JE_SEITE, $ab),
            'aeltere' => Schaufenster::gibtAeltere($ab + Schaufenster::JE_SEITE),
            'imPortal' => null,
        ]);
    }

    /**
     * Datenquelle fuer die eigene Webseite (Einstellung "webseiten"): die neuesten
     * Schaufenster-Beitraege als JSON, damit die Webseite sie in ihrem eigenen Design zeigt.
     * Nur diese Webseiten duerfen das im Browser abrufen (CORS); 10 Minuten zwischenspeicherbar.
     */
    public function actionFeed(string $sprache = '', int $anzahl = 6)
    {
        $sprache = in_array($sprache, Module::SPRACHEN, true) ? $sprache : 'de';
        $anzahl = min(max($anzahl, 1), Schaufenster::JE_SEITE);
        $antwort = Yii::$app->response;
        $antwort->format = Response::FORMAT_JSON;
        $herkunft = (string)Yii::$app->request->headers->get('Origin');
        if (in_array($herkunft, Module::instanz()->webseiten, true)) {
            $antwort->headers->set('Access-Control-Allow-Origin', $herkunft);
            $antwort->headers->set('Vary', 'Origin');
        }
        header_remove('Pragma');
        header_remove('Expires');
        $antwort->headers->set('Cache-Control', 'public, max-age=600');
        $antwort->headers->set('X-Robots-Tag', 'noindex');
        $t = Texte::alle($sprache);
        $eintraege = array_map(fn(array $k) => [
            'titel' => $k['titel'],
            'text' => $k['text'],
            'bild' => $k['bild'],
            'video' => $k['video'],
            'datum' => substr($k['datum'], 0, 10),
            'link' => $k['link'],
        ], Schaufenster::karten($sprache, $anzahl));
        return [
            'titel' => $t['sf_titel'],
            'einleitung' => $t['sf_intro'],
            'weiterlesen' => $t['sf_weiterlesen'],
            'alle' => ['text' => $t['sf_alle'], 'link' => Url::to(['/nexus-teilen/lesen/schaufenster', 'sprache' => $sprache], true)],
            'mitmachen' => ['text' => $t['registrieren'], 'link' => Module::instanz()->registrierenUrl()],
            'eintraege' => $eintraege,
        ];
    }

    /** Eine Datei des freigegebenen Inhalts -- nur Dateien, die genau an diesem Inhalt haengen. */
    public function actionDatei(string $t = '', string $guid = '')
    {
        [, $record] = $this->freigegeben($t);
        $datei = $record === null ? null : File::findOne([
            'guid' => $guid,
            'object_model' => get_class($record),
            'object_id' => $record->id,
        ]);
        $pfad = $datei?->store->get();
        if ($datei === null || !is_file((string)$pfad)) {
            throw new NotFoundHttpException();
        }
        header_remove('Pragma');
        header_remove('Expires');
        Yii::$app->response->headers->set('Cache-Control', 'private, max-age=3600');
        Yii::$app->response->headers->set('X-Robots-Tag', 'noindex');
        Yii::$app->response->headers->set('X-Content-Type-Options', 'nosniff');
        $mime = strtolower((string)$datei->mime_type);
        $direkt = in_array($mime, self::DIREKT_ANZEIGBAR, true);
        return Yii::$app->response->sendFile($pfad, $datei->file_name, [
            'mimeType' => $direkt ? $mime : 'application/octet-stream',
            'inline' => $direkt,
        ]);
    }

    /** @return array{0: ?Freigabe, 1: ?ContentActiveRecord} */
    private function freigegeben(string $t): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $t)) {
            return [null, null];
        }
        $freigabe = Freigabe::findOne(['schluessel' => $t]);
        $record = $freigabe === null ? null : Inhalt::ausContentId((int)$freigabe->content_id);
        if ($record === null || (int)$record->content->state !== Content::STATE_PUBLISHED) {
            return [null, null];
        }
        return [$freigabe, $record];
    }

    /**
     * Suchmaschinen: Das Schaufenster und seine Beitraege duerfen gefunden werden (Josh, 03.10.2026:
     * "am besten geht sie viral") -- ausgewaehlt von Admins und anonymisiert. Links, die ein Mitglied
     * nur zum Weitergeben freigegeben hat, bleiben fuer Suchmaschinen gesperrt.
     */
    private function kopfzeilen(bool $indexieren = false): void
    {
        Yii::$app->response->headers->set('X-Robots-Tag', $indexieren ? 'index, follow' : 'noindex, nofollow');
        // same-origin statt no-referrer: Die Kartenbilder (nexus-karte) gibt es fuer Gaeste nur, wenn die
        // Anfrage erkennbar von unserer eigenen Seite kommt. Nach aussen geht weiter keine Herkunft mit.
        Yii::$app->response->headers->set('Referrer-Policy', 'same-origin');
        $this->view->params['nexusTeilen']['indexieren'] = $indexieren;
    }

    /**
     * Karte der Gemeinschaften aus dem Modul nexus-karte -- dieselbe wie auf der Gast-Startseite
     * (Josh, 04.10.2026). Fehlt das Modul oder gibt es keine Gemeinschaften, bleibt die Seite ohne.
     */
    private function gemeinschaftsKarte(string $sprache): ?string
    {
        $klasse = 'nexus\modules\karte\services\GastKarte';
        if (!class_exists($klasse) || Yii::$app->getModule('nexus-karte') === null) {
            return null;
        }
        $vorher = Yii::$app->language;
        Yii::$app->language = $sprache;
        try {
            $html = $klasse::html();
            if ($html !== null) {
                $tags = $klasse::tags($this->view);
                $this->view->params['nexusTeilen']['zusatzKopf'] = $tags['kopf'];
                $this->view->params['nexusTeilen']['zusatzEnde'] = $tags['ende'];
            }
            return $html;
        } catch (\Throwable $e) {
            Yii::error('nexus-teilen: Karte im Schaufenster fehlgeschlagen: ' . $e->getMessage(), 'nexus-teilen');
            return null;
        } finally {
            Yii::$app->language = $vorher;
        }
    }

    /** @return array<string, string> Sprachfassungen derselben Seite fuer Suchmaschinen (hreflang). */
    private static function sprachfassungen(array $ziel): array
    {
        $liste = [];
        foreach (Module::SPRACHEN as $code) {
            $liste[$code] = Url::to(array_merge($ziel, ['sprache' => $code]), true);
        }
        return $liste;
    }

    /** Fertiges HTML fuer die Oeffentlichkeit -- auch die Admin-Vorschau nutzt genau diesen Weg. */
    public static function oeffentlichesHtml(string $text, ContentActiveRecord $record, Freigabe $freigabe): string
    {
        $html = RichTextToHtmlConverter::process($text, ['record' => $record]);
        return self::namenEntlinken(self::dateienUmleiten($html, $freigabe));
    }

    /** Bild-/Download-Adressen im Text zeigen auf die Anmelde-pflichtige Download-Adresse -- umbiegen. */
    private static function dateienUmleiten(string $html, Freigabe $freigabe): string
    {
        return preg_replace_callback(
            '#(?:https?://[^"\'\s/]+)?/file/file/download\?guid=([a-f0-9-]{36})[^"\'\s]*#i',
            fn(array $m) => Url::to(['/nexus-teilen/lesen/datei', 't' => $freigabe->schluessel, 'guid' => $m[1]]),
            $html
        );
    }

    /** Erwaehnte Mitglieder (@Name) bleiben als Text stehen, aber ohne Link auf ihr Profil. */
    public static function namenEntlinken(string $html): string
    {
        $wir = preg_quote(Yii::$app->request->hostInfo, '#');
        return preg_replace('#<a\b[^>]*href="(?:' . $wir . ')?/(?:u/|user/profile)[^"]*"[^>]*>(.*?)</a>#is', '$1', $html);
    }

    /** @return array<int, array{name: string, url: string, art: string}> */
    private function anhaenge(ContentActiveRecord $record, Freigabe $freigabe): array
    {
        $liste = [];
        $dateien = File::find()->where([
            'object_model' => get_class($record),
            'object_id' => $record->id,
            'show_in_stream' => 1,
        ])->orderBy(['id' => SORT_ASC])->all();
        foreach ($dateien as $datei) {
            $mime = (string)$datei->mime_type;
            $istVideo = Standbild::istVideo($datei);
            $liste[] = [
                'name' => (string)$datei->file_name,
                'url' => Url::to(['/nexus-teilen/lesen/datei', 't' => $freigabe->schluessel, 'guid' => $datei->guid]),
                'art' => str_starts_with($mime, 'image/') && in_array(strtolower($mime), self::DIREKT_ANZEIGBAR, true)
                    ? 'bild' : ($istVideo ? 'video' : 'datei'),
                'rund' => $istVideo && Standbild::istVideonachricht($datei),
                'standbild' => $istVideo ? Url::to(['/nexus-teilen/lesen/standbild', 't' => $freigabe->schluessel, 'guid' => $datei->guid]) : null,
            ];
        }
        return $liste;
    }

    /** Standbild eines Videos dieses Inhalts (siehe Standbild) -- gleiche Pruefung wie actionDatei. */
    public function actionStandbild(string $t = '', string $guid = '')
    {
        [, $record] = $this->freigegeben($t);
        $datei = $record === null ? null : File::findOne([
            'guid' => $guid,
            'object_model' => get_class($record),
            'object_id' => $record->id,
        ]);
        $pfad = $datei === null ? null : Standbild::pfad($datei);
        if ($pfad === null) {
            throw new NotFoundHttpException();
        }
        header_remove('Pragma');
        header_remove('Expires');
        Yii::$app->response->headers->set('Cache-Control', 'public, max-age=86400');
        Yii::$app->response->headers->set('X-Robots-Tag', 'noindex');
        return Yii::$app->response->sendFile($pfad, 'standbild.jpg', ['mimeType' => 'image/jpeg', 'inline' => true]);
    }

    /** Vorschaubild fuer Messenger: erstes Bild im Text oder in den Anhaengen, sonst das Logo. */
    private function erstesBild(string $html, array $anhaenge): string
    {
        if (preg_match('#<img[^>]+src="([^"]+)"#i', $html, $m)) {
            return Url::to(html_entity_decode($m[1]), true);
        }
        foreach ($anhaenge as $anhang) {
            if ($anhang['art'] === 'bild') {
                return Url::to($anhang['url'], true);
            }
        }
        foreach ($anhaenge as $anhang) {
            if ($anhang['standbild'] !== null) {
                return Url::to($anhang['standbild'], true);
            }
        }
        return Url::to(Yii::$app->view->theme->getBaseUrl() . '/resources/img/nexus-logo-voll.jpg', true);
    }
}
