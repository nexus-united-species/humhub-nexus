# nexus-supporter-bridge

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Ko-fi-Anbindung:** Wer über [Ko-fi](https://ko-fi.com) monatlich unterstützt, bekommt automatisch Zugang zu einem Unterstützer-Kreis (z. B. für `nexus-protected-library`). Ko-fi meldet jede Zahlung an `https://<portal>/nexus-supporter-bridge/webhook/receive`; die Zuordnung zum Portal-Konto läuft über die E-Mail-Adresse. Fälle, die das Modul nicht sicher zuordnen kann, landen zur Entscheidung im Adminbereich; die Admins bekommen eine E-Mail.

Läuft eine Unterstützung aus, entzieht das Modul den Zugang **nie** selbst: aktiv → Karenzzeit → „wartet auf Entscheidung“. Ab dann entscheidet ein Mensch.

**Voraussetzungen:** HumHub 1.18+, ein privater Kreis, ein Ko-fi-Konto mit eingerichtetem Webhook.

**Einstellungen** (Umgebungsvariablen des HumHub-Containers):

| Variable | Bedeutung |
|---|---|
| `KO_FI_VERIFICATION_TOKEN` | Prüfschlüssel aus den Ko-fi-Webhook-Einstellungen (Pflicht) |
| `SUPPORTER_SPACE_ID` | Space-ID des Unterstützer-Kreises (Pflicht) |
| `GRACE_PERIOD_DAYS` | Karenzzeit in Tagen (Standard 7) |

`commands/check_expiry.php` einmal täglich ausführen (als `www-data` im Container). Verwaltung: Verwaltung → Module → Unterstützer-Brücke.

## English

**Ko-fi integration:** people who support you monthly via [Ko-fi](https://ko-fi.com) automatically get access to a supporter circle (e.g. for `nexus-protected-library`). Ko-fi reports every payment to `https://<portal>/nexus-supporter-bridge/webhook/receive`; matching to the portal account uses the email address. Cases the module cannot match reliably are put up for decision in the admin area, and the admins get an email.

When a subscription ends, the module **never** removes access on its own: active → grace period → "awaiting decision". From then on, a human decides.

**Requirements:** HumHub 1.18+, a private circle, a Ko-fi account with a configured webhook.

**Settings:** environment variables `KO_FI_VERIFICATION_TOKEN` (required), `SUPPORTER_SPACE_ID` (required), `GRACE_PERIOD_DAYS` (default 7). Run `commands/check_expiry.php` once a day (as `www-data` inside the container).
