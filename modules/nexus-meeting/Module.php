<?php

namespace nexus\modules\meeting;

use humhub\modules\user\models\User;
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
 * - Offene Tuer: Eingetragene Menschen (Einstellungen) oeffnen oben in der Leiste ihre "offene Tuer" --
 *   dann sehen alle Mitglieder auf jeder Seite "X ist gerade da" und kommen mit einem Klick ins
 *   Gespraech, wahlweise ueber einen Warteraum. Siehe models/Tuer.php.
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

    /**
     * Wer eine "offene Tuer" oeffnen darf (Josh, 18.10.2026: "Menschen sehen, wenn ich am Rechner bin,
     * und koennen einfach reinkommen"). Liste von Benutzer-IDs in den Einstellungen des Moduls --
     * leer heisst: niemand. Den Hinweis sehen alle angemeldeten Mitglieder.
     * @return int[]
     */
    public function tuerPersonen(): array
    {
        $roh = (string)$this->settings->get('tuer_personen', '');
        return array_values(array_unique(array_filter(array_map('intval', explode(',', $roh)))));
    }

    public function setzeTuerPersonen(array $ids): void
    {
        $this->settings->set('tuer_personen', implode(',', array_unique(array_filter(array_map('intval', $ids)))));
    }

    public function darfTuerOeffnen(?User $mensch): bool
    {
        return $mensch !== null && in_array((int)$mensch->id, $this->tuerPersonen(), true);
    }
}
