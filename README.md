<p align="center">
  <img src="https://raw.githubusercontent.com/nexus-united-species/.github/main/profile/assets/logo.png" alt="N.E.X.U.S. Logo" width="100">
</p>

<h1 align="center">N.E.X.U.S. HumHub-Erweiterungen</h1>

<p align="center">
  <strong>Module für HumHub aus einer echten Gemeinschaft – Karte, Übersetzen, Sprachnachrichten, Teilen und mehr</strong><br>
  <strong>HumHub modules from a real community – map, translation, voice messages, sharing and more</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/HumHub-1.18%2B-1b9ed6" alt="HumHub 1.18+">
  <img src="https://img.shields.io/badge/license-AGPL%20v3-blue" alt="License AGPL v3">
  <img src="https://img.shields.io/badge/status-preview-orange" alt="Status: preview">
</p>

<p align="center">
  <a href="https://community.nexus-terminal.org/">Live-Community</a> ·
  <a href="https://www.nexus-terminal.org">Webseite / Website</a> ·
  <a href="https://github.com/nexus-united-species/oneapp/discussions">Diskussionen / Discussions</a>
</p>

<p align="center">
  <a href="#-deutsch">🇩🇪 Deutsch</a> &nbsp;|&nbsp; <a href="#-english">🇬🇧 English</a>
</p>

---

## 🇩🇪 Deutsch

> **Vorschau:** Diese Module laufen produktiv auf der [N.E.X.U.S.-Community](https://community.nexus-terminal.org/). Adressen, Kreis-Nummern und Links sind einstellbar (siehe README des jeweiligen Moduls); einige Oberflächentexte und die Persona des Assistenten sind noch auf N.E.X.U.S. zugeschnitten. Die Oberfläche ist Deutsch, vieles auch Englisch und Spanisch.

### Worum es geht

[N.E.X.U.S.](https://www.nexus-terminal.org) baut offene Infrastruktur für selbstbestimmte Gemeinschaften. Unsere Community-Plattform läuft auf [HumHub](https://www.humhub.com). Was uns dort gefehlt hat, haben wir selbst gebaut – und teilen es hier mit allen, die HumHub für ihre eigene Gemeinschaft nutzen.

### Module

| Modul | Was es tut |
|---|---|
| [`nexus-karte`](modules/nexus-karte) | **Gemeinschaftskarte:** zeigt Kreise (Spaces), die als Gemeinschaft markiert sind, auf einer Landkarte – nur Ort/Region, nie eine Adresse. Kartenkacheln laufen über den eigenen Server. |
| [`nexus-translate`](modules/nexus-translate) | **Übersetzen-Knopf** an Beiträgen, Kreisbeschreibungen und Terminen – über einen eigenen KI-Zugang (Google Gemini) statt eines kostenpflichtigen Marktplatz-Moduls. |
| [`nexus-voice`](modules/nexus-voice) | **Sprach- und Videonachrichten** in Beiträgen, Kommentaren und privaten Nachrichten (bis 5 bzw. 3 Minuten, runder Abspieler). Optional mit Mitschrift über [`extras/nexus-whisper`](extras/nexus-whisper). |
| [`nexus-teilen`](modules/nexus-teilen) | **Teilen-Knopf** unter Beiträgen und Wiki-Seiten (Handy-Teilen-Menü oder Link) und öffentliche Lese-Links für einzeln freigegebene Inhalte. |
| [`nexus-meeting`](modules/nexus-meeting) | **Laufende Online-Treffen** aus dem Kalender als Hinweis, Öffnen im kleinen Fenster; behebt einen Ladefehler des Jitsi-Moduls bei langsamem Netz. |
| [`nexus-markdown-preview`](modules/nexus-markdown-preview) | **Vorschau für `.md`-Dateien**: formatiert hochgeladene Markdown-Dateien statt Rohtext. |
| [`nexus-video-faststart`](modules/nexus-video-faststart) | **Videos starten sofort:** verschiebt beim Hochladen den `moov`-Atom an den Dateianfang. |
| [`nexus-notif-banner`](modules/nexus-notif-banner) | **Freundlicher Hinweis vor der Browser-Abfrage** für Benachrichtigungen – damit nicht reflexartig „Blockieren" geklickt wird. |
| [`nexus-hilfe`](modules/nexus-hilfe) | **Hilfe & Support:** Hilfeseite, KI-Fragefunktion und Anfrageformular (auch ohne Anmeldung); Anfragen landen in einem privaten Support-Kreis. |
| [`nexus-mitgliedsanfrage`](modules/nexus-mitgliedsanfrage) | **Antworten neuer Mitglieder** auf die Kennenlern-Fragen der Registrierung gehen als Nachricht an die Admins. |
| [`nexus-community-assistant`](modules/nexus-community-assistant) | **KI-Assistent** mit eigenem Bot-Konto: Wochenzusammenfassung für Admins, Meeting-Erinnerungen, Begrüßung, Antworten auf @-Erwähnungen und Direktnachrichten. Wissensbasis frei befüllbar ([`knowledge/`](modules/nexus-community-assistant/knowledge)). |
| [`nexus-gesundheit`](modules/nexus-gesundheit) | **Gesundheitswissen** im Stil von Wikipedia in einem Kreis: Suche auch mit Alltagswörtern, Themen, Schlagworte, Querverweise, Versionen. Nur Admins bearbeiten, Kreis-Mitglieder schicken Vorschläge. Artikel werden aus einem eigenen Ordner importiert (nicht im Repo). |
| [`nexus-arbeitszeit`](modules/nexus-arbeitszeit) | **Ehrenamtliche Arbeitsstunden** erfassen (Formular oder Nachricht an den Assistenten), Kreisen zuordnen, freigeben, als Excel exportieren. |
| [`nexus-protected-library`](modules/nexus-protected-library) | **Geschützter Lesebereich** für Bücher (EPUB-Import), kapitelweise im Browser lesbar, nur für Mitglieder eines Unterstützer-Kreises. |
| [`nexus-supporter-bridge`](modules/nexus-supporter-bridge) | **Ko-fi-Anbindung:** Unterstützende erhalten automatisch Zugang zum Unterstützer-Kreis. |

**Extras** (N.E.X.U.S.-spezifisch oder Ergänzungen anderer Module, eher als Vorlage gedacht):

| Extra | Was es ist |
|---|---|
| [`extras/nexus-theme`](extras/nexus-theme) | Unser Theme mit eigener Gast-Startseite – als Beispiel für eigenes Branding. |
| [`extras/nexus-quicklinks`](extras/nexus-quicklinks) | Links zu weiteren Diensten in der Navigationsleiste. |
| [`extras/mail-read-receipts`](extras/mail-read-receipts) | Lesestatus („Gelesen") für das Mail-Modul – überschreibt eine Datei des Mail-Moduls. |
| [`extras/nexus-whisper`](extras/nexus-whisper) | Kleiner Mitschrift-Dienst (faster-whisper) für `nexus-voice`, als Docker-Container. |
| [`extras/terminology-circles`](extras/terminology-circles) | Begriffs-Überschreibungen: „Space" heißt „Kreis" (DE/EN/ES). |

### Installation

1. Voraussetzung: HumHub **1.18 oder neuer**.
2. Gewünschten Modulordner nach `protected/modules/` deiner HumHub-Installation kopieren (bzw. in einen Docker-Container unter `/data/modules/`).
3. Im HumHub-Admin unter **Verwaltung → Module** aktivieren und die Moduleinstellungen ausfüllen.
4. Cache leeren: `php protected/yii cache/flush-all`

Zugangsdaten werden **nie** im Code gespeichert, sondern über Moduleinstellungen oder Umgebungsvariablen gesetzt:

| Modul | Einstellung / Umgebungsvariable |
|---|---|
| `nexus-translate`, `nexus-community-assistant` | Google-API-Schlüssel und Modell (Moduleinstellung) |
| `nexus-community-assistant`, `nexus-hilfe`, `nexus-arbeitszeit` | `ASSISTANT_USER_ID` – Konto des Assistenten |
| `nexus-supporter-bridge`, `nexus-protected-library` | `SUPPORTER_SPACE_ID`, `KO_FI_VERIFICATION_TOKEN`, `GRACE_PERIOD_DAYS` |
| `nexus-voice` | Whisper-Token (Moduleinstellung), passend zu `extras/nexus-whisper` |
| `nexus-arbeitszeit` | Nextcloud-Zugang für den Bericht (Moduleinstellung) |

Alles ohne Geheimnis – eigene Webseiten, Links für Impressum/Datenschutz, Kreis- und Seiten-Nummern – steht in `protected/config/common.php` unter `'modules' => ['<modul-id>' => [...]]`. Ohne Eintrag nutzen die Module neutrale Vorgaben. Welche Einstellungen es gibt, steht in der README des Moduls.

**Abhängigkeiten:** `nexus-hilfe` und `nexus-mitgliedsanfrage` brauchen `nexus-arbeitszeit` (Nachrichtenversand); `nexus-community-assistant` braucht `nexus-translate` (KI-Zugang). Alle anderen Verbindungen sind optional – fehlt ein Modul, entfällt nur die jeweilige Zusatzfunktion.

### Mitmachen

Fehler, Fragen und Ideen gerne als [Issue](https://github.com/nexus-united-species/humhub-nexus/issues) oder in den [Diskussionen](https://github.com/nexus-united-species/oneapp/discussions). Es gelten unser [Leitfaden für Beiträge](https://github.com/nexus-united-species/.github/blob/main/CONTRIBUTING.md) und der [Verhaltenskodex](https://github.com/nexus-united-species/.github/blob/main/CODE_OF_CONDUCT.md). Sicherheitslücken bitte [nicht öffentlich melden](https://github.com/nexus-united-species/.github/blob/main/SECURITY.md).

### Lizenz

[AGPL v3](LICENSE) – wie HumHub selbst. HumHub ist eine Marke der HumHub GmbH; dieses Projekt steht in keiner Verbindung zur HumHub GmbH.

<p align="right"><a href="#-english">English ↓</a></p>

---

## 🇬🇧 English

> **Preview:** These modules run in production on the [N.E.X.U.S. community](https://community.nexus-terminal.org/). Addresses, circle IDs and links are configurable (see each module's README); some UI texts and the assistant's persona are still tailored to N.E.X.U.S. The interface is German, much of it also English and Spanish.

### What this is about

[N.E.X.U.S.](https://nexus-terminal.org/en/) builds open infrastructure for self-determined communities. Our community platform runs on [HumHub](https://www.humhub.com). Whatever we were missing there, we built ourselves – and we share it here with everyone who runs HumHub for their own community.

### Modules

| Module | What it does |
|---|---|
| [`nexus-karte`](modules/nexus-karte) | **Community map:** shows spaces marked as a community on a map – only place/region, never an address. Map tiles are served through your own server. |
| [`nexus-translate`](modules/nexus-translate) | **Translate button** on posts, space descriptions and events – using your own AI access (Google Gemini) instead of a paid marketplace module. |
| [`nexus-voice`](modules/nexus-voice) | **Voice and video messages** in posts, comments and private messages (up to 5 and 3 minutes, round player). Optional transcription via [`extras/nexus-whisper`](extras/nexus-whisper). |
| [`nexus-teilen`](modules/nexus-teilen) | **Share button** under posts and wiki pages (mobile share sheet or link) and public read links for individually released content. |
| [`nexus-meeting`](modules/nexus-meeting) | **Live online meetings** from the calendar shown as a notice, opened in a small window; fixes a loading error of the Jitsi module on slow connections. |
| [`nexus-markdown-preview`](modules/nexus-markdown-preview) | **Preview for `.md` files:** renders uploaded Markdown files instead of raw text. |
| [`nexus-video-faststart`](modules/nexus-video-faststart) | **Videos start instantly:** moves the `moov` atom to the start of the file on upload. |
| [`nexus-notif-banner`](modules/nexus-notif-banner) | **Friendly notice before the browser's notification prompt** – so people don't reflexively click "Block". |
| [`nexus-hilfe`](modules/nexus-hilfe) | **Help & support:** help page, AI question feature and request form (also without login); requests go to a private support space. |
| [`nexus-mitgliedsanfrage`](modules/nexus-mitgliedsanfrage) | **New members' answers** to the registration questions are sent to the admins as a message. |
| [`nexus-community-assistant`](modules/nexus-community-assistant) | **AI assistant** with its own bot account: weekly summary for admins, meeting reminders, welcome messages, replies to @-mentions and direct messages. Knowledge base is up to you ([`knowledge/`](modules/nexus-community-assistant/knowledge)). |
| [`nexus-gesundheit`](modules/nexus-gesundheit) | **Health knowledge** as a Wikipedia-style reference in a space: search with everyday words, topics, keywords, cross-links, versions. Only admins edit, space members send suggestions. Articles are imported from your own folder (not in the repo). |
| [`nexus-arbeitszeit`](modules/nexus-arbeitszeit) | **Volunteer hours:** log hours (form or message to the assistant), assign them to spaces, approve, export to Excel. |
| [`nexus-protected-library`](modules/nexus-protected-library) | **Protected reading area** for books (EPUB import), readable chapter by chapter in the browser, only for members of a supporter space. |
| [`nexus-supporter-bridge`](modules/nexus-supporter-bridge) | **Ko-fi integration:** supporters automatically get access to the supporter space. |

**Extras** (N.E.X.U.S.-specific or additions to other modules, meant as templates):

| Extra | What it is |
|---|---|
| [`extras/nexus-theme`](extras/nexus-theme) | Our theme with its own guest start page – as an example for custom branding. |
| [`extras/nexus-quicklinks`](extras/nexus-quicklinks) | Links to further services in the navigation bar. |
| [`extras/mail-read-receipts`](extras/mail-read-receipts) | Read receipts ("Read") for the mail module – overrides a file of the mail module. |
| [`extras/nexus-whisper`](extras/nexus-whisper) | Small transcription service (faster-whisper) for `nexus-voice`, as a Docker container. |
| [`extras/terminology-circles`](extras/terminology-circles) | Wording overrides: "Space" becomes "Circle" (DE/EN/ES). |

### Installation

1. Requirement: HumHub **1.18 or newer**.
2. Copy the module folder you want into `protected/modules/` of your HumHub installation (or into a Docker container under `/data/modules/`).
3. Enable it in the HumHub admin under **Administration → Modules** and fill in the module settings.
4. Clear the cache: `php protected/yii cache/flush-all`

Credentials are **never** stored in code; they are set via module settings or environment variables:

| Module | Setting / environment variable |
|---|---|
| `nexus-translate`, `nexus-community-assistant` | Google API key and model (module setting) |
| `nexus-community-assistant`, `nexus-hilfe`, `nexus-arbeitszeit` | `ASSISTANT_USER_ID` – the assistant's account |
| `nexus-supporter-bridge`, `nexus-protected-library` | `SUPPORTER_SPACE_ID`, `KO_FI_VERIFICATION_TOKEN`, `GRACE_PERIOD_DAYS` |
| `nexus-voice` | Whisper token (module setting), matching `extras/nexus-whisper` |
| `nexus-arbeitszeit` | Nextcloud access for the report (module setting) |

Everything that is not a secret – your websites, imprint/privacy links, circle and page IDs – goes into `protected/config/common.php` under `'modules' => ['<module-id>' => [...]]`. Without an entry, the modules use neutral defaults. Each module's README lists its settings.

**Dependencies:** `nexus-hilfe` and `nexus-mitgliedsanfrage` require `nexus-arbeitszeit` (message sending); `nexus-community-assistant` requires `nexus-translate` (AI access). All other links between modules are optional – if a module is missing, only the related extra feature is unavailable.

### Contributing

Bugs, questions and ideas are welcome as an [issue](https://github.com/nexus-united-species/humhub-nexus/issues) or in the [Discussions](https://github.com/nexus-united-species/oneapp/discussions). Our [contribution guide](https://github.com/nexus-united-species/.github/blob/main/CONTRIBUTING.md) and [code of conduct](https://github.com/nexus-united-species/.github/blob/main/CODE_OF_CONDUCT.md) apply. Please [do not report security vulnerabilities publicly](https://github.com/nexus-united-species/.github/blob/main/SECURITY.md).

### License

[AGPL v3](LICENSE) – like HumHub itself. HumHub is a trademark of HumHub GmbH; this project is not affiliated with HumHub GmbH.

<p align="right"><a href="#-deutsch">Deutsch ↑</a></p>
