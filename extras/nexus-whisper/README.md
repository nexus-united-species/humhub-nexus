# nexus-whisper

🇩🇪 [Deutsch](#deutsch) · 🇬🇧 [English](#english)

## Deutsch

**Interner Mitschrift-Dienst für [`nexus-voice`](../../modules/nexus-voice):** Ein kleiner Python-Dienst mit [faster-whisper](https://github.com/SYSTRAN/faster-whisper), der Sprachnachrichten in Text umwandelt. Er läuft als eigener Container im Docker-Netz des Portals **ohne veröffentlichten Port** – nur der HumHub-Container erreicht ihn, die Aufnahmen verlassen den Server nie. Es wird immer nur eine Aufnahme zur Zeit verarbeitet.

Schnittstelle: `POST /mitschrift`, Audiodaten im Körper, Kopfzeile `X-Token`. Antwort: `{"text": …, "sprache": "de", "dauer": …, "rechenzeit": …}`.

**Einrichtung:**

1. Ein Docker-Image mit Python, `faster-whisper` und `PyAV` bereitstellen (wir nutzen unser eigenes `cockpit-cockpit`, in `docker-compose.yml` anpassen).
2. Ein Whisper-Modell (bei uns `small`) in den Ordner `modell/` legen – der Dienst lädt nichts aus dem Internet (`HF_HUB_OFFLINE=1`).
3. `WHISPER_TOKEN` in eine `.env` neben `docker-compose.yml` schreiben und denselben Wert im Portal setzen: `php yii settings/set nexus-voice whisperToken "<token>"`.
4. Den Netzwerknamen (`humhub_default`) an das eigene Docker-Netz des Portals anpassen, dann `docker compose up -d`.

## English

**Internal transcription service for [`nexus-voice`](../../modules/nexus-voice):** a small Python service using [faster-whisper](https://github.com/SYSTRAN/faster-whisper) that turns voice messages into text. It runs as its own container on the portal's Docker network **without a published port** – only the HumHub container can reach it, and recordings never leave the server. It processes one recording at a time.

Interface: `POST /mitschrift`, audio data in the body, header `X-Token`. Response: `{"text": …, "sprache": "de", "dauer": …, "rechenzeit": …}`.

**Setup:** provide a Docker image with Python, `faster-whisper` and `PyAV` (adjust `image:` in `docker-compose.yml`); put a Whisper model (we use `small`) into `modell/` – the service never downloads anything; write `WHISPER_TOKEN` to a `.env` file and set the same value in the portal (`php yii settings/set nexus-voice whisperToken "<token>"`); adapt the network name (`humhub_default`) to your portal's Docker network; `docker compose up -d`.
