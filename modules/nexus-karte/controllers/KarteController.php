<?php

namespace nexus\modules\karte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\wiki\models\WikiPage;
use nexus\modules\karte\models\Suchender;
use nexus\modules\karte\Module;
use nexus\modules\karte\services\Kacheln;
use nexus\modules\karte\services\KartenDaten;
use nexus\modules\karte\services\Texte;
use Throwable;
use Yii;
use yii\helpers\Url;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Die Karte selbst (nur Angemeldete) und ihre Kartenbilder. Die Kartenbilder duerfen auch Gaeste
 * holen -- die Willkommensseite zeigt die Karte (Josh, 30.09.2026).
 */
class KarteController extends Controller
{
    /** Browser duerfen ein Kartenbild eine Woche behalten -- spart Anfragen an uns und an OSM. */
    private const BROWSER_SPEICHER_S = 7 * 24 * 3600;

    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY => ['index']]];
    }

    public function actionIndex()
    {
        return $this->render('index', [
            'punkte' => KartenDaten::punkte(),
            'suchende' => KartenDaten::suchende(),
            'ichSuche' => Suchender::find()->where(['user_id' => (int)Yii::$app->user->id])->exists(),
            'gruendenUrl' => $this->gruendenUrl(),
            'darfVerwalten' => KartenDaten::verwaltbar(Yii::$app->user->getIdentity()) !== []
                || Yii::$app->user->getIdentity()->isSystemAdmin(),
        ]);
    }

    /** Ein Kartenbild, ueber unseren Server geholt (siehe Kacheln). */
    public function actionKachel(int $z, int $x, int $y)
    {
        // Gaeste nur von unseren eigenen Seiten aus -- sonst koennte jede fremde Webseite unseren
        // Server als kostenlose Kartenquelle einbinden. Dazu kommen die eigenen Webseiten aus der
        // Einstellung "webseiten" (zeigen die Karte auf ihrer Startseite); exakter Host-Vergleich.
        $herkunft = parse_url((string)Yii::$app->request->referrer, PHP_URL_HOST);
        $erlaubt = array_merge([Yii::$app->request->hostName], Module::instanz()->webseitenHosts());
        if (Yii::$app->user->isGuest && !in_array($herkunft, $erlaubt, true)) {
            throw new NotFoundHttpException();
        }
        $pfad = Kacheln::datei($z, $x, $y);
        if ($pfad === null) {
            throw new NotFoundHttpException();
        }
        // Die Sitzung setzt "nicht speichern"-Kopfzeilen; fuer ein Kartenbild ist das unnoetig.
        header_remove('Pragma');
        header_remove('Expires');
        $antwort = Yii::$app->response;
        $antwort->headers->set('Cache-Control', 'private, max-age=' . self::BROWSER_SPEICHER_S);
        return $antwort->sendFile($pfad, "$z-$x-$y.png", ['mimeType' => 'image/png', 'inline' => true]);
    }

    /**
     * Datenquelle fuer die Karte auf der eigenen Webseite (Einstellung "webseiten"). Nur, was Gaeste
     * ohnehin sehen: Name, Ort, gerundete Lage -- keine Beschreibung, kein Bild, kein Link zum
     * Kreis, keine "Suchenden". Im Browser abrufen darf sie nur die Webseite (CORS).
     */
    public function actionDaten(string $sprache = 'de')
    {
        if (!Yii::$app->request->isGet) {
            throw new MethodNotAllowedHttpException();
        }
        $antwort = Yii::$app->response;
        $antwort->format = Response::FORMAT_JSON;
        $herkunft = (string)Yii::$app->request->headers->get('Origin');
        if (in_array($herkunft, Module::instanz()->webseiten, true)) {
            $antwort->headers->set('Access-Control-Allow-Origin', $herkunft);
        }
        $antwort->headers->set('Vary', 'Origin');
        header_remove('Pragma');
        header_remove('Expires');
        $antwort->headers->set('Cache-Control', 'public, max-age=600');
        $antwort->headers->set('X-Robots-Tag', 'noindex');

        $sprache = in_array($sprache, ['de', 'en', 'es'], true) ? $sprache : 'de';
        $vorher = Yii::$app->language;
        Yii::$app->language = $sprache;
        try {
            $registrieren = Module::instanz()->registrierenUrl();
            return [
                'titel' => Texte::t('gast_titel'),
                'text' => Texte::t('gast_text'),
                'hinweis' => Texte::t('ort_hinweis'),
                'quelle' => Texte::t('karte_quelle'),
                'kachel' => Url::to(['/nexus-karte/karte/kachel'], true),
                'minZoom' => Kacheln::MIN_ZOOM,
                'maxZoom' => Kacheln::MAX_ZOOM,
                // Die oeffentliche Gast-Startseite zeigt Karte und Schaufenster; die Anleitung zum
                // Gruenden (Wiki-Seite) sehen Gaeste nicht -- deshalb fuehrt "gruenden" zur Registrierung.
                'finden' => ['text' => Texte::t('web_finden'), 'link' => Url::to(['/dashboard/dashboard/index'], true)],
                'gruenden' => ['text' => Texte::t('web_gruenden'), 'link' => $registrieren],
                'gemeinschaften' => KartenDaten::punkteFuerGaeste(),
            ];
        } finally {
            Yii::$app->language = $vorher;
        }
    }

    /** Adresse der Anleitung "Deine Gemeinschaft gruenden"; ohne Wiki-Modul/Seite kein Knopf. */
    private function gruendenUrl(): ?string
    {
        try {
            $id = (int)Module::instanz()->wikiGruenden;
            $seite = $id > 0 && class_exists(WikiPage::class) ? WikiPage::findOne($id) : null;
            return $seite?->getUrl();
        } catch (Throwable $e) {
            Yii::warning('nexus-karte: Anleitung nicht gefunden: ' . $e->getMessage(), 'nexus-karte');
            return null;
        }
    }
}
