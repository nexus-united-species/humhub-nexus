# nexus-wissen

🇬🇧 [English](#english) · 🇩🇪 [Deutsch](#deutsch)

## English

**Knowledge base:** Wikipedia-style reference collections inside spaces. Write articles in the portal, upload Word or text files, or import a folder of Markdown files, and your members get a searchable, cross-linked reference: a handbook, a recipe collection, a herbal guide, a glossary, an FAQ, project documentation. Each space can have its own collection, and a space can have several.

### What members see

- **Search with everyday words.** Every article has visible keywords and hidden search terms. A colloquial word finds the matching article even if the article itself uses the technical term. Queries with several words rank articles that match all of them first; common word endings such as plurals are ignored.
- **Topics and keywords** to browse.
- **Article pages** with a table of contents, automatic cross-links (when an article mentions another article's title, the first mention becomes a link), an info box (topic, keywords, review date, number of references) and "See also" (articles sharing keywords).
- **An optional notice** under every article, e.g. "does not replace medical advice".
- **"I would like to add / change something"**: space members send a suggestion to the editors. **"Talk about it in the space"** creates a regular post linking to the article.

### What editors get

- **Editors are the system admins and the owner and admins of the space.** Only they change articles. This is deliberate: the module is meant for reviewed content, not an open wiki. Members send suggestions.
- **Create a knowledge area yourself:** space owners and admins find "Create knowledge area" in the space's settings menu (top right) as long as the space has none. Name, icon, description and notice can be changed later under "Settings".
- **Categories in the portal:** create, rename, choose an icon, reorder, and delete empty ones. On first use the categories of a collection from `common.php` (`themen`) are taken over; after that the portal is the only place to change them.
- **New articles:** write them in the editor or upload a file (.docx, .md, .txt, up to 10 MB). The upload only fills the form, so you can check and correct the text first. Word images are not transferred; a notice marks the spot. Every new article starts as a **draft** that only editors see (also not in search or cross-links) until someone clicks "Publish". Keywords left empty are suggested by the AI if available.
- **Version history** for every article; any version can be restored.
- **Suggestion inbox:** accept or decline with a short reply; the member is told the result.
- **Release switch:** a new collection is visible only to editors until an editor clicks "Release for all members".
- **Review reminders:** an article can have a "next review" date; the daily cron reminds the editors.
- **Import is repeatable:** unchanged files are skipped, changed files update the article, and articles edited in the portal are **never overwritten**, only reported. Articles whose file disappeared are reported, not deleted.

### Step by step: add your own collection

**1. Prepare a folder with Markdown files on the server**, readable by the web server user:

```
/data/wissen/handbook/
  Getting-started/
    Create-an-account.md
    Your-profile.md
  Spaces/
    Join-a-space.md
  Glossary.md
```

- Every `.md` file becomes one article. The **first subfolder is its topic** ("Getting-started" becomes the topic "Getting started"; a leading number like `01_` is removed from the displayed name, so you can control the order). Files directly in the folder have no topic.
- The **title** is the first `# Heading` of the file (or the file name).
- `README.md` and files or folders starting with `_` or `.` are skipped.
- Articles link to each other with normal relative links: `[Your profile](Your-profile.md)`.
- Use `## Headings` for sections: they form the table of contents. A section called "Sources", "References" or "Literature" is counted as references.

**Optional: details at the top of a file** (front matter):

```markdown
---
title: Create an account
topic: Getting-started
keywords: [account, registration]
search_terms: [sign up, login, password]
reviewed: 2026-10-01
next_review: 2027-10-01
---
# Create an account
...
```

German field names work as well (`titel`, `thema`, `schlagworte`, `suchwoerter`, `pruefdatum`, `wiedervorlage`, `kennung`).

**Optional: a `manifest.json`** in the folder (or in `Redaktion/`) if an editorial team keeps a list. Then only listed files are imported:

```json
[
  {"artikelpfad": "Spaces/Join-a-space.md", "schluessel": "SP-001", "titel": "Join a space",
   "thema": "Spaces", "pruefdatum": "2026-10-01", "wiedervorlage": "2027-10-01", "status": "approved"}
]
```

`schluessel` (or `original`) is the stable identity of the article. Without it, the file path is used, so renaming a file creates a new article. With the setting `importStatus` only entries with this `status` are imported.

**2. Register the collection** in `protected/config/common.php`:

```php
'modules' => [
    'nexus-wissen' => [
        'sammlungen' => [
            'handbook' => [                       // identifier: lowercase letters, digits, hyphens
                'kreis' => 5,                      // space ID (required)
                'name' => ['en' => 'Handbook', 'de' => 'Handbuch'],
                'importPfad' => '/data/wissen/handbook',
            ],
        ],
    ],
],
```

**3. Enable the module and import** (in `protected/`, as the web server user):

```bash
php yii module/enable nexus-wissen
php yii wissen/liste                        # shows your collections
php yii wissen/import handbook --probe      # shows what would happen
php yii wissen/import handbook              # imports
```

**4. Open the space.** A new menu entry with the collection's name appears for editors. Check a few articles, then click **"Release for all members"**.

Run the import again whenever the files change.

### All settings of a collection

Texts can be a string or a per-language array `['en' => …, 'de' => …, 'es' => …]`.

| Key | Meaning | Default |
|---|---|---|
| `kreis` | Space ID | required |
| `name` | name in the menu and as heading | "Knowledge" |
| `beschreibung` | line under the heading | – |
| `symbol` | Font Awesome icon name | `book` |
| `emoji` | in front of messages and posts | 📖 |
| `suchbeispiel` | placeholder in the search field | "Search term or keyword" |
| `hinweis` | notice box under every article | none |
| `themen` | starting categories (order, names, icons): `['01_Basics' => ['en' => 'Basics', 'symbol' => 'star']]`; taken over once, afterwards edited in the portal | from folder names |
| `importPfad` | import folder | – |
| `importStatus` | import only manifest entries with this `status` | all |
| `menuePosition` | sort order in the space menu | `105` (below "Stream") |
| `kiBeschreibung` | what the collection is about, for AI keywords | the name |
| `kiAnweisung` | your own complete instruction for AI keywords | built in |

### Keywords by AI (optional)

With [`nexus-community-assistant`](../nexus-community-assistant) and [`nexus-translate`](../nexus-translate) (Google Gemini) installed, the AI can suggest keywords and search terms. It never changes the article text.

```bash
php yii wissen/schlagworte handbook --probe=3   # show suggestions for 3 articles, save nothing
php yii wissen/schlagworte handbook             # all articles without keywords
php yii wissen/schlagworte handbook --neu       # all articles again
```

Without AI, add keywords in the file header, in the manifest or on the edit page.

### Messages

Suggestions, decisions and review reminders are sent as private messages through [`nexus-arbeitszeit`](../nexus-arbeitszeit). Without that module, suggestions are still stored and appear in the collection's suggestion list.

**Requirements:** HumHub 1.18+. The interface is available in English, German and Spanish; articles stay in their original language.

## Deutsch

**Wissenssammlungen:** Nachschlagewerke im Stil von Wikipedia in Kreisen (Spaces). Artikel als Markdown-Dateien schreiben oder sammeln, einspielen, fertig: Handbuch, Rezeptsammlung, Pflanzenführer, Glossar, häufige Fragen, Projektdokumentation. Jeder Kreis kann eine eigene Sammlung bekommen, auch mehrere.

**Für Mitglieder:**
- **Suche mit Alltagswörtern:** Jeder Artikel trägt sichtbare Schlagworte und unsichtbare Suchwörter. Bei mehreren Wörtern stehen Artikel vorn, die alle treffen; Wortendungen spielen keine Rolle.
- **Themen und Schlagworte** zum Stöbern.
- **Artikelseiten** mit Inhaltsverzeichnis, automatischen Querverweisen, Infokasten und „Siehe auch“.
- **Hinweis** unter jedem Artikel (frei wählbar, z. B. „ersetzt keinen ärztlichen Rat“).
- **„Ich möchte etwas ergänzen / ändern“** schickt einen Vorschlag an die Redaktion. **„Im Kreis darüber sprechen“** legt einen Beitrag im Kreis an.

**Für die Redaktion** (Systemadmins sowie Besitzer und Admins des Kreises):
- Wissensbereich selbst anlegen: im Kreis oben rechts im Einstellungsmenü „Wissensbereich anlegen“ (solange der Kreis noch keinen hat).
- Kategorien im Portal anlegen, umbenennen, sortieren, leere löschen. Die Themen aus `common.php` werden beim ersten Aufruf übernommen, danach gilt nur noch das Portal.
- Neue Artikel schreiben oder eine Datei hochladen (.docx, .md, .txt). Jeder neue Artikel ist zuerst ein **Entwurf**, den nur die Redaktion sieht, bis jemand auf „Veröffentlichen“ klickt.
- Versionsgeschichte, Vorschlagsliste mit Antwort an die Person.
- Freigabe-Knopf: Bis zur Freigabe sieht nur die Redaktion die Sammlung.
- Erinnerung bei fälliger Prüfung (Feld „Nächste Prüfung“).
- Der Import ist wiederholbar und überschreibt im Portal bearbeitete Artikel **nie**.

**Schritt für Schritt:**
1. **Ordner mit Markdown-Dateien** auf dem Server anlegen. Jede `.md`-Datei wird ein Artikel, der erste Unterordner ist das Thema. Der Titel ist die erste `# Überschrift`. Querverweise sind normale Links wie `[Profil](Profil.md)`. Angaben wie Titel, Thema, Schlagworte und Prüfdatum können oben in der Datei stehen (`titel:`, `thema:`, `schlagworte: [a, b]`, `suchwoerter:`, `pruefdatum:`, `wiedervorlage:`). Optional steuert eine `manifest.json`, welche Dateien übernommen werden.
2. **Sammlung eintragen** in `protected/config/common.php` unter `'modules' => ['nexus-wissen' => ['sammlungen' => ['kennung' => ['kreis' => …, 'name' => …, 'importPfad' => …]]]]`. Alle Einstellungen stehen in der Tabelle oben.
3. **Einspielen:** `php yii wissen/import <kennung> --probe`, danach ohne `--probe`.
4. **Im Kreis prüfen** und auf **„Für alle Mitglieder freigeben“** klicken.

**KI-Schlagworte (optional):** `php yii wissen/schlagworte <kennung> --probe=3`, danach ohne `--probe`. Braucht `nexus-community-assistant` und `nexus-translate`.
