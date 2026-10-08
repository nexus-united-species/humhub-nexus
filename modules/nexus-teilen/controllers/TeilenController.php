<?php

namespace nexus\modules\teilen\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\helpers\Html;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\widgets\richtext\converter\RichTextToHtmlConverter;
use humhub\modules\nexusTranslate\SprachErkennung;
use nexus\modules\teilen\models\Freigabe;
use nexus\modules\teilen\services\Inhalt;
use nexus\modules\teilen\services\Texte;
use Yii;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Fuer Angemeldete: Text + Link zum Teilen holen, Freigabe setzen und zuruecknehmen.
 */
class TeilenController extends Controller
{
    /** So viel Text geht mit in den Messenger -- der Rest steht hinter dem Link. */
    private const KURZTEXT_ZEICHEN = 300;

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['freigeben', 'zuruecknehmen', 'schaufenster-an', 'schaufenster-aus']],
        ];
    }

    public function actionInfo(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $record = $this->lesbar($id);
        $leser = class_exists(SprachErkennung::class) ? SprachErkennung::leser() : null;
        $fassung = Inhalt::fassung($record, $leser);
        $freigabe = Freigabe::fuer($id);
        // Geht der Link nach draussen (oeffentlich), dann auch der Text ohne erwaehnte Namen.
        if ($freigabe !== null) {
            $fassung['text'] = Inhalt::anonymisieren($fassung['text'], Texte::get('ein_mitglied'));
        }
        $kurz = Inhalt::kurztext($fassung['text'], self::KURZTEXT_ZEICHEN);
        $text = $fassung['titel'] !== '' ? $fassung['titel'] . "\n\n" . $kurz : $kurz;
        $darf = Inhalt::darfFreigeben($record, Yii::$app->user->getIdentity());

        return [
            'titel' => Inhalt::ueberschrift($fassung['titel'], $fassung['text']),
            'text' => $text,
            // Oeffentlicher Link mit der Sprache, in der der Teilende liest -- der Empfaenger sieht
            // dieselbe Fassung (Rueckmeldung aus dem Team: "nur das Original, nicht die Uebersetzung").
            'link' => $freigabe !== null
                ? $freigabe->adresse($fassung['sprache'])
                : Url::to(['/content/perma', 'id' => $id], true),
            'oeffentlich' => $freigabe !== null,
            // Nicht oeffentlich: erst ein Hinweis, wie man es oeffentlich stellt (Josh, 02.10.2026) --
            // sonst landet ein Link bei Aussenstehenden, der nur die Anmeldeseite zeigt.
            'darf' => $darf,
            'texte' => [
                'intern_titel' => Texte::get('intern_titel'),
                'intern_text' => Texte::get($darf ? 'intern_text' : 'intern_text_fremd'),
                'knopf_oeffentlich' => Texte::get('knopf_oeffentlich'),
                'knopf_mitglieder' => Texte::get('knopf_mitglieder'),
                'frage_an' => Texte::get('frage_an'),
                'kopiert' => Texte::get('kopiert'),
                'fehler' => Texte::get('fehler'),
                'fenster_titel' => Texte::get('fenster_titel'),
                'fenster_teilen' => Texte::get('fenster_teilen'),
                'fenster_kopieren' => Texte::get('fenster_kopieren'),
                'fenster_schliessen' => Texte::get('fenster_schliessen'),
            ],
        ];
    }

    public function actionFreigeben(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $record = $this->freigebbar($id);
        $freigabe = Freigabe::anlegen($id, (int)Yii::$app->user->id);
        Yii::info("nexus-teilen: content#$id oeffentlich freigegeben von user#" . Yii::$app->user->id, 'nexus-teilen');
        return [
            'link' => $freigabe->adresse(Inhalt::sprache($record)),
            'meldung' => Texte::get('ist_an'),
        ];
    }

    public function actionZuruecknehmen(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->freigebbar($id);
        Freigabe::deleteAll(['content_id' => $id]);
        Yii::info("nexus-teilen: Freigabe content#$id aufgehoben von user#" . Yii::$app->user->id, 'nexus-teilen');
        return ['meldung' => Texte::get('ist_aus')];
    }

    /**
     * Admin-Vorschau vor dem Schaufenster: genau so, wie der Inhalt oeffentlich erscheint
     * (anonymisiert), dazu gelb markiert alle Namen von Mitgliedern, die im Text stehen.
     */
    public function actionVorschau(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $record = $this->schaufensterfaehig($id);
        $sprache = Texte::sprache();
        $fassung = Inhalt::fassung($record, null);
        $text = Inhalt::anonymisieren($fassung['text'], Texte::get('ein_mitglied'));
        $html = LesenController::namenEntlinken(RichTextToHtmlConverter::process($text, ['record' => $record]));
        $namen = Inhalt::namenImText(Inhalt::kurztext($text, 100000) . ' ' . $fassung['titel']);
        foreach ($namen as $name) {
            $html = (string)preg_replace(
                '/(?<![\p{L}\p{N}])(' . preg_quote(Html::encode($name), '/') . ')(?![\p{L}\p{N}])(?![^<]*>)/u',
                '<mark class="nexus-teilen-name">$1</mark>',
                $html
            );
        }
        return [
            'titel' => Inhalt::ueberschrift($fassung['titel'], $text),
            'zeigeTitel' => $fassung['titel'] !== '',
            'html' => $html,
            'namen' => $namen,
            'texte' => [
                'titel' => Texte::get('sf_vorschau_titel'),
                'namen' => Texte::get('sf_namen'),
                'keine' => Texte::get('sf_keine_namen'),
                'bilder' => Texte::get('sf_bilder'),
                'stellen' => Texte::get('sf_stellen'),
                'abbrechen' => Texte::get('sf_abbrechen'),
            ],
            'sprache' => $sprache,
        ];
    }

    public function actionSchaufensterAn(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->schaufensterfaehig($id);
        $freigabe = Freigabe::anlegen($id, (int)Yii::$app->user->id);
        $freigabe->insSchaufenster((int)Yii::$app->user->id);
        Yii::info("nexus-teilen: content#$id ins Schaufenster von user#" . Yii::$app->user->id, 'nexus-teilen');
        return [
            'meldung' => Texte::get('sf_drin_meldung'),
            'seite' => Url::to(['/nexus-teilen/lesen/schaufenster'], true),
        ];
    }

    public function actionSchaufensterAus(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->schaufensterfaehig($id);
        Freigabe::fuer($id)?->ausSchaufenster();
        Yii::info("nexus-teilen: content#$id aus dem Schaufenster von user#" . Yii::$app->user->id, 'nexus-teilen');
        return ['meldung' => Texte::get('sf_raus_meldung')];
    }

    private function schaufensterfaehig(int $id): ContentActiveRecord
    {
        $record = $this->lesbar($id);
        if (!Inhalt::darfSchaufenster(Yii::$app->user->getIdentity())) {
            throw new ForbiddenHttpException();
        }
        return $record;
    }

    private function lesbar(int $id): ContentActiveRecord
    {
        $record = Inhalt::ausContentId($id);
        if ($record === null) {
            throw new NotFoundHttpException();
        }
        if (!$record->content->canView()) {
            throw new ForbiddenHttpException();
        }
        return $record;
    }

    private function freigebbar(int $id): ContentActiveRecord
    {
        $record = $this->lesbar($id);
        if (!Inhalt::darfFreigeben($record, Yii::$app->user->getIdentity())) {
            throw new ForbiddenHttpException();
        }
        return $record;
    }
}
