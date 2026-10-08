# nexus-theme

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Das Theme der N.E.X.U.S.-Community** (Navy/Gold, heller und dunkler Modus) als Vorlage. Neben Farben und Schriftgrößen (`scss/`) überschreibt es einige HumHub-Ansichten:

- `dashboard/…/index_guest.php` – eigene Willkommensseite für Gäste statt der Standard-Startseite (ohne Mitgliederfotos), mit Karte und Schaufenster, wenn `nexus-karte` / `nexus-teilen` aktiv sind.
- `user/widgets/views/accountTopMenu.php` – getrennte Knöpfe „Anmelden“ / „Registrieren“ für Gäste, Sprachumschalter, Direkt-Links und Hilfe für Angemeldete.
- `humhub/widgets/views/topNavigation.php` – keine Navigationsleiste auf der Gast-Startseite.
- `humhub/views/mail/layouts/{html,text}.php` – Hinweis „bitte nicht antworten“ in jeder System-E-Mail.

Bewusst N.E.X.U.S.-spezifisch (Logo, Texte, Anmeldung über Authentik mit `authclient=authentik`). Die Pfade folgen HumHubs Regel für Theme-Überschreibungen (`ThemeViews`). Nach Änderungen an `scss/` das Theme neu bauen (Verwaltung → Darstellung, oder `ThemeHelper::buildCss()`).

## English

**The theme of the N.E.X.U.S. community** (navy/gold, light and dark mode) as a template. Besides colours and font sizes (`scss/`) it overrides a few HumHub views:

- `dashboard/…/index_guest.php` – a custom welcome page for guests instead of the default start page (no member photos), with map and showcase when `nexus-karte` / `nexus-teilen` are active.
- `user/widgets/views/accountTopMenu.php` – separate "Sign in" / "Register" buttons for guests, language switcher, direct links and help for members.
- `humhub/widgets/views/topNavigation.php` – no navigation bar on the guest start page.
- `humhub/views/mail/layouts/{html,text}.php` – a "please do not reply" notice in every system email.

Deliberately N.E.X.U.S.-specific (logo, texts, login via Authentik with `authclient=authentik`). The paths follow HumHub's rule for theme overrides (`ThemeViews`). After changing `scss/`, rebuild the theme (Administration → Appearance, or `ThemeHelper::buildCss()`).
