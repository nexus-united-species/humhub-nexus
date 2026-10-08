# nexus-voice

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Sprach- und Videonachrichten:** Mikrofon- und Kamera-Knopf in Beiträgen, Kommentaren und privaten Nachrichten. Sprachnachricht bis 5 Minuten, Videonachricht bis 3 Minuten (runder Abspieler). Beides wird im Browser aufgenommen und als normaler Dateianhang hochgeladen.

Optional mit **Mitschrift:** Die Aufnahme geht an einen eigenen Whisper-Dienst im selben Server-Netz (siehe [`extras/nexus-whisper`](../../extras/nexus-whisper)) – sie verlässt den Server nicht.

**Voraussetzungen:** HumHub 1.18+, HTTPS (Browser erlauben Mikrofon/Kamera nur verschlüsselt). Für die Mitschrift: laufender `nexus-whisper`-Dienst.

**Einstellungen:**

```php
// protected/config/common.php -- nur nötig, wenn der Dienst anders erreichbar ist
'modules' => ['nexus-voice' => ['mitschriftUrl' => 'http://nexus-whisper:8000/mitschrift']],
```

```bash
# gemeinsamer Schlüssel mit dem Whisper-Dienst (WHISPER_TOKEN) -- ohne ihn keine Mitschrift
php yii settings/set nexus-voice whisperToken "<schluessel>"
```

## English

**Voice and video messages:** microphone and camera buttons in posts, comments and private messages. Voice messages up to 5 minutes, video messages up to 3 minutes (round player). Both are recorded in the browser and uploaded as a normal attachment.

Optional **transcription:** the recording is sent to your own Whisper service on the same server network (see [`extras/nexus-whisper`](../../extras/nexus-whisper)) – it never leaves your server.

**Requirements:** HumHub 1.18+, HTTPS (browsers only allow microphone/camera over encrypted connections). For transcription: a running `nexus-whisper` service.

**Settings:** `mitschriftUrl` in `protected/config/common.php` (only if the service runs elsewhere) and the shared token as module setting `whisperToken` (`php yii settings/set nexus-voice whisperToken "<token>"`). Without the token, transcription is disabled.
