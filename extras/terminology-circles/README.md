# terminology-circles

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**„Kreis“ statt „Space“:** Wort-Überschreibungen für HumHubs Oberfläche in Deutsch („Kreis“), Englisch („Circle“) und Spanisch („Círculo“). HumHub legt Dateien aus `protected/config/messages/<sprache>/` über seine eigenen Übersetzungen – ohne Eingriff in den Kern. Dazu ein paar Korrekturen an HumHubs spanischer Übersetzung (falsches Geschlecht bei „ha creado un nuevo Entrada“).

**Einspielen:** die Ordner `de/`, `en-US/`, `es/` nach `protected/config/messages/` kopieren (im offiziellen Docker-Image `/data/config/messages/`) und den Zwischenspeicher leeren. Details und Herkunft der Dateien: [LIESMICH.md](LIESMICH.md).

## English

**"Circle" instead of "Space":** wording overrides for HumHub's interface in German ("Kreis"), English ("Circle") and Spanish ("Círculo"). HumHub layers files from `protected/config/messages/<language>/` over its own translations – no core changes. Also includes a few fixes to HumHub's Spanish translation (wrong gender in "ha creado un nuevo Entrada").

**Installation:** copy the folders `de/`, `en-US/`, `es/` to `protected/config/messages/` (official Docker image: `/data/config/messages/`) and flush the cache. Details (German): [LIESMICH.md](LIESMICH.md).
