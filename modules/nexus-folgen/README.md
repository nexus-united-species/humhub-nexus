# nexus-folgen

🇬🇧 [English](#english) · 🇩🇪 [Deutsch](#deutsch)

## English

**New accounts automatically follow selected spaces.**

HumHub's dashboard only shows content from spaces you are a member of or follow. If your working groups are readable for all logged-in users but only members may post, nobody notices what happens there unless they visit each space. Following is exactly the middle ground: content appears on the dashboard, posting and commenting stay reserved for members.

- New accounts follow the configured spaces automatically, **without notifications** (no bell, no e-mail).
- Members of a space are skipped; existing follow settings are kept.
- Everyone can click "Unfollow" in a space at any time.
- `php yii folgen/alle [--probe]` applies it once to existing accounts (note: this also re-follows people who unfollowed in the meantime).

**Settings** (`protected/config/common.php`):

```php
'modules' => ['nexus-folgen' => [
    'kreise' => [5, 6, 7],   // space IDs
    'ausnahmen' => [29],     // optional: user IDs that never follow automatically (e.g. bot accounts)
]],
```

Enable the module **before** adding the settings entry. **Requirements:** HumHub 1.18+.

## Deutsch

**Neue Konten folgen automatisch ausgewählten Kreisen.** Die Startseite zeigt bei HumHub nur Beiträge aus Kreisen, in denen man Mitglied ist oder denen man folgt. Folgen bedeutet: Beiträge auf der Startseite sehen, schreiben und kommentieren erst als Mitglied. Ohne Benachrichtigungen; Mitglieder werden übersprungen; jeder kann jederzeit „Nicht mehr folgen“ klicken. Einmalig für bestehende Konten: `php yii folgen/alle` (vorher mit `--probe` zählen). Einstellungen siehe oben (`kreise`, `ausnahmen`).
