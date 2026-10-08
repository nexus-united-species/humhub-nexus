# nexus-teilen

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Teilen und öffentliche Lese-Links.**

1. **„Weitergeben“** unter Beiträgen und Wiki-Seiten: am Handy das Teilen-Menü des Geräts (Signal, WhatsApp, E-Mail …), am PC „Link kopieren“. Der mitgeschickte Text steht in der Sprache des Teilenden.
2. **„Öffentlich lesbar machen“:** Verfasser oder Admins geben einen einzelnen Beitrag oder eine Wiki-Seite frei. Er ist dann unter einem eigenen Link ohne Anmeldung lesbar – ohne Namen des Verfassers, ohne Kommentare, ohne Kreis. Erwähnte Mitglieder werden zu „ein Mitglied“. Jederzeit zurücknehmbar.
3. **Schaufenster:** Admins stellen freigegebene Inhalte in eine öffentliche Seite (für Suchmaschinen sichtbar), dazu ein JSON-Feed für die eigene Webseite.

Angehängte Dateien zeigt die Leseseite nur bei Bildern, Video, Audio und PDF direkt an; alles andere wird heruntergeladen.

**Voraussetzungen:** HumHub 1.18+. Optional: `nexus-translate` (übersetzte Fassungen), `nexus-karte` (Karte im Schaufenster), ffmpeg (Standbilder für Videos).

**Einstellungen** (`protected/config/common.php`, alle optional):

```php
'modules' => [
    'nexus-teilen' => [
        'webseiten' => ['https://example.org'],          // dürfen den Schaufenster-Feed im Browser abrufen
        'registrierenUrl' => 'https://example.org/join', // Knopf "Mitmachen" (Standard: Anmeldeseite)
        'anmeldenUrl' => '/user/auth/login',             // Knopf "Schon Mitglied? Anmelden"
        'fussLinks' => [                                  // Fußzeile je Sprache (fehlt eine: Deutsch)
            'de' => ['Impressum' => 'https://example.org/impressum', 'Datenschutz' => 'https://example.org/datenschutz'],
            'en' => ['Imprint' => 'https://example.org/en/imprint', 'Privacy' => 'https://example.org/en/privacy'],
        ],
        'kontaktEmail' => 'kontakt@example.org',
    ],
],
```

**Hinweis:** Die Oberflächentexte (`services/Texte.php`, DE/EN/ES) nennen die N.E.X.U.S.-Gemeinschaft – für eine andere Gemeinschaft dort anpassen.

## English

**Sharing and public read links.**

1. **"Share"** below posts and wiki pages: the device's share menu on phones, "copy link" on desktops. The text is sent in the sharer's language.
2. **"Make publicly readable":** the author or an admin publishes a single post or wiki page under its own link, readable without login – without the author's name, comments or circle. Mentioned members become "a member". Can be revoked at any time.
3. **Showcase:** admins put published items on a public page (indexable by search engines), plus a JSON feed for your own website.

On the read page, attachments are displayed inline only for images, video, audio and PDF; everything else is downloaded.

**Requirements:** HumHub 1.18+. Optional: `nexus-translate` (translated versions), `nexus-karte` (map on the showcase page), ffmpeg (video stills).

**Settings:** see the example above – `webseiten`, `registrierenUrl`, `anmeldenUrl`, `fussLinks` (footer links per language), `kontaktEmail`.

**Note:** the UI texts (`services/Texte.php`, DE/EN/ES) mention the N.E.X.U.S. community – adapt them for your own community.
