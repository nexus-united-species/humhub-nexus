# nexus-arbeitszeit

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Ehrenamtliche Arbeitsstunden:** Mitglieder tragen ihre Stunden ein – über die Seite „Meine Arbeitsstunden“ (Profil-Menü) oder einfach per Nachricht an den Assistenten („heute 2 Stunden am Newsletter gearbeitet“). Jeder Eintrag gehört zu einem Kreis und hat eine Beschreibung. Systemadmins geben frei oder bitten um Rücksprache; die Person bekommt jeweils eine Nachricht. Export als Excel-taugliche CSV. Optional: ein Excel-Bericht, der bei jeder Änderung in eine Nextcloud hochgeladen wird.

Das Modul stellt außerdem den gemeinsamen Nachrichtenversand (`services/Benachrichtigung.php`) und die Admin-Liste (`ZeitService::admins()`) bereit, die `nexus-hilfe`, `nexus-mitgliedsanfrage` und `nexus-community-assistant` mitbenutzen.

**Voraussetzungen:** HumHub 1.18+. Optional: `nexus-community-assistant` (Eintragen per Nachricht; der Absender ist das Bot-Konto `ASSISTANT_USER_ID`), eine Nextcloud mit technischem Konto.

**Einstellungen** (optional, nur für den Nextcloud-Bericht):

```php
// protected/config/common.php
'modules' => [
    'nexus-arbeitszeit' => [
        'nextcloudUrl' => 'https://cloud.example.org',
        'nextcloudOrdner' => 'Arbeitsstunden',      // Ordner aus Sicht des technischen Kontos
        'nextcloudDatei' => 'Arbeitsstunden.xlsx',
    ],
],
```

```bash
php yii settings/set nexus-arbeitszeit nextcloudBenutzer "<konto>"
php yii settings/set nexus-arbeitszeit nextcloudPasswort "<app-passwort>"
```

## English

**Volunteer working hours:** members log their hours – via the page "My working hours" (profile menu) or simply by messaging the assistant ("worked 2 hours on the newsletter today"). Every entry belongs to a circle and has a description. System admins approve or ask for clarification; the person gets a message each time. Export as an Excel-compatible CSV. Optional: an Excel report uploaded to a Nextcloud after every change.

The module also provides the shared message sending (`services/Benachrichtigung.php`) and the admin list (`ZeitService::admins()`) used by `nexus-hilfe`, `nexus-mitgliedsanfrage` and `nexus-community-assistant`.

**Requirements:** HumHub 1.18+. Optional: `nexus-community-assistant` (logging via message; sender is the bot account `ASSISTANT_USER_ID`), a Nextcloud with a technical account.

**Settings** (optional, only for the Nextcloud report): `nextcloudUrl`, `nextcloudOrdner`, `nextcloudDatei` in `protected/config/common.php`; account and app password as module settings `nextcloudBenutzer` / `nextcloudPasswort` (never in code).
