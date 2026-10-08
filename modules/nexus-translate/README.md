# nexus-translate

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Übersetzen-Knopf** an Beiträgen, Kommentaren, privaten Nachrichten, Wiki-Seiten, Kreisbeschreibungen und Terminen – über einen eigenen KI-Zugang (Google Gemini) statt eines kostenpflichtigen Marktplatz-Moduls. Übersetzungen werden gespeichert, jeder Text wird also nur einmal übersetzt. Dazu ein Sprachumschalter (DE/EN/ES) für die Oberfläche.

Andere Module nutzen den hier hinterlegten KI-Zugang mit (`nexus-community-assistant`, `nexus-teilen`).

**Voraussetzungen:** HumHub 1.18+, ein Google-Gemini-API-Schlüssel.

**Einstellungen** (in der Datenbank):

```bash
php yii settings/set nexus-translate googleApiKey "<api-schluessel>"
php yii settings/set nexus-translate modell "gemini-3.7-flash"     # optional
php yii settings/set nexus-translate kreisnamenAktiv 1             # optional: auch Kreisnamen übersetzen
```

`werkzeuge_*.php` sind Konsolen-Hilfen, um bestehende Inhalte vorab zu übersetzen.

## English

**Translate button** on posts, comments, private messages, wiki pages, circle descriptions and calendar entries – using your own AI access (Google Gemini) instead of a paid marketplace module. Translations are cached, so every text is translated only once. Also includes a UI language switcher (DE/EN/ES).

Other modules reuse the AI access configured here (`nexus-community-assistant`, `nexus-teilen`).

**Requirements:** HumHub 1.18+, a Google Gemini API key.

**Settings** (stored in the database): `googleApiKey` (required), `modell` (optional model name), `kreisnamenAktiv` (optional, also translate circle names) – see the commands above.

`werkzeuge_*.php` are console helpers to pre-translate existing content.
