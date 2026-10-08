<?php

namespace humhub\modules\nexusVoice\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use Yii;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Mitschrift einer Sprachnachricht (Josh, 26.09.2026). Nimmt die Aufnahme vom Browser
 * entgegen und reicht sie an den internen Dienst "nexus-whisper" weiter (eigener
 * Container im Portal-Netz, nicht aus dem Internet erreichbar, siehe
 * extras/nexus-whisper/, Adresse = Einstellung "mitschriftUrl"). Die Aufnahme verlaesst den
 * Server nicht und wird hier nicht gespeichert -- gespeichert wird sie nur als normaler
 * Anhang, wenn der Mensch sendet.
 */
class MitschriftController extends Controller
{
    private const MAX_BYTES = 15 * 1024 * 1024;

    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY => ['index']]];
    }

    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (!Yii::$app->request->isPost) {
            throw new HttpException(405, 'Nur POST.');
        }
        $datei = UploadedFile::getInstanceByName('audio');
        if ($datei === null || $datei->error !== UPLOAD_ERR_OK || $datei->size <= 0 || $datei->size > self::MAX_BYTES) {
            throw new HttpException(400, 'Keine gueltige Aufnahme.');
        }
        $modul = Yii::$app->getModule('nexus-voice');
        $token = (string)$modul->settings->get('whisperToken');
        if ($token === '' || (string)$modul->mitschriftUrl === '') {
            throw new HttpException(503, 'Mitschrift ist nicht eingerichtet.');
        }

        $ch = curl_init($modul->mitschriftUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/octet-stream', 'X-Token: ' . $token],
            CURLOPT_POSTFIELDS => file_get_contents($datei->tempName),
            // 5 Minuten Sprache brauchen auf 2 Kernen ~2-3 Minuten; wartet zusaetzlich eine
            // andere Aufnahme (der Dienst arbeitet eine nach der anderen ab), dauert es laenger.
            CURLOPT_TIMEOUT => 420,
        ]);
        $antwort = curl_exec($ch);
        $fehler = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($fehler || $code !== 200) {
            Yii::error('Mitschrift fehlgeschlagen: ' . ($fehler ?: "HTTP $code " . mb_substr((string)$antwort, 0, 200)), 'nexus-voice');
            throw new HttpException(502, 'Mitschrift gerade nicht moeglich.');
        }
        $daten = json_decode((string)$antwort, true) ?: [];
        return [
            'text' => (string)($daten['text'] ?? ''),
            'sprache' => (string)($daten['sprache'] ?? ''),
        ];
    }
}
