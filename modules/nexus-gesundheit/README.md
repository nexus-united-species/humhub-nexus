# nexus-gesundheit

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Gesundheitswissen:** ein Nachschlagewerk im Stil von Wikipedia innerhalb eines Kreises (Space) – für Gesundheitsartikel, die eine Redaktion als Markdown liefert.

- **Suche auch mit Alltagswörtern:** Jeder Artikel trägt sichtbare Schlagworte und unsichtbare Suchwörter („Bauchweh“ findet den Artikel über Bauchschmerzen). Beides kann eine KI vorschlagen, der Artikeltext bleibt dabei unverändert.
- **Themen und Schlagworte** zum Stöbern, **Artikelseite** mit Inhaltsverzeichnis, automatischen Querverweisen auf andere Artikel, Infokasten (Thema, Prüfdatum, nächste Prüfung, Anzahl Belege), „Siehe auch“ und einem festen medizinischen Hinweis.
- **Nur Admins bearbeiten.** Jede Änderung wird als Version gespeichert und lässt sich wiederherstellen.
- **Mitglieder des Kreises machen Vorschläge** („Ich möchte etwas ergänzen“ / „… ändern“). Die Admins bekommen eine Nachricht, entscheiden in einer Liste und antworten kurz – die Antwort geht an die vorschlagende Person.
- **„Im Kreis darüber sprechen“** legt einen normalen Beitrag im Kreis an, mit Link zum Artikel.
- **Freigabe:** Bis ein Admin auf „Für alle Mitglieder freigeben“ klickt, sehen nur Admins den Bereich. Danach sehen ihn alle angemeldeten Mitglieder, die den Kreis öffnen können.
- **Wiedervorlage:** Ist das Datum „Nächste Prüfung“ erreicht, erinnert der tägliche Cron-Lauf die Admins.

**Die Artikel selbst sind nicht Teil dieses Moduls.** Sie werden aus einem Ordner auf dem Server importiert:

```
<importPfad>/
  Redaktion/manifest.json   Liste der Artikel (siehe unten)
  Artikel/<Thema>/<Datei>.md
```

`manifest.json` ist eine Liste von Einträgen dieser Form – übernommen werden nur Einträge mit `"status": "ueberarbeitet"`:

```json
{"original": "G0001 Alter Dateiname.doc", "status": "ueberarbeitet",
 "artikelpfad": "Artikel/02_Heilpflanzen/G0001_Aloe_vera.md", "titel": "…",
 "thema": "02_Heilpflanzen", "pruefdatum": "2026-10-08", "wiedervorlage": "2027-10-08"}
```

Schlüssel eines Artikels ist `original` – der Import ist wiederholbar: unveränderte Artikel werden übersprungen, neue Fassungen aktualisiert, **im Portal bearbeitete Artikel aber nie überschrieben**, nur gemeldet. Die Themen-Codes (`01_Ernaehrung` … `06_Gesundheitswissen`) und ihre Namen stehen in `models/Artikel.php` und `services/Texte.php` – für andere Inhalte dort anpassen.

**Voraussetzungen:** HumHub 1.18+. Optional: `nexus-arbeitszeit` (verschickt die Nachrichten an Admins und Mitglieder), `nexus-community-assistant` + `nexus-translate` (KI für Schlagworte).

**Einstellungen** (`protected/config/common.php`):

```php
'modules' => ['nexus-gesundheit' => [
    'kreis' => 14,                              // Space-ID, in dem das Gesundheitswissen erscheint (Pflicht)
    'importPfad' => '/data/gesundheit-import',  // Ordner mit Redaktion/ und Artikel/
]],
```

**Befehle** (im HumHub-Verzeichnis, als Web-Server-Benutzer):

```bash
php modules/nexus-gesundheit/commands/import.php --probe        # zeigen, was passieren würde
php modules/nexus-gesundheit/commands/import.php                # übernehmen
php modules/nexus-gesundheit/commands/schlagworte.php --probe=3 # KI-Schlagworte für 3 Artikel zeigen
php modules/nexus-gesundheit/commands/schlagworte.php           # für alle Artikel ohne Schlagworte
```

Die Befehle erwarten HumHub unter `/opt/humhub` (Docker-Image) – bei anderer Installation die Pfade im Kopf der Skripte anpassen.

**Hinweis:** Die Oberfläche ist dreisprachig (DE/EN/ES), die Artikel bleiben in ihrer Originalsprache.

## English

**Health knowledge:** a Wikipedia-style reference inside a space – for health articles supplied by an editorial team as Markdown.

- **Search with everyday words:** every article has visible keywords and hidden search terms (a colloquial word finds the matching article). An AI can suggest both; the article text itself is never changed.
- **Topics and keywords** for browsing; **article page** with table of contents, automatic cross-links to other articles, info box (topic, review date, next review, number of references), "See also" and a fixed medical disclaimer.
- **Only admins edit.** Every change is saved as a version and can be restored.
- **Space members make suggestions** ("I would like to add / change something"). Admins get a message, decide in a list and reply briefly – the reply is sent to the member.
- **"Talk about it in the circle"** creates a regular post in the space linking to the article.
- **Release:** until an admin clicks "Release for all members", only admins see it. Afterwards all logged-in members who can open the space see it.
- **Review reminders:** when the "next review" date is reached, the daily cron run reminds the admins.

**The articles are not part of this module.** They are imported from a folder on the server (`Redaktion/manifest.json` + `Artikel/<topic>/<file>.md`, format see German section). Only entries with `"status": "ueberarbeitet"` are imported. The import is repeatable; articles edited in the portal are never overwritten, only reported. Topic codes and names live in `models/Artikel.php` and `services/Texte.php` – adapt them for other content.

**Requirements:** HumHub 1.18+. Optional: `nexus-arbeitszeit` (sends messages), `nexus-community-assistant` + `nexus-translate` (AI keywords).

**Settings:** `kreis` (space ID, required) and `importPfad` (import folder) in `protected/config/common.php` – see above. **Commands:** `commands/import.php [--probe]` and `commands/schlagworte.php [--probe=N] [--neu]`; they expect HumHub at `/opt/humhub` (Docker image).

**Note:** the interface is available in German, English and Spanish; articles stay in their original language.
