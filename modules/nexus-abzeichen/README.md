# nexus-abzeichen

🇬🇧 [English](#english) · 🇩🇪 [Deutsch](#deutsch)

## English

**Badges and moments – recognition without competition.**

Many gamification systems reward with points and rankings. That turns voluntary work into a contest. This module does the opposite:

- **Badges (self-declared):** members read a text, for example a pledge or a code of conduct, and commit with one click. The badge appears as a **coloured frame and a small seal around their profile picture everywhere on the platform**. It can be removed at any time with one click, without questions. Only "shows the badge: yes/no" is stored: **no date, no number, no order**, so nobody stands above anyone else.
- **Moments (private):** when someone reaches something, a short golden celebration appears and a quiet counter grows on their own badge page. **Only they can see it.** No rankings, no red marks, no losing streaks: a quieter week is fine. Two kinds are built in:
  - `aktivzeit`: weeks with at least *n* active minutes on the platform (time spent there, not work).
  - `arbeitsstunden`: the sum of working hours **approved by an admin** in the module `nexus-arbeitszeit` (if installed). It is celebrated at each new level.
- **Levels:** every moment can have levels (threshold → name). The badge page shows reached and upcoming levels. Values never go down.
- **Sharing is optional:** a celebration can be shared with one click as the member's own post in a chosen space (`teilenKreis`), with a few words of their own if they like. Nothing is shared automatically, and the same level can only be shared once.

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
            'name' => ['en' => 'Time on the platform'],
            'einheit' => ['en' => 'week|weeks'],                  // singular|plural
            'feier' => ['en' => 'Great to have you here! You spent 2 hours here this week.'],
            'stufen' => [4 => ['en' => 'Regular visitor'], 12 => ['en' => 'Familiar face']],
            'teilenText' => ['en' => '🎉 {wert} {einheit} with 2 hours each on the platform!'],
            'symbol' => 'compass',
        ],
        'work' => [
            'art' => 'arbeitsstunden',     // approved hours from nexus-arbeitszeit
            'name' => ['en' => 'Active contribution'],
            'einheit' => ['en' => 'hour|hours'],
            'feier' => ['en' => 'Thank you! {wert} approved hours – new level: {stufe}'],
            'stufen' => [10 => ['en' => 'First building blocks'], 50 => ['en' => 'Busy hands']],
            'teilenText' => ['en' => '🎉 I have contributed {wert} hours – level “{stufe}”!'],
            'symbol' => 'handshake-o',
        ],
    ],
    'teilenKreis' => 4,                    // optional: space ID for sharing a celebration
    'ausnahmen' => [29],                   // optional: user IDs without time counting (bots)
    'wochenAufbewahren' => 8,
]],
```

Add new badges or moments by adding entries; no code change is needed for badges. Placeholders in `feier` and `teilenText`: `{wert}`, `{einheit}`, `{stufe}`. New kinds of moments call `Aktivzeit::momentErreicht($userId, $kennung)` (count +1) or `Aktivzeit::wertAnheben($userId, $kennung, $wert)` (set a higher value). The celebration and the private counter then work automatically. Members find all badges under **profile menu → Badges**.

Enable the module **before** adding the settings entry. **Requirements:** HumHub 1.18+. Interface in English, German and Spanish.

## Deutsch

**Abzeichen und Momente – Anerkennung ohne Wettbewerb.**

- **Abzeichen (selbst erklärt):** Text lesen, mit einem Klick bekennen. Es erscheint ein farbiger Rahmen mit Siegel am Profilbild, überall im Portal, und lässt sich jederzeit lösen. Gespeichert wird nur ja/nein, ohne Datum, Nummer oder Reihenfolge.
- **Momente (privat):** kurze goldene Feier und ein stiller Zähler, den nur der Mensch selbst sieht. Keine Rangliste, kein Rot, kein Verlust. Zwei Arten: `aktivzeit` (Wochen mit mindestens *n* aktiven Minuten im Portal, also Zeit im Portal, nicht Arbeit) und `arbeitsstunden` (von einem Admin freigegebene Stunden aus `nexus-arbeitszeit`, gefeiert wird jede neue Stufe).
- **Stufen** je Moment (Schwelle → Name), sichtbar auf der eigenen Abzeichen-Seite. Werte sinken nie.
- **Teilen nur auf Wunsch:** eine Feier lässt sich mit einem Klick als eigener Beitrag im eingestellten Kreis (`teilenKreis`) teilen, auf Wunsch mit eigenen Worten. Derselbe Stand nur einmal.
- **Aktive Zeit** = echte Seitenaufrufe und Absenden. Hintergrund-Abfragen zählen nicht, Pausen über 10 Minuten nur als 1 Minute. Die Wochenzähler werden nach einigen Wochen gelöscht und sind für niemanden sonst sichtbar, auch nicht für Admins.
- **Einstellungen** siehe oben (`abzeichen`, `momente`, `ausnahmen`, `teilenKreis`, `wochenAufbewahren`). Neue Abzeichen kommen als weiterer Eintrag dazu. Mitglieder finden sie im Profilmenü unter „Abzeichen“.
