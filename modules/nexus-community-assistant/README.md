# nexus-community-assistant

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**KI-Assistent mit eigenem Bot-Konto** (bei uns „Nova“). Er gibt sich nie als Mensch aus.

- **Antwortet** auf @-Erwähnungen in Beiträgen/Kommentaren und auf private Nachrichten – in der Sprache der Frage, gestützt auf eine Wissensbasis aus Textdateien ([`knowledge/`](knowledge)) und die Anleitungen eines Kreises.
- **Gibt weiter:** Was ein Mensch klären muss (Konto-Probleme, persönliche Anliegen …), landet als Support-Anfrage bei `nexus-hilfe`, ohne das Modul bei den Admins.
- **Begrüßt** neue Mitglieder per privater Nachricht (3 Stunden nach dem ersten Besuch, DE/EN/ES), auf Wunsch mit einem passenden Kreis-Vorschlag aus den Antworten von `nexus-mitgliedsanfrage`.
- **Wochenbericht** an die Admins, **Meeting-Erinnerungen** (in der Modulverwaltung einstellbar), wiederkehrende **Selbstvorstellung** und optional ein **Spendenaufruf** in ausgewählten Kreisen.

**Voraussetzungen:** HumHub 1.18+ (offizielles Docker-Image: die Skripte in `commands/` erwarten `/opt/humhub` und `/data/config`), ein Bot-Konto, **`nexus-translate`** (liefert den KI-Zugang). Optional: `nexus-hilfe`, `nexus-arbeitszeit`, `nexus-mitgliedsanfrage`, Wiki- und Kalender-Modul.

**Umgebungsvariablen** des HumHub-Containers: `ASSISTANT_USER_ID` (ID des Bot-Kontos, Pflicht), `PERIODIC_REMINDER_SPACE_IDS` (kommagetrennte Space-IDs für Selbstvorstellung/Spendenaufruf).

**Zeitgesteuert** (z. B. systemd-Timer oder cron, als `www-data` im Container):

| Skript | Takt |
|---|---|
| `commands/send_welcome.php` | alle 15 Minuten (`--probe` zeigt nur die Texte) |
| `commands/check_meetings.php` | alle paar Minuten |
| `commands/send_periodic_reminders.php` | stündlich (prüft selbst, ob etwas fällig ist) |
| `commands/send_weekly_summary.php` | wöchentlich |

**Einstellungen** (`protected/config/common.php`, alle optional):

```php
'modules' => [
    'nexus-community-assistant' => [
        'anleitungenKreis' => 2,              // Wiki-Seiten dieses Kreises = Anleitungen für Mitglieder
        'vorstellungenKreis' => 18,           // Begrüßung: "stell dich hier vor"
        'nichtVorschlagen' => [2, 3, 4],      // Kreise, die die Begrüßung nie vorschlägt
        'willkommensrundeTermin' => 43,       // Kalendereintrag einer regelmäßigen Willkommensrunde
        'spendenLink' => 'https://ko-fi.com/...', // ohne Link kein Spendenaufruf
    ],
],
```

**Hinweis:** Persona und feste Texte sind auf N.E.X.U.S. zugeschnitten (`services/AssistentPersona.php`, `WillkommensService.php`, `PeriodicReminderService.php`, `WeeklySummaryService.php`) – für eine andere Gemeinschaft dort anpassen. Die N.E.X.U.S.-Wissensbasis ist nicht Teil des Repos.

## English

**AI assistant with its own bot account** (ours is called "Nova"). It never pretends to be human.

- **Answers** @-mentions in posts/comments and private messages – in the language of the question, based on a knowledge base of text files ([`knowledge/`](knowledge)) and the guides of a circle.
- **Hands over:** anything a human has to handle (account problems, personal matters …) becomes a support request in `nexus-hilfe`, or goes to the admins without that module.
- **Welcomes** new members with a private message (3 hours after their first visit, DE/EN/ES), optionally suggesting a matching circle based on the answers from `nexus-mitgliedsanfrage`.
- **Weekly report** to the admins, **meeting reminders** (configurable in the module admin), recurring **self-introduction** and an optional **donation appeal** in selected circles.

**Requirements:** HumHub 1.18+ (official Docker image: the scripts in `commands/` expect `/opt/humhub` and `/data/config`), a bot account, **`nexus-translate`** (provides the AI access). Optional: `nexus-hilfe`, `nexus-arbeitszeit`, `nexus-mitgliedsanfrage`, wiki and calendar modules.

**Environment variables** of the HumHub container: `ASSISTANT_USER_ID` (bot account ID, required), `PERIODIC_REMINDER_SPACE_IDS` (comma-separated space IDs for self-introduction/donation appeal).

**Scheduled scripts:** see the table above (run as `www-data` inside the container, e.g. via systemd timers or cron).

**Settings:** see the example above – `anleitungenKreis`, `vorstellungenKreis`, `nichtVorschlagen`, `willkommensrundeTermin`, `spendenLink` (no link = no donation appeal).

**Note:** persona and fixed texts are tailored to N.E.X.U.S. (`services/AssistentPersona.php`, `WillkommensService.php`, `PeriodicReminderService.php`, `WeeklySummaryService.php`) – adapt them for your community. The N.E.X.U.S. knowledge base is not part of this repository.
