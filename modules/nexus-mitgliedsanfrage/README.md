# nexus-mitgliedsanfrage

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Kennenlern-Antworten neuer Mitglieder an die Admins:** Wer sich registriert, beantwortet beim Identitätsanbieter (bei uns Authentik) vier kurze Fragen. Beim ersten Login liest das Modul die Antworten aus der Anmeldung und schickt sie allen Systemadmins als Nachricht – genau einmal je Mensch. Keine Prüfung vor der Freischaltung: Der Mensch ist sofort drin. Optional wird er beim allerersten Betreten in einen Willkommens-Kreis geleitet.

**Voraussetzungen:** HumHub 1.18+ mit Anmeldung über OpenID Connect, **`nexus-arbeitszeit`** (verschickt die Nachrichten). Der Identitätsanbieter muss eine Angabe `nexus_anfrage` mitliefern – ein Objekt mit den Schlüsseln `aufmerksam`, `erwartung`, `faehigkeiten`, `einbringen`. Fehlt sie, passiert nichts.

**Einstellungen** (`protected/config/common.php`, optional):

```php
'modules' => ['nexus-mitgliedsanfrage' => ['willkommenKreis' => 2]],  // Space-ID; leer = normales Ziel nach dem Login
```

## English

**Getting-to-know-you answers of new members to the admins:** when people register, they answer four short questions at the identity provider (Authentik in our case). On their first login, the module reads the answers from the login data and sends them to all system admins as a message – exactly once per person. There is no approval step: the person is in immediately. Optionally, they are taken to a welcome circle on their very first visit.

**Requirements:** HumHub 1.18+ with OpenID Connect login, **`nexus-arbeitszeit`** (sends the messages). The identity provider must deliver a claim `nexus_anfrage` – an object with the keys `aufmerksam`, `erwartung`, `faehigkeiten`, `einbringen`. Without it, nothing happens.

**Settings:** `willkommenKreis` (space ID) in `protected/config/common.php` – optional.
