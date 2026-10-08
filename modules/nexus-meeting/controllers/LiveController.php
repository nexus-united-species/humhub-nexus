<?php

namespace nexus\modules\meeting\controllers;

use DateInterval;
use DateTime;
use DateTimeZone;
use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\calendar\Module as CalendarModule;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryQuery;
use humhub\modules\space\models\Space;
use nexus\modules\meeting\Module;
use Throwable;
use Yii;
use yii\web\Response;

/**
 * Welche Online-Treffen laufen gerade? Fuer das Skript auf jeder Portalseite (alle 60 s).
 * Nur Termine in Kreisen, in denen der Mensch Mitglied ist -- sonst saehe jeder jedes Treffen.
 */
class LiveController extends Controller
{
    /** Ein laufender Termin wird so lange gesucht: lange Treffen und Vorlauf eingeschlossen. */
    private const SUCHFENSTER_STUNDEN = 12;

    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var Module $modul */
        $modul = $this->module;
        $jitsi = Yii::$app->getModule('jitsi-meet');
        $einstellung = $jitsi ? $jitsi->getSettingsForm() : null;

        return [
            'fenster' => $modul->fensterAn(),
            'domain' => $einstellung ? (string)$einstellung->jitsiDomain : '',
            'praefix' => $einstellung ? (string)$einstellung->roomPrefix : '',
            'name' => (string)Yii::$app->user->getIdentity()->displayName,
            'meetings' => $this->laufende(),
        ];
    }

    /** @return array<int, array{id: string, titel: string, kreis: string, bis: string, raum: string, link: string}> */
    private function laufende(): array
    {
        if (Yii::$app->getModule('calendar') === null || !class_exists(CalendarEntryQuery::class)) {
            return [];
        }
        // Das Kalender-Modul bringt eine eigene Bibliothek fuer Wiederholungen mit und laedt sie nur bei
        // Bedarf -- ohne diesen Aufruf bricht die Abfrage wiederkehrender Termine ab.
        CalendarModule::registerAutoloader();
        $jetzt = new DateTime('now', new DateTimeZone('UTC'));
        $vorlauf = (clone $jetzt)->add(new DateInterval('PT' . Module::VORLAUF_MINUTEN . 'M'));
        $von = (clone $jetzt)->sub(new DateInterval('PT' . self::SUCHFENSTER_STUNDEN . 'H'));
        $ich = (int)Yii::$app->user->id;
        $liste = [];
        try {
            $eintraege = CalendarEntryQuery::findForFilter($von, $vorlauf, null, [], null, true);
        } catch (Throwable $e) {
            Yii::error('nexus-meeting: Kalender nicht lesbar: ' . $e->getMessage(), 'nexus-meeting');
            return [];
        }
        foreach ($eintraege as $e) {
            if (!($e instanceof CalendarEntry) || !$e->online) {
                continue;
            }
            $start = $e->getStartDateTime();
            $ende = $e->getEndDateTime();
            if ($start > $vorlauf || $ende < $jetzt) {
                continue;
            }
            $kreis = $e->content->container ?? null;
            if (!($kreis instanceof Space) || !$kreis->isMember($ich)) {
                continue;
            }
            $link = self::meetingLink((string)$e->location, (string)$e->description);
            $liste[] = [
                'id' => $e->id . '-' . $start->format('YmdHi'),
                'titel' => (string)$e->title,
                'kreis' => (string)$kreis->name,
                'bis' => $ende->format(DATE_ATOM),
                'raum' => self::raumAusLink($link),
                'link' => $link,
            ];
        }
        return $liste;
    }

    /**
     * Wo steht der Meeting-Link? Meist im Feld "Ort" (bei Online-Terminen der Teilnahme-Link), manchmal
     * aber nur in der Beschreibung -- Josh' Testtermin am 02.10.2026: Ort leer, Link im Text. Dann war
     * kein "Beitreten"-Knopf da. Erst der Ort, dann der erste passende Link in der Beschreibung.
     */
    private static function meetingLink(string $ort, string $beschreibung): string
    {
        if (preg_match('#^https?://\S+$#', trim($ort))) {
            return trim($ort);
        }
        if (preg_match('#https?://[^\s)\]<>"]*(?:/conference/|/jitsi-meet/room/|kmeet\.infomaniak\.com/)[^\s)\]<>"]*#i', $beschreibung, $treffer)) {
            return rtrim($treffer[0], '.,;:!?*');
        }
        return '';
    }

    /**
     * Raumname aus dem Online-Link. Drei Schreibweisen: Kurzadresse des Jitsi-Moduls
     * (".../conference/Kreis0Dienstagstreffen" -- so stehen sie in unseren Terminen),
     * lange Adresse (".../jitsi-meet/room/open?name=X") oder direkt kMeet.
     */
    private static function raumAusLink(string $link): string
    {
        $teile = parse_url($link);
        parse_str($teile['query'] ?? '', $abfrage);
        $pfad = (string)($teile['path'] ?? '');
        $name = '';
        if (preg_match('#^/conference/([^/]+)#', $pfad, $treffer)) {
            $name = rawurldecode($treffer[1]);
        } elseif (str_contains($pfad, '/jitsi-meet/room/')) {
            $name = (string)($abfrage['name'] ?? '');
        } elseif (str_contains($teile['host'] ?? '', 'kmeet.infomaniak.com')) {
            $name = trim((string)($teile['path'] ?? ''), '/');
            // Direkter kMeet-Link traegt das Praefix schon -- dann ohne zweites Praefix verwenden.
            return $name === '' ? '' : 'direkt:' . preg_replace('/[^A-Za-z0-9]/', '', $name);
        }
        // Gleiche Bereinigung wie das Jitsi-Modul (RoomController::fixRoomName), sonst landet man im falschen Raum.
        return preg_replace('/[^A-Za-z0-9]/', '', ucwords($name));
    }
}
