# nexus-abzeichen

🇬🇧 [English](#english) · 🇩🇪 [Deutsch](#deutsch)

## English

**Badges and moments – recognition without competition.**

Many gamification systems reward with points and rankings. That turns voluntary work into a contest. This module does the opposite:

- **Badges (self-declared):** members read a text, for example a pledge or a code of conduct, and commit with one click. The badge appears as a **coloured frame and a small seal around their profile picture everywhere on the platform**. It can be removed at any time with one click, without questions. Only "shows the badge: yes/no" is stored: **no date, no number, no order**, so nobody stands above anyone else.
- **Moments (private):** when someone reaches something, for example **2 active hours on the platform in a week**, a short golden celebration appears and a quiet counter grows on their own badge page. **Only they can see it.** No rankings, no red marks, no losing streaks: a quieter week is fine.

**How active time is measured:** real page views (including navigation without reload) and form submissions. Background requests do not count, and gaps longer than 10 minutes count as 1 minute, so an open but unused tab does not add time. Weekly counters are deleted after a few weeks and are not visible to anyone else, including admins.

**Settings** (`protected/config/common.php`), all texts as a string or `['en' => …, 'de' => …, 'es' => …]`:

```php
'modules' => ['nexus-abzeichen' => [
    'abzeichen' => [
        'pledge' => [
            'name' => ['en' => 'Our pledge'],
            'kurz' => ['en' => 'Three promises to yourself and the future'],
            'textDatei' => '/data/abzeichen/pledge.md',   // or 'text' => 'Markdown …'
            'farbe' => '#D4AF37',                         // frame colour, default gold
            'symbol' => 'star',                           // Font Awesome name for the seal
        ],
    ],
    'momente' => [
        'two-hours' => [
            'art' => 'aktivzeit',          // active minutes per calendar week
            'minutenProWoche' => 120,
            'name' => ['en' => 'Weeks of building'],
            'feier' => ['en' => 'Thank you! Your 2 hours for this week are done.'],
            'symbol' => 'trophy',
        ],
    ],
    'ausnahmen' => [29],                   // optional: user IDs without time counting (bots)
    'wochenAufbewahren' => 8,
]],
```

Add new badges or moments by adding entries; no code change is needed for badges. New kinds of moments (other than `aktivzeit`) call `Aktivzeit::momentErreicht($userId, $kennung)`. The celebration and the private counter then work automatically. Members find all badges under **profile menu → Badges**.

Enable the module **before** adding the settings entry. **Requirements:** HumHub 1.18+. Interface in English, German and Spanish.

## Deutsch

**Abzeichen und Momente – Anerkennung ohne Wettbewerb.**

- **Abzeichen (selbst erklärt):** Text lesen, mit einem Klick bekennen. Es erscheint ein farbiger Rahmen mit Siegel am Profilbild, überall im Portal, und lässt sich jederzeit lösen. Gespeichert wird nur ja/nein, ohne Datum, Nummer oder Reihenfolge.
- **Momente (privat):** kurze goldene Feier, zum Beispiel nach 2 aktiven Stunden im Portal in einer Woche, dazu ein stiller Zähler, den nur der Mensch selbst sieht. Keine Rangliste, kein Rot, kein Verlust.
- **Aktive Zeit** = echte Seitenaufrufe und Absenden. Hintergrund-Abfragen zählen nicht, Pausen über 10 Minuten nur als 1 Minute. Die Wochenzähler werden nach einigen Wochen gelöscht und sind für niemanden sonst sichtbar, auch nicht für Admins.
- **Einstellungen** siehe oben (`abzeichen`, `momente`, `ausnahmen`, `wochenAufbewahren`). Neue Abzeichen kommen als weiterer Eintrag dazu. Mitglieder finden sie im Profilmenü unter „Abzeichen“.
