<?php

namespace nexus\modules\mitgliedsanfrage\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\modules\user\services\LinkRegistrationService;
use nexus\modules\mitgliedsanfrage\Module;
use nexus\modules\mitgliedsanfrage\Texte;
use Throwable;
use Yii;
use yii\helpers\Url;
use yii\validators\EmailValidator;

/**
 * Einladungslink eines Kreises ("/user/registration/by-link?token=..&spaceId=.." leitet hierher).
 *
 * HumHub merkt sich die Einladung nur in der Browser-Sitzung. Wer die Bestaetigungs-E-Mail von
 * Authentik auf einem anderen Geraet oeffnet (z. B. Link am PC, E-Mail am Handy), landete nie im
 * Kreis. Deshalb fragen wir hier zuerst die E-Mail-Adresse und legen HumHubs eigene Einladung fuer
 * diese Adresse an (Invite mit space_invite_id). Entsteht spaeter ein Konto mit dieser Adresse --
 * auf welchem Geraet auch immer --, nimmt HumHub den Menschen selbst in den Kreis auf
 * (User::setUpApproved). Es wird dabei keine E-Mail von HumHub verschickt.
 */
class EinladungController extends Controller
{
    /** Gaeste duerfen hierher, wie bei HumHubs eigener Registrierung */
    public $access = ControllerAccess::class;

    public $layout = '@humhub/modules/user/views/layouts/main';

    private const MAX_VERSUCHE = 10;
    private const ZAEHLER = 'nexus-einladung-versuche';

    /** @var Module */
    public $module;

    public function actionIndex(?string $token = null, $spaceId = null)
    {
        $dienst = new LinkRegistrationService($token, Space::findOne(['id' => (int)$spaceId]));
        $kreis = $dienst->getSpace();
        if ($token === null || $kreis === null || !$dienst->isEnabled() || !$dienst->isValid()) {
            return $this->render('meldung', ['titel' => Texte::t('titel'), 'text' => Texte::t('ungueltig')]);
        }
        // Gleiches Geraet: HumHubs eigener Weg nimmt die Einladung nach der Anmeldung ebenfalls mit
        $dienst->storeInSession();
        $anmelden = Url::to(['/user/auth/external', 'authclient' => 'authentik', 'rememberMe' => 1]);

        $email = trim((string)Yii::$app->request->post('email', ''));
        $fehler = null;
        if (Yii::$app->request->isPost) {
            $versuche = (int)Yii::$app->session->get(self::ZAEHLER, 0) + 1;
            Yii::$app->session->set(self::ZAEHLER, $versuche);
            if ($versuche > self::MAX_VERSUCHE) {
                $fehler = Texte::t('zu_oft');
            } elseif (!(new EmailValidator())->validate($email)) {
                $fehler = Texte::t('email_falsch');
            } elseif (User::find()->where(['email' => $email])->exists()) {
                return $this->render('meldung', [
                    'titel' => Texte::t('titel'),
                    'text' => Texte::t('konto_da', ['{kreis}' => $kreis->name]),
                    'knopf' => Texte::t('anmelden'),
                    'ziel' => $anmelden,
                ]);
            } else {
                try {
                    $dienst->convertToInvite($email);
                } catch (Throwable $e) {
                    Yii::error('nexus-mitgliedsanfrage: Einladung fuer Kreis ' . $kreis->id . ' nicht angelegt: ' . $e->getMessage(), 'nexus-mitgliedsanfrage');
                }
                return $this->render('bereit', ['email' => $email, 'ziel' => $this->module->registrierenUrl ?: $anmelden]);
            }
        }

        return $this->render('index', ['kreis' => $kreis, 'email' => $email, 'fehler' => $fehler, 'anmelden' => $anmelden]);
    }
}
