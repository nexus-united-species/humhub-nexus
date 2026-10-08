<?php

namespace humhub\modules\nexusTranslate\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use Yii;
use yii\web\BadRequestHttpException;

/**
 * Sprachumschalter DE / EN / ES oben in der Kopfzeile (Josh, 25.09.2026).
 *
 * HumHub hat dafuer nur das Einstellungen-Formular, das neue Mitglieder
 * nicht finden (Tania musste suchen). Ein Klick setzt die Sprache des
 * eigenen Kontos und fuehrt zurueck auf dieselbe Seite.
 */
class SpracheController extends Controller
{
    public const ERLAUBT = ['de' => 'DE', 'en-US' => 'EN', 'es' => 'ES'];

    private const MELDUNG = [
        'de' => 'Sprache auf Deutsch umgestellt.',
        'en' => 'Language switched to English.',
        'es' => 'Idioma cambiado a español.',
    ];
    /** Hinweis in der bisherigen Sprache; %s = Kuerzel des Knopfs, der zurueckfuehrt. */
    private const ZURUECK = [
        'de' => 'Versehentlich? Oben auf %s klicken, dann ist wieder Deutsch eingestellt.',
        'en' => 'By mistake? Click %s at the top to switch back to English.',
        'es' => '¿Por error? Haz clic arriba en %s para volver al español.',
    ];

    private static function kurz(string $code): string
    {
        return strtolower(explode('-', $code)[0]);
    }

    /** "en" -> "en-US" usw., passend zu den Schluesseln von ERLAUBT. */
    private static function langCode(string $code): string
    {
        foreach (array_keys(self::ERLAUBT) as $erlaubt) {
            if (self::kurz($erlaubt) === self::kurz($code)) {
                return $erlaubt;
            }
        }
        return 'de';
    }

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY => ['setzen']],
            [ControllerAccess::RULE_POST => ['setzen']],
        ];
    }

    public function actionSetzen()
    {
        $sprache = (string)Yii::$app->request->post('sprache');
        if (!isset(self::ERLAUBT[$sprache]) || !array_key_exists($sprache, Yii::$app->i18n->getAllowedLanguages())) {
            throw new BadRequestHttpException('Unbekannte Sprache.');
        }
        $benutzer = Yii::$app->user->getIdentity();
        $vorher = (string)$benutzer->language;
        $benutzer->updateAttributes(['language' => $sprache]);

        // Kurze Meldung nach dem Umstellen (07.10.2026: ein Mitglied landete versehentlich auf Spanisch
        // und dachte, er haette nichts gemacht). In der NEUEN Sprache und in der BISHERIGEN -- wer sich
        // verklickt hat, versteht die neue Sprache womoeglich nicht.
        $meldung = self::MELDUNG[self::kurz($sprache)];
        if ($vorher !== '' && self::kurz($vorher) !== self::kurz($sprache) && isset(self::ZURUECK[self::kurz($vorher)])) {
            $meldung .= ' · ' . sprintf(self::ZURUECK[self::kurz($vorher)], self::ERLAUBT[self::langCode($vorher)] ?? 'DE');
        }
        $this->view->success($meldung);

        // Zurueck auf dieselbe Seite -- aber nur innerhalb dieser Plattform.
        $zurueck = (string)Yii::$app->request->referrer;
        if (!str_starts_with($zurueck, Yii::$app->request->hostInfo . '/')) {
            $zurueck = Yii::$app->homeUrl;
        }
        return $this->redirect($zurueck);
    }
}
