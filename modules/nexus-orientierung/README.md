# nexus-orientierung

🇬🇧 [English](#english) · 🇩🇪 [Deutsch](#deutsch)

## English

**Finding your way more easily.** Two small helps, built at the request of a member who has limited eyesight:

- **Back to the same place:** HumHub reloads the "Latest activities" box on the dashboard from the top when you come back with the browser's back button, showing only the first 10 entries. With this module, the clicked entry is remembered (only in this browser tab), the box loads more entries until it is there again, scrolls to it and highlights it briefly.
- **Posts & comments:** a new item in every profile menu. The profile stream shows posts, but not comments, although many members mainly write comments. This page lists both, newest first, 50 per page, with place, time and a short excerpt. It only shows what the reader is allowed to see anyway (the same `Content::canView()` check as everywhere else), only published content, no drafts and nothing from the trash.

**Settings** (optional, `protected/config/common.php`):

```php
'modules' => ['nexus-orientierung' => ['proSeite' => 50]],
```

**Requirements:** HumHub 1.18+. Interface in English, German and Spanish.

## Deutsch

- **Zurück an dieselbe Stelle:** Wer in „Letzte Aktivitäten“ einen Eintrag anklickt und mit „Zurück“ wiederkommt, landet wieder bei genau diesem Eintrag. Er wird kurz hervorgehoben. Gemerkt wird nur im Browser-Tab.
- **Beiträge & Kommentare:** neuer Punkt im Profilmenü jedes Menschen. Beiträge und Kommentare, neueste zuerst, je 50, nur was der Lesende ohnehin sehen darf.
