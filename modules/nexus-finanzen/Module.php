<?php

namespace nexus\modules\finanzen;

use humhub\modules\user\models\User;
use Yii;

/**
 * Finanzierungs-Ampel (Konzept "Gemeinsam finanzieren, was wir gemeinsam aufbauen", V1.0, 25.10.2026).
 *
 * Drei Toepfe, die nie vermischt werden:
 *  - Grundbetrieb: laufende Kacheln, jeden Monat neu zu decken
 *  - Optionale Dienste: laufend, aber nur wenn gemeinsam getragen (grau, bis freigegeben)
 *  - Entwicklungsfonds: einmalige Kacheln, die Summe der freigegebenen ist gedeckelt
 *
 * Geld kommt als Eingang herein (Ko-fi automatisch ueber nexus-supporter-bridge, alles andere von Hand).
 * Zweckgebunden geht es sofort in seine Kachel; frei kommt es in den Topf. Aus dem Topf verteilt die
 * Monatsverteilung nach Rangfolge -- das System SCHLAEGT VOR, eine finanzverantwortliche Person
 * bestaetigt. Geht dabei Geld an eine Kachel, von der jemand selbst bezahlt wird (Verguetung), muss eine
 * ZWEITE Person freigeben; dasselbe gilt fuer Auszahlungen. Niemand genehmigt die eigene Verguetung allein.
 *
 * Einstellungen liegen in den Moduleinstellungen (Verwaltung → Einstellungen), nicht in common.php.
 */
class Module extends \humhub\components\Module
{
    public function getName()
    {
        return 'N.E.X.U.S. Finanzierungs-Ampel';
    }

    public function getDescription()
    {
        return 'Kostenkacheln mit Ampel, Einnahmen, Monatsverteilung mit Vier-Augen-Freigabe, oeffentliche Zahlen.';
    }

    public static function instanz(): ?self
    {
        $m = Yii::$app->getModule('nexus-finanzen');
        return $m instanceof self ? $m : null;
    }

    /** @return string[] Benutzernamen der Finanzverantwortlichen */
    public function verantwortliche(): array
    {
        return self::liste((string)$this->settings->get('verantwortliche', ''));
    }

    /** @return string[] Webseiten (Origin), die die oeffentlichen Zahlen im Browser abrufen duerfen */
    public function webseiten(): array
    {
        return self::liste((string)$this->settings->get('webseiten', ''));
    }

    /** Deckel des Entwicklungsfonds in Cent (Summe der freigegebenen einmaligen Kacheln) */
    public function entwicklungsDeckel(): int
    {
        return (int)$this->settings->get('entwicklungsDeckel', 0);
    }

    /** Link "Mithelfen" (eigene Foerderseite); leer = kein Link */
    public function mithelfenUrl(): string
    {
        $u = trim((string)$this->settings->get('mithelfenUrl', ''));
        return preg_match('#^https://#i', $u) ? $u : '';
    }

    public function istVerantwortlich(?User $mensch): bool
    {
        return $mensch !== null && in_array(mb_strtolower($mensch->username), array_map('mb_strtolower', $this->verantwortliche()), true);
    }

    /** @return User[] */
    public function verantwortlicheMenschen(): array
    {
        $namen = $this->verantwortliche();
        return $namen === [] ? [] : User::find()->where(['username' => $namen, 'status' => User::STATUS_ENABLED])->all();
    }

    private static function liste(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\s,;]+/', $text))));
    }
}
