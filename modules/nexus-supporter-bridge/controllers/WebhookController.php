<?php

namespace nexus\modules\supporterBridge\controllers;

use nexus\modules\supporterBridge\services\KofiWebhookService;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Oeffentlicher Endpunkt, den Ko-fi anspricht. Bewusst ohne
 * Anmeldepflicht -- die Absicherung laeuft ausschliesslich ueber den
 * verification_token, den nur Ko-fi und wir kennen.
 *
 * Bewusst yii\web\Controller statt humhub\components\Controller: Ko-fi ist kein angemeldeter
 * Mensch. HumHubs Zugriffsregeln wuerden den Aufruf bei abgeschaltetem Gastzugang auf die
 * Anmeldeseite umleiten, und Ko-fi kann kein CSRF-Token mitsenden. Geschuetzt wird hier durch
 * POST-Pflicht, Mengenbegrenzung je Adresse und den geheimen Pruefschluessel (hash_equals).
 */
class WebhookController extends Controller
{
    public $enableCsrfValidation = false;

    /** Hoechstens so viele Anfragen je IP innerhalb von RATE_LIMIT_FENSTER
     *  Sekunden -- ein Webhook-Endpunkt braucht keine hohe Frequenz, das
     *  hier ist nur ein grober Schutz gegen Missbrauch/Beschuss. */
    private const RATE_LIMIT_MAX = 20;
    private const RATE_LIMIT_FENSTER = 60;

    public function actionReceive(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 405;
            return $this->asJson(['ok' => false, 'error' => 'method not allowed']);
        }

        if (!$this->rateLimitOk()) {
            Yii::$app->response->statusCode = 429;
            return $this->asJson(['ok' => false, 'error' => 'rate limit']);
        }

        // Ko-fi sendet application/x-www-form-urlencoded mit einem
        // Feld "data", dessen Wert ein JSON-String ist -- kein roher
        // JSON-Request-Body.
        $roh = Yii::$app->request->post('data');
        $payload = is_string($roh) ? json_decode($roh, true) : null;

        if (!is_array($payload)) {
            Yii::$app->response->statusCode = 400;
            return $this->asJson(['ok' => false, 'error' => 'invalid payload']);
        }

        $erwarteterToken = getenv('KO_FI_VERIFICATION_TOKEN');
        $gesendeterToken = $payload['verification_token'] ?? '';

        if (empty($erwarteterToken) || !hash_equals($erwarteterToken, (string)$gesendeterToken)) {
            Yii::$app->response->statusCode = 403;
            return $this->asJson(['ok' => false, 'error' => 'invalid token']);
        }

        $spaceId = (int)getenv('SUPPORTER_SPACE_ID');
        if ($spaceId <= 0) {
            Yii::error('nexus-supporter-bridge: SUPPORTER_SPACE_ID nicht konfiguriert');
            Yii::$app->response->statusCode = 500;
            return $this->asJson(['ok' => false, 'error' => 'server misconfigured']);
        }

        // Mindestbetrag steht fest im Service (jede monatliche Unterstuetzung, Josh 28.09.2026).
        $service = new KofiWebhookService($spaceId);
        $ergebnis = $service->verarbeiten($payload);

        Yii::$app->response->statusCode = $ergebnis['status'];
        return $this->asJson(['ok' => $ergebnis['status'] === 200, 'message' => $ergebnis['message']]);
    }

    private function rateLimitOk(): bool
    {
        $ip = Yii::$app->request->userIP ?? 'unbekannt';
        $schluessel = 'nexus-supporter-bridge-ratelimit-' . $ip;
        $zaehler = (int)Yii::$app->cache->get($schluessel);

        if ($zaehler >= self::RATE_LIMIT_MAX) {
            return false;
        }

        Yii::$app->cache->set($schluessel, $zaehler + 1, self::RATE_LIMIT_FENSTER);
        return true;
    }
}
