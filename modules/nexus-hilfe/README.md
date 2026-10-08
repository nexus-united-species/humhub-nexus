# nexus-hilfe

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Hilfe & Support:** Eine Hilfe-Seite (`/nexus-hilfe/hilfe`) mit den Anleitungen der Gemeinschaft, einem Weg zum KI-Assistenten und einem Anfrage-Formular – **auch ohne Anmeldung**, für alle, die gar nicht erst ins Portal kommen. Jede Anfrage wird ein nummerierter Beitrag in einem privaten Support-Kreis; das Team bekommt eine Nachricht, der Mensch eine Eingangsbestätigung („Antwort innerhalb von 24 Stunden“). Schutz gegen Spam: verstecktes Lockfeld, höchstens 3 Anfragen je Stunde und Adresse (angemeldet 10), Bild-Anhang nur JPEG/PNG/WebP/GIF bis zur Größengrenze.

**Voraussetzungen:** HumHub 1.18+, **`nexus-arbeitszeit`** (verschickt die Nachrichten), ein privater Kreis für das Support-Team, ein Bot-Konto (Umgebungsvariable `ASSISTANT_USER_ID`), unter dem die Anfragen gepostet werden. Optional: Wiki-Modul (Anleitungen), `nexus-community-assistant`.

**Einstellungen:**

```bash
php yii settings/set nexus-hilfe kreis 22   # Space-ID des Support-Kreises (Pflicht)
```

```php
// protected/config/common.php -- optional
'modules' => ['nexus-hilfe' => ['anleitungenKreis' => 2]],  // Kreis, dessen Wiki-Seiten als Anleitungen erscheinen
```

**Hinweis:** Die Texte (`services/Texte.php`, DE/EN/ES) nennen den Assistenten „Nova“ – für eine andere Gemeinschaft dort anpassen.

## English

**Help & support:** a help page (`/nexus-hilfe/hilfe`) with the community's guides, a way to the AI assistant and a request form – **also without login**, for everyone who cannot get into the portal. Each request becomes a numbered post in a private support circle; the team gets a message and the person a confirmation ("reply within 24 hours"). Spam protection: hidden honeypot field, at most 3 requests per hour and IP (logged in: 10), image attachments only JPEG/PNG/WebP/GIF up to a size limit.

**Requirements:** HumHub 1.18+, **`nexus-arbeitszeit`** (sends the messages), a private circle for the support team, a bot account (environment variable `ASSISTANT_USER_ID`) that posts the requests. Optional: wiki module (guides), `nexus-community-assistant`.

**Settings:** module setting `kreis` (space ID of the support circle, required) and optionally `anleitungenKreis` in `protected/config/common.php` – see above.

**Note:** the texts (`services/Texte.php`, DE/EN/ES) call the assistant "Nova" – adapt them for your community.
