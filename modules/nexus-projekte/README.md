# nexus-projekte

🇬🇧 [English](#english) · 🇩🇪 [Deutsch](#deutsch)

## English

**Project boards for spaces** – made for communities where many members are not used to project tools. HumHub's task module offers lists, states and "start task"; this module offers a pin board with cards and buttons.

### How it works

- **Projects** live in a space (menu entry "Projects"). The overview shows every project as a tile with lead, due date, progress bar and what is waiting for you.
- **A project is a board** with four columns: **Open → In progress → Up for feedback → Done**. Each card is one piece of work, e.g. one month of a calendar.
- **Each card has exactly one responsible person** (so it is clear who is in charge), any number of helpers, a due date, a checklist of steps and links to documents (wiki pages, files).
- **"I will take this"**: any space member can pick up an open card with one click.
- **One-click feedback**: when a card is moved to "Up for feedback", the space gets a post asking for feedback. Members click **"Looks good"** or **"I have a change"** (with a short explanation). The card shows who agreed, who asked for changes and which project members have not answered yet.
- **"My tasks"** in the top bar (and as a box on the dashboard and, filtered to that space, at the top of each space's sidebar): everything that concerns you across all spaces – cards you are responsible for or helping with, and feedback rounds waiting for you – sorted by date, overdue items in red.
- **Reminders** as private messages: one day before a due date, once when a card is overdue (only to the responsible person and the lead, never publicly), and one day before a feedback round ends to project members who have not answered.
- Works on phones: buttons instead of drag and drop.

### Who can do what

| | |
|---|---|
| Create projects | space admins (with `'projektAnlegen' => 'mitglieder'`: all members) |
| Project leads (up to 5) and space admins | edit project, add cards, assign people, move cards, mark as done, delete |
| Responsible person | edit their card, tick steps, move it up to "Up for feedback" (only the lead marks it done) |
| Helpers | tick steps |
| Space members | "I will take this", feedback, "Join in" (to get feedback reminders) |

### Installation

1. Copy the folder to `protected/modules/nexus-projekte` (or your custom module path).
2. `php yii module/enable nexus-projekte` (or Administration → Modules).
3. In each space that should use it: **Space → Settings → Modules → Projects → Enable.** Disabling it in a space deletes that space's projects (HumHub asks before).

Optional settings in `protected/config/common.php`:

```php
'modules' => ['nexus-projekte' => [
    'projektAnlegen' => 'kreisadmins',  // or 'mitglieder': every member may create projects
    'erinnern' => true,                 // private messages and reminders
    'abstimmungTage' => 7,              // default length of a feedback round
]],
```

**Messages:** private messages are sent through [`nexus-arbeitszeit`](../nexus-arbeitszeit) (one ongoing conversation per project). Without it, the board and "My tasks" still show everything – only the messages are skipped. Posts in the space work without it. Reminders run with HumHub's daily cron.

**Requirements:** HumHub 1.18+. Interface in English, German and Spanish; messages and posts are German (`services/Nachrichten.php`).

## Deutsch

**Projekt-Tafeln für Kreise** – gemacht für Gemeinschaften, in denen viele Mitglieder keine Projektwerkzeuge gewohnt sind: eine Pinnwand mit Kacheln und Knöpfen statt Listen und Status.

- **Projekte** im Kreis (Menüpunkt „Projekte“), jedes als Kachel mit Leitung (bis zu fünf Menschen), Termin, Fortschritt und dem, was auf dich wartet.
- **Tafel mit vier Spalten:** Offen → In Arbeit → Zur Abstimmung → Fertig.
- **Jede Kachel:** genau eine verantwortliche Person, Mithelfende, Frist, Teilschritte zum Abhaken, Links zu Unterlagen.
- **„Das übernehme ich“** – ein Klick für jedes Kreismitglied.
- **Abstimmung mit einem Klick:** Kommt eine Kachel auf „Zur Abstimmung“, erscheint ein Beitrag im Kreis. Mitglieder klicken „Passt so“ oder „Ich habe eine Änderung“. Die Kachel zeigt, wer zugestimmt hat, wer etwas ändern möchte und wer aus dem Projekt sich noch nicht gemeldet hat.
- **„Meine Aufgaben“** oben im Portal, auf der Startseite und rechts in jedem Kreis (nur die Aufgaben dieses Kreises): alles aus allen Kreisen, nach Datum, Überfälliges rot.
- **Erinnerungen** als private Nachricht: einen Tag vor der Frist, einmal bei Überschreitung (nur an Verantwortliche und Leitung, nie öffentlich), einen Tag vor Ende einer Abstimmung an Beteiligte ohne Rückmeldung.

**Einrichten:** Modul einschalten, dann im Kreis unter Einstellungen → Module „Projekte“ aktivieren. Einstellungen siehe oben (`projektAnlegen`, `erinnern`, `abstimmungTage`). Private Nachrichten über `nexus-arbeitszeit`, sonst entfallen nur die Nachrichten.
