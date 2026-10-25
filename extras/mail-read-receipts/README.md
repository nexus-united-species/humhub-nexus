# mail-read-receipts

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Lesestatus für private Nachrichten.** Kein eigenes Modul, sondern eine **Änderung an zwei Dateien des offiziellen HumHub-Moduls [„Mail“](https://github.com/humhub/mail)** (AGPL): Unter der jeweils letzten eigenen Nachricht einer Unterhaltung steht „Gelesen“, „Noch nicht gelesen“ oder „Noch nicht gelesen von: …“. Genutzt wird die Spalte `user_message.last_viewed`, die das Mail-Modul ohnehin führt.

**Dazu: Antwortadresse** (`models/MessageNotification.php`): Die E-Mail-Benachrichtigung über neue Nachrichten beachtet im Original die HumHub-Einstellung „Antwortadresse für System-E-Mails“ (`mailerSystemEmailReplyTo`) nicht – ein Klick auf „Antworten“ landet sonst im Postfach des Absenders. Ergänzt wie im HumHub-Kern (`notification/targets/MailTarget.php`).

**Einspielen:** die Dateien über die gleichnamigen Dateien in `modules/mail/widgets/` bzw. `modules/mail/models/` legen (vorher sichern) und den Zwischenspeicher leeren.

**Achtung:** Ein Update des Mail-Moduls überschreibt die Änderung. Vor einem Update prüfen, ob sich `ConversationEntry.php` / `views/conversationEntry.php` im Original geändert haben, und die Änderung danach neu übertragen. Getestet mit Mail-Modul 3.3.13 und 3.3.15 (die drei Dateien sind in beiden Versionen im Original gleich). Die Texte sind fest Deutsch.

## English

**Read receipts for private messages.** Not a module of its own but a **change to two files of the official HumHub [Mail](https://github.com/humhub/mail) module** (AGPL): below the last own message of a conversation it shows "Read", "Not read yet" or "Not read yet by: …". It uses the column `user_message.last_viewed`, which the mail module maintains anyway.

**Plus: reply-to address** (`models/MessageNotification.php`): the original e-mail notification about new messages ignores HumHub's setting "reply-to address for system e-mails" (`mailerSystemEmailReplyTo`) – clicking "Reply" otherwise goes to the sender mailbox. Added the same way as in HumHub core (`notification/targets/MailTarget.php`).

**Installation:** copy the files over the files of the same name in `modules/mail/widgets/` and `modules/mail/models/` (back them up first) and flush the cache.

**Caution:** updating the mail module overwrites this change. Before updating, check whether `ConversationEntry.php` / `views/conversationEntry.php` changed upstream, and re-apply the change afterwards. Tested with mail module 3.3.13 and 3.3.15 (the three files are identical upstream in both). The texts are German only.
