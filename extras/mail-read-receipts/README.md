# mail-read-receipts

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Lesestatus für private Nachrichten.** Kein eigenes Modul, sondern eine **Änderung an zwei Dateien des offiziellen HumHub-Moduls [„Mail“](https://github.com/humhub/mail)** (AGPL): Unter der jeweils letzten eigenen Nachricht einer Unterhaltung steht „Gelesen“, „Noch nicht gelesen“ oder „Noch nicht gelesen von: …“. Genutzt wird die Spalte `user_message.last_viewed`, die das Mail-Modul ohnehin führt.

**Einspielen:** die beiden Dateien über die gleichnamigen Dateien in `modules/mail/widgets/` legen (vorher sichern) und den Zwischenspeicher leeren.

**Achtung:** Ein Update des Mail-Moduls überschreibt die Änderung. Vor einem Update prüfen, ob sich `ConversationEntry.php` / `views/conversationEntry.php` im Original geändert haben, und die Änderung danach neu übertragen. Getestet mit der Mail-Modul-Version aus HumHub 1.18. Die Texte sind fest Deutsch.

## English

**Read receipts for private messages.** Not a module of its own but a **change to two files of the official HumHub [Mail](https://github.com/humhub/mail) module** (AGPL): below the last own message of a conversation it shows "Read", "Not read yet" or "Not read yet by: …". It uses the column `user_message.last_viewed`, which the mail module maintains anyway.

**Installation:** copy the two files over the files of the same name in `modules/mail/widgets/` (back them up first) and flush the cache.

**Caution:** updating the mail module overwrites this change. Before updating, check whether `ConversationEntry.php` / `views/conversationEntry.php` changed upstream, and re-apply the change afterwards. Tested with the mail module shipped for HumHub 1.18. The texts are German only.
