<?php

namespace nexus\modules\wissen\controllers;

use humhub\components\Controller;
use nexus\modules\teilen\services\Texte as TeilenTexte;
use nexus\modules\wissen\models\Artikel;
use nexus\modules\wissen\Module;
use nexus\modules\wissen\services\Bilder;
use nexus\modules\wissen\services\Darstellung;
use nexus\modules\wissen\services\Sammlung;
use Yii;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

/**
 * Oeffentliche Leseseite EINES Wissensartikels -- auch fuer Gaeste, nur ueber die geheime Adresse, die die
 * Redaktion freigegeben hat. Gezeigt wird nur der Text (ohne Namen, Kommentare, Vorschlaege, Kreis und ohne
 * Verweise auf andere Artikel). Rahmen, Fusszeile und Texte kommen vom Modul nexus-teilen (dieselbe Seite
 * wie bei oeffentlichen Beitraegen); ohne dieses Modul gibt es die Seite nicht.
 * Bilder laufen ueber actionBild: die Adresse der Sammlung verlangt eine Anmeldung.
 */
class OeffentlichController extends Controller
{
    public $layout = '@nexus-teilen/views/layouts/oeffentlich';

    public function init()
    {
        parent::init();
        if (!Module::oeffentlichMoeglich()) {
            throw new NotFoundHttpException();
        }
    }

    public function actionIndex(string $t = '', string $sprache = '')
    {
        $this->kopfzeilen();
        $seitenSprache = in_array($sprache, \nexus\modules\teilen\Module::SPRACHEN, true) ? $sprache : TeilenTexte::sprache();
        $t = $this->schluessel($t);
        $gefunden = $this->finden($t);
        if ($gefunden === null) {
            Yii::$app->response->statusCode = 404;
            $this->view->params['nexusTeilen'] = ['sprache' => $seitenSprache, 'titel' => 'N.E.X.U.S.'];
            return $this->render('@nexus-teilen/views/lesen/weg', ['t' => TeilenTexte::alle($seitenSprache), 'sprache' => $seitenSprache]);
        }
        [$artikel, $sammlung] = $gefunden;

        $html = Darstellung::oeffentlich(
            $artikel->inhalt,
            $sammlung,
            fn(string $name) => Url::to(['/nexus-wissen/oeffentlich/bild', 't' => $t, 'd' => $name])
        );
        $this->view->params['nexusTeilen'] = array_merge($this->view->params['nexusTeilen'] ?? [], [
            'sprache' => $seitenSprache,
            'titel' => $artikel->titel,
            'beschreibung' => Darstellung::anriss($artikel->inhalt, 200),
            'bild' => Url::to(Yii::$app->view->theme->getBaseUrl() . '/resources/img/nexus-logo-voll.jpg', true),
            'adresse' => $artikel->oeffentlicheAdresse(),
            'sprachfassungen' => [],
        ]);

        return $this->render('artikel', [
            't' => TeilenTexte::alle($seitenSprache),
            'artikel' => $artikel,
            'sammlungName' => $sammlung->name($seitenSprache),
            'html' => $html,
            'datum' => (string)$artikel->updated_at,
            'imPortal' => Yii::$app->user->isGuest ? null : $artikel->url($sammlung, true),
        ]);
    }

    /** Ein Bild des freigegebenen Artikels -- nur Bilder, die wirklich in diesem Artikel stehen. */
    public function actionBild(string $t = '', string $d = '')
    {
        $this->kopfzeilen();
        $gefunden = $this->finden($this->schluessel($t));
        if ($gefunden === null) {
            throw new NotFoundHttpException();
        }
        [$artikel, $sammlung] = $gefunden;
        $name = basename($d);
        $pfad = $name !== '' && str_contains((string)$artikel->inhalt, $name) ? Bilder::pfad($sammlung, $name) : null;
        if ($pfad === null) {
            throw new NotFoundHttpException();
        }
        $antwort = Yii::$app->response;
        header_remove('Pragma');
        header_remove('Expires');
        $antwort->headers->set('Cache-Control', 'public, max-age=86400');
        $antwort->headers->set('X-Content-Type-Options', 'nosniff');
        return $antwort->sendFile($pfad, $name, [
            'mimeType' => Bilder::MIME[strtolower(pathinfo($name, PATHINFO_EXTENSION))] ?? 'application/octet-stream',
            'inline' => true,
        ]);
    }

    private function schluessel(string $t): string
    {
        return preg_match('/^[a-f0-9]{32}$/', $t) ? $t : '';
    }

    /**
     * @return array{0: Artikel, 1: Sammlung}|null  null = es gibt keine solche Freigabe (oder sie wurde zurueckgenommen,
     *   der Artikel ist wieder Entwurf, oder die Sammlung ist nicht (mehr) freigegeben)
     */
    private function finden(string $t): ?array
    {
        if ($t === '') {
            return null;
        }
        $artikel = Artikel::findOne(['oeffentlich_schluessel' => $t]);
        if ($artikel === null || $artikel->entwurf) {
            return null;
        }
        $sammlung = Module::instanz()?->sammlung((string)$artikel->sammlung);
        return $sammlung !== null && $sammlung->freigegeben() ? [$artikel, $sammlung] : null;
    }

    private function kopfzeilen(): void
    {
        // Nicht in Suchmaschinen: die Freigabe gilt fuer Menschen mit dem Link, nicht fuer das ganze Netz
        Yii::$app->response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        Yii::$app->response->headers->set('Referrer-Policy', 'same-origin');
        $this->view->params['nexusTeilen']['indexieren'] = false;
    }
}
