# nexus-video-faststart

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Videos starten sofort:** Nach jedem Video-Upload wird das „Inhaltsverzeichnis“ der Datei (`moov`-Atom) an den Anfang verschoben – Desktop-Browser brechen sonst bei großen Dateien ab, bevor sie zu spielen beginnen. Videos, die Desktop-Browser nicht abspielen können (HDR/10 Bit, HEVC), werden in normales H.264 umgewandelt; Videonachrichten aus `nexus-voice` (WebM) werden zu MP4.

**Voraussetzungen:** HumHub 1.18+, `ffmpeg` und `ffprobe` (z. B. statische Builds). Fehlt eines davon, tut das Modul nichts.

**Einstellungen** (`protected/config/common.php`, nur wenn die Programme woanders liegen):

```php
'modules' => ['nexus-video-faststart' => ['ffmpeg' => '/usr/bin/ffmpeg', 'ffprobe' => '/usr/bin/ffprobe']],
```

Standard: `/data/bin/ffmpeg` und `/data/bin/ffprobe` (Datenordner des offiziellen HumHub-Docker-Images).

## English

**Videos start immediately:** after every video upload, the file's index (`moov` atom) is moved to the beginning – otherwise desktop browsers give up on large files before playback starts. Videos that desktop browsers cannot play (HDR/10-bit, HEVC) are converted to standard H.264; video messages from `nexus-voice` (WebM) become MP4.

**Requirements:** HumHub 1.18+, `ffmpeg` and `ffprobe` (e.g. static builds). If either is missing, the module does nothing.

**Settings:** `ffmpeg` / `ffprobe` paths in `protected/config/common.php` (default `/data/bin/ffmpeg`, `/data/bin/ffprobe`).
