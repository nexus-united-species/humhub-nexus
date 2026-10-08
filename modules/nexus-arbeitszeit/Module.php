<?php

namespace nexus\modules\arbeitszeit;

/**
 * Arbeitsstunden fuer N.E.X.U.S. (Josh, 28.09.2026) -- Nachfolger der /logtime-Funktion des
 * Telegram-Bots (Gruppe inaktiv). Vorgaben: Freigabe durch JEDEN mit Admin-Rolle (Systemadmins),
 * jeder Eintrag einem Kreis zugeordnet + Beschreibung, Excel-Export statt Google-Tabelle,
 * "moeglichst benutzerfreundlich und leicht verstaendlich".
 *
 * Zwei Wege zum Eintragen: Seite "Meine Arbeitsstunden" (Profil-Menue) oder eine ganz normale
 * Nachricht an den Assistenten ("heute 2 Stunden am Newsletter gearbeitet") -- ohne Befehle und
 * ohne "|"-Zeichen, das bei Telegram viele ueberfordert hat.
 */
class Module extends \humhub\components\Module
{
    /*
     * Optionaler Excel-Bericht in einer Nextcloud. Einstellung in protected/config/common.php:
     *   'modules' => ['nexus-arbeitszeit' => ['nextcloudUrl' => 'https://cloud.example.org', ...]]
     * Benutzer und App-Passwort stehen als Moduleinstellungen nextcloudBenutzer / nextcloudPasswort
     * in der Datenbank, nie im Code. Ohne nextcloudUrl gibt es keinen Bericht.
     */

    /** @var string|null Adresse der Nextcloud, z. B. "https://cloud.example.org". */
    public $nextcloudUrl = null;

    /** @var string Ordner in der Nextcloud (aus Sicht des technischen Kontos). */
    public $nextcloudOrdner = 'Arbeitsstunden';

    /** @var string Dateiname des Berichts. */
    public $nextcloudDatei = 'Arbeitsstunden.xlsx';

    public function getName()
    {
        return 'N.E.X.U.S. Arbeitsstunden';
    }

    public function getDescription()
    {
        return 'Arbeitsstunden eintragen, freigeben und als Excel exportieren.';
    }
}
