# HumHub 1.19 – was vor dem Update angepasst werden muss / what needs to change before upgrading

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

Stand / as of: 2026-10-09, HumHub 1.19.0-beta.3. Quelle / source:
[`docs/develop/module-migrate-1.19.md`](https://github.com/humhub/humhub/blob/develop/docs/develop/module-migrate-1.19.md).
Die Module laufen derzeit unter HumHub **1.18**. / The modules currently run on HumHub **1.18**.

## Deutsch

| # | Änderung in 1.19 | Betroffen | Was zu tun ist |
|---|---|---|---|
| 1 | `$file->store->get()` liefert einen Pfad **relativ zum Datenspeicher**, keinen echten Dateipfad mehr. `is_file()`, `file_get_contents()`, `sendFile()` usw. schlagen still fehl. | `nexus-markdown-preview` (PreviewController), `nexus-teilen` (LesenController::actionDatei, services/Standbild), `nexus-video-faststart` (Events) | Lesen über `$file->store->getContent()` / `getContentStream()`, Ausliefern über `sendStreamAsFile()`. Für ffmpeg (Standbild, Video-Schnellstart) wird ein lokaler Pfad gebraucht: nur bei lokalem Datenspeicher (`LocalMountConfig`) den Pfad zusammensetzen, sonst Funktion abschalten. |
| 2 | Kommentare sind nicht mehr polymorph: `comment.content_id` und `comment.parent_comment_id` statt `object_model`/`object_id`. | `nexus-community-assistant` (MentionReplyService, PosterService, WeeklySummaryService) | Abfragen und das Anlegen von Antworten auf die neuen Spalten umstellen. |
| 3 | `RichTextToShortTextConverter` liefert **unkodierten** Text. | `nexus-teilen` (services/Inhalt::kurztext), `nexus-translate` (TranslateController::actionAktivitaeten) | Wo der Text in HTML landet: `RichTextToShortHtmlConverter` oder `Html::encode()` – sonst XSS. |
| 4 | Tabelle `activity` umgebaut (`content_id` statt `object_model`/`object_id`). | `nexus-translate` (actionAktivitaeten, `Activity::getSource()`) | Mit 1.19 prüfen, ob `getSource()` weiter den Inhalt liefert. |
| 5 | Anmeldung über OpenID Connect (z. B. Authentik): `AuthController` wertet `rememberMe` nicht mehr aus – SSO-Anmeldungen gelten nur noch für die Browsersitzung. | `extras/nexus-theme` (Anmelde-Links mit `rememberMe=1`) | Andere Lösung für „angemeldet bleiben“ finden (z. B. Sitzungsdauer in der Konfiguration). |
| 6 | `getProfileImage()` veraltet (funktioniert noch), `getProfileBannerImage()` entfernt. | `nexus-karte` (KartenDaten), `nexus-community-assistant` (WillkommensService) | Auf `getImage()` umstellen. |
| 7 | Aliase `@webroot-static` / `@web-static` und der Webordner `static/` entfallen; Theme-Ressourcen werden anders veröffentlicht. | `extras/nexus-theme` (Bild auf der Gast-Seite, Theme-Bau) | Mit 1.19 prüfen; Theme neu bauen. |
| 8 | Migrationen: `humhub\components\Migration::createTable()` übernimmt die Collation der `user`-Tabelle. | alle Module | Erledigt: alle Migrationen nutzen `humhub\components\Migration`. |

**Empfehlung:** vor dem Update eine Testinstanz mit 1.19 aufsetzen, die Module dort einspielen und die Punkte 1–7 durchgehen.

## English

| # | Change in 1.19 | Affected | What to do |
|---|---|---|---|
| 1 | `$file->store->get()` returns a path **relative to the data mount**, no longer a local file path; `is_file()`, `file_get_contents()`, `sendFile()` etc. fail silently. | `nexus-markdown-preview` (PreviewController), `nexus-teilen` (LesenController::actionDatei, services/Standbild), `nexus-video-faststart` (Events) | Read via `$file->store->getContent()` / `getContentStream()`, serve via `sendStreamAsFile()`. ffmpeg (video stills, faststart) needs a local path: only build it for a local data mount (`LocalMountConfig`), otherwise disable the feature. |
| 2 | Comments are no longer polymorphic: `comment.content_id` and `comment.parent_comment_id` replace `object_model`/`object_id`. | `nexus-community-assistant` (MentionReplyService, PosterService, WeeklySummaryService) | Migrate queries and reply creation to the new columns. |
| 3 | `RichTextToShortTextConverter` returns **unencoded** text. | `nexus-teilen` (services/Inhalt::kurztext), `nexus-translate` (TranslateController::actionAktivitaeten) | Where rendered into HTML: use `RichTextToShortHtmlConverter` or `Html::encode()` – otherwise XSS. |
| 4 | `activity` table restructured (`content_id` instead of `object_model`/`object_id`). | `nexus-translate` (actionAktivitaeten, `Activity::getSource()`) | Verify `getSource()` still resolves the content on 1.19. |
| 5 | OpenID Connect login (e.g. Authentik): `AuthController` no longer evaluates `rememberMe` – SSO logins last for the browser session only. | `extras/nexus-theme` (login links with `rememberMe=1`) | Find another way to keep users signed in (e.g. session lifetime configuration). |
| 6 | `getProfileImage()` deprecated (still works), `getProfileBannerImage()` removed. | `nexus-karte` (KartenDaten), `nexus-community-assistant` (WillkommensService) | Switch to `getImage()`. |
| 7 | Aliases `@webroot-static` / `@web-static` and the webroot `static/` folder are gone; theme resources are published differently. | `extras/nexus-theme` (guest page image, theme build) | Verify on 1.19; rebuild the theme. |
| 8 | Migrations: `humhub\components\Migration::createTable()` adopts the collation of the `user` table. | all modules | Done: all migrations extend `humhub\components\Migration`. |

**Recommendation:** set up a 1.19 test instance before upgrading, deploy the modules there and work through items 1–7.
