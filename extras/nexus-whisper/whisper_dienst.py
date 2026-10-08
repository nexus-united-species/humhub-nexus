"""Interner Mitschrift-Dienst fuer Sprachnachrichten im Portal (Josh, 26.09.2026:
"den Mitschnitt, so dass er automatisch im Textfeld steht und dann uebersetzt wird").

Laeuft als eigener Container ``nexus-whisper`` im Docker-Netz des Portals
(``humhub_default``), OHNE veroeffentlichten Port -- erreichbar nur fuer den
HumHub-Container, nicht aus dem Internet. Die Aufnahmen verlassen den Server nie.

Nutzt das Cockpit-Image (bringt faster-whisper + PyAV zum Entpacken von M4A/WebM
mit) und das Modell "small" aus /modell (vom PC kopiert, kein Download). "small"
statt "medium": gemessen am 06.08.2026 halb so langsam, bei Fachbegriffen ohnehin
kein Gewinn (siehe CLAUDE.md, Abschnitt Whisper-Modellgroesse).

Schnittstelle: POST /mitschrift, Rohdaten der Audiodatei im Koerper,
Kopfzeile X-Token (gemeinsames Geheimnis mit dem HumHub-Modul nexus-voice).
Antwort: {"text": ..., "sprache": "de", "dauer": 12.3, "rechenzeit": 5.1}
Es wird immer nur EINE Aufnahme zur Zeit verarbeitet (4 CPU-Kerne teilen sich
Portal, Nextcloud, Authentik ...); weitere warten kurz.
"""

from __future__ import annotations

import hmac
import json
import os
import tempfile
import threading
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

from faster_whisper import WhisperModel, decode_audio

MODELL_PFAD = os.environ.get("WHISPER_MODELL", "/modell/faster-whisper-small")
TOKEN = os.environ["WHISPER_TOKEN"]
MAX_BYTES = 15 * 1024 * 1024          # 5 Minuten bei 48 kbit/s sind ~1,8 MB
THREADS = int(os.environ.get("WHISPER_THREADS", "2"))
# Schreibweise unserer Eigennamen -- nur Hilfe fuers Erkennen, die Sprache wird
# unabhaengig davon am Ton bestimmt.
HINWEIS = "N.E.X.U.S., AETHER, OneApp, Bauplan, Kreis, Menschheitsfamilie, Jitsi."
# Leise Aufnahmen anheben: Browser nehmen den Kameraton teils extrem leise auf (Videonachricht
# vom 02.10.2026: im Mittel -61 dB). Die Spracherkennung (und ihr Stille-Filter) arbeitet auf
# normal lautem Ton deutlich zuverlaessiger. Hoechstens 100-fach, damit reines Rauschen nicht
# zu "Sprache" aufgeblasen wird.
ZIEL_SPITZE = 0.9
MAX_VERSTAERKUNG = 100.0

_modell = WhisperModel(MODELL_PFAD, device="cpu", compute_type="int8", cpu_threads=THREADS)
_sperre = threading.Lock()


def mitschrift(pfad: str) -> dict:
    beginn = time.time()
    with _sperre:
        ton = decode_audio(pfad, sampling_rate=16000)   # entpackt auch den Ton aus Videos (WebM/MP4)
        spitze = float(abs(ton).max()) if ton.size else 0.0
        if 0.0 < spitze < ZIEL_SPITZE:
            ton = ton * min(ZIEL_SPITZE / spitze, MAX_VERSTAERKUNG)
        abschnitte, info = _modell.transcribe(
            ton, beam_size=1, vad_filter=True, initial_prompt=HINWEIS,
        )
        text = " ".join(a.text.strip() for a in abschnitte).strip()
    return {
        "text": text,
        "sprache": info.language,
        "dauer": round(info.duration, 1),
        "rechenzeit": round(time.time() - beginn, 1),
    }


class Handler(BaseHTTPRequestHandler):
    def _antwort(self, code: int, daten: dict) -> None:
        roh = json.dumps(daten, ensure_ascii=False).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(roh)))
        self.end_headers()
        self.wfile.write(roh)

    def do_GET(self) -> None:  # Lebenszeichen fuer Kontrollen
        self._antwort(200, {"ok": True}) if self.path == "/gesund" else self._antwort(404, {"fehler": "unbekannt"})

    def do_POST(self) -> None:
        if self.path != "/mitschrift":
            return self._antwort(404, {"fehler": "unbekannt"})
        if not hmac.compare_digest(self.headers.get("X-Token", ""), TOKEN):
            return self._antwort(403, {"fehler": "kein Zugriff"})
        laenge = int(self.headers.get("Content-Length") or 0)
        if laenge <= 0 or laenge > MAX_BYTES:
            return self._antwort(413, {"fehler": "Aufnahme leer oder zu gross"})
        with tempfile.NamedTemporaryFile(suffix=".audio") as datei:
            datei.write(self.rfile.read(laenge))
            datei.flush()
            try:
                self._antwort(200, mitschrift(datei.name))
            except Exception as fehler:  # kaputte Aufnahme o. ae. -- nie den Dienst abstuerzen lassen
                self._antwort(422, {"fehler": f"Mitschrift nicht moeglich: {type(fehler).__name__}"})

    def log_message(self, format: str, *args) -> None:  # keine Audiodetails ins Protokoll
        print(f"{self.address_string()} {format % args}", flush=True)


if __name__ == "__main__":
    print(f"Mitschrift-Dienst bereit (Modell {MODELL_PFAD}, {THREADS} Kerne)", flush=True)
    ThreadingHTTPServer(("0.0.0.0", 8000), Handler).serve_forever()
