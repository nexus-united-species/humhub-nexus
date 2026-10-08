<?php

namespace nexus\modules\hilfe\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\hilfe\services\Support;
use nexus\modules\hilfe\services\Texte;
use Throwable;
use Yii;
use yii\web\UploadedFile;

/**
 * Hilfe-Seite und Anfrage-Formular -- bewusst auch fuer Gaeste: Wer nicht ins Portal kommt
 * (Passwort, Registrierung), muss trotzdem Hilfe bekommen. Gegen Spam: verstecktes Lockfeld
 * und hoechstens 3 Anfragen je Stunde und Internetadresse (angemeldet: 10).
 */
class HilfeController extends Controller
{
    private const MAX_GAST_STUNDE = 3;
    private const MAX_MITGLIED_STUNDE = 10;
    private const MIN_ZEICHEN = 10;

    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_POST => ['senden']]];
    }

    public function actionIndex()
    {
        return $this->render('index', $this->seitenDaten());
    }

    public function actionSenden()
    {
        $t = Texte::alle();
        $gast = Yii::$app->user->isGuest;
        $anfrage = Yii::$app->request;
        $daten = [
            'thema' => (string)$anfrage->post('thema', 'sonstiges'),
            'text' => trim((string)$anfrage->post('text', '')),
            'name' => trim((string)$anfrage->post('name', '')),
            'email' => trim((string)$anfrage->post('email', '')),
        ];
        $bild = UploadedFile::getInstanceByName('bild');

        // Lockfeld: Menschen sehen es nicht und lassen es leer, einfache Spam-Programme fuellen es aus.
        if ((string)$anfrage->post('website', '') !== '') {
            return $this->render('danke', ['t' => $t, 'nummer' => 0, 'gast' => $gast]);
        }
        $fehler = null;
        if (mb_strlen($daten['text']) < self::MIN_ZEICHEN) {
            $fehler = $t['fehler_text'];
        } elseif ($gast && ($daten['name'] === '' || !filter_var($daten['email'], FILTER_VALIDATE_EMAIL))) {
            $fehler = $t['fehler_email'];
        } elseif (!Support::bildGueltig($bild)) {
            $fehler = $t['fehler_bild'];
        } elseif (!$this->unterGrenze($gast)) {
            $fehler = $t['fehler_viele'];
        }
        if ($fehler !== null) {
            return $this->render('index', array_merge($this->seitenDaten(), ['fehler' => $fehler, 'eingabe' => $daten]));
        }

        try {
            $mensch = $gast ? null : Yii::$app->user->getIdentity();
            $nummer = Support::anlegen(
                $mensch,
                $daten['thema'] === 'nova' ? 'sonstiges' : $daten['thema'],
                mb_substr($daten['text'], 0, 5000),
                $gast ? 'gast' : 'formular',
                true,
                $daten['name'],
                $daten['email'],
                $bild
            );
        } catch (Throwable $e) {
            Yii::error('nexus-hilfe: Anfrage nicht angelegt: ' . $e->getMessage(), 'nexus-hilfe');
            return $this->render('index', array_merge($this->seitenDaten(), ['fehler' => $t['fehler_allgemein'], 'eingabe' => $daten]));
        }
        return $this->render('danke', ['t' => $t, 'nummer' => $nummer, 'gast' => $gast]);
    }

    /** Zaehlt Anfragen je Stunde (Gaeste je Internetadresse, Mitglieder je Konto). */
    private function unterGrenze(bool $gast): bool
    {
        $schluessel = 'nexus-hilfe-' . ($gast ? 'ip-' . Yii::$app->request->userIP : 'user-' . Yii::$app->user->id);
        $anzahl = (int)Yii::$app->cache->get($schluessel);
        if ($anzahl >= ($gast ? self::MAX_GAST_STUNDE : self::MAX_MITGLIED_STUNDE)) {
            return false;
        }
        Yii::$app->cache->set($schluessel, $anzahl + 1, 3600);
        return true;
    }

    /** @return array<string, mixed> */
    private function seitenDaten(): array
    {
        $gast = Yii::$app->user->isGuest;
        $anleitungen = [];
        $novaLink = null;
        if (!$gast) {
            $kreisId = (int)$this->module->anleitungenKreis;
            $kreis = $kreisId > 0 ? Space::findOne(['id' => $kreisId]) : null;
            $klasse = 'humhub\modules\wiki\models\WikiPage';
            if ($kreis !== null && class_exists($klasse)) {
                try {
                    foreach ($klasse::find()->contentContainer($kreis)->readable()->all() as $seite) {
                        $anleitungen[] = ['titel' => (string)$seite->title, 'link' => $seite->getUrl()];
                    }
                } catch (Throwable $e) {
                    Yii::warning('nexus-hilfe: Anleitungen nicht lesbar: ' . $e->getMessage(), 'nexus-hilfe');
                }
            }
            $nova = User::findOne(['id' => (int)getenv('ASSISTANT_USER_ID')]);
            if ($nova !== null) {
                $novaLink = ['/mail/mail/create', 'userGuid' => $nova->guid];
            }
        }
        return [
            't' => Texte::alle(),
            'gast' => $gast,
            'anleitungen' => $anleitungen,
            'novaLink' => $novaLink,
            'fehler' => null,
            'eingabe' => ['thema' => 'frage', 'text' => '', 'name' => '', 'email' => ''],
        ];
    }
}
