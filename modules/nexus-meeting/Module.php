<?php

namespace nexus\modules\meeting;

use yii\helpers\Url;

/**
 * Laufende Meetings sichtbar machen (Josh, 02.10.2026: "den Link sehen die Blinden wieder nicht").
 *
 * - Hinweis: Laeuft ein Online-Termin aus dem Kalender (10 Minuten vorher bis Ende), zeigt jede
 *   Portalseite einen kleinen Hinweis "Jetzt live" -- nur Mitgliedern des Kreises, zu dem er gehoert.
 * - Kleines Fenster: "Beitreten" oeffnet das kMeet-Treffen in einem schwebenden Fenster, das beim
 *   Weiterklicken im Portal offen bleibt. Am Handy erst klein, beim Beitreten gross (Josh).
 * - Abschaltbar (Verwaltung -> Module -> Konfigurieren): ohne Fenster fuehrt "Beitreten" wie bisher
 *   auf die Meeting-Seite des Jitsi-Moduls -- dann aber als volles Neuladen der Seite.
 *
 * Nebenbei behoben: Das Jitsi-Modul laedt das kMeet-Programm (external_api.js) erst mit dem
 * Meeting-Fenster und startet das Treffen oft, bevor es da ist -- bei langsamem Handynetz kam dann
 * "manchmal ein Fehler" (Rueckmeldung aus dem Team, 02.10.2026). Beide Wege laden es jetzt vollstaendig vorher.
 */
class Module extends \humhub\components\Module
{
    public const VORLAUF_MINUTEN = 10;

    public function getName()
    {
        return 'N.E.X.U.S. Meetings live';
    }

    public function getDescription()
    {
        return 'Hinweis auf laufende Online-Treffen und kleines Meeting-Fenster (abschaltbar).';
    }

    public function getConfigUrl()
    {
        return Url::to(['/nexus-meeting/config/index']);
    }

    public function fensterAn(): bool
    {
        return (string)$this->settings->get('fenster', '1') === '1';
    }
}
