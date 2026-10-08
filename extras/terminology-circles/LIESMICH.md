# HumHub-Wort-Ueberschreibungen

Kopie von `/mnt/data/humhub/humhub-data/config/messages/` (im Container `/data/config/messages/`).
HumHub legt diese Dateien ueber seine eigenen Uebersetzungen (`ModuleMessageSource::loadMessages`).

- `de/` — "Space" heisst "Kreis" (12.09.2026, Sektor → Kreis).
- `en-US/`, `es/` — dasselbe auf Englisch ("Circle") und Spanisch ("Círculo"), 25.09.2026.
  Erzeugt aus den Schluesseln von `de/`: Englisch aus dem Originaltext, Spanisch aus HumHubs
  eigener spanischer Uebersetzung mit Espacio → Círculo. Platzhalter wie `{spaceName}` bleiben
  unveraendert.

Nach einer Aenderung: Datei auf den Server kopieren, `www-data` als Eigentuemer, Cache leeren
(`php /opt/humhub/protected/yii cache/flush-all`).

Ergaenzt 25.09.2026:
- `*/ActivityModule.base.php` — Aktivitaeten wie "ist dem Space beigetreten" kamen aus dem
  Aktivitaets-Modul, nicht aus dem Space-Modul, und standen deshalb noch auf Space/espacio.
- `es/ContentModule.activities.php`, `es/LikeModule.{activities,notifications}.php` — HumHubs
  Spanisch schrieb "ha creado un nuevo Entrada" (falsches Geschlecht). Da die Inhaltsart wechselt
  (Entrada, Encuesta, Archivo, ...), neutral mit Doppelpunkt: "ha publicado: Entrada ...",
  "A X le gusta: Entrada ...".

Ergaenzt 02.10.2026:
- `*/SharebetweenModule.base.php` — HumHubs "Teilen" (in einen anderen Kreis) heisst "In Kreis teilen",
  damit es sich vom neuen "Weitergeben" (Modul nexus-teilen, nach draussen) unterscheidet.
