(function () {
    'use strict';

    // Das Portal wechselt Seiten ohne Neuladen (PJAX) und fuehrt Skripte dabei erneut
    // aus -- ohne diese Sperre haengten sich Beobachter und Knoepfe mehrfach an
    // (gleiche Lehre wie beim Uebersetzen-Modul, 21.09.2026).
    if (window.nexusVoiceGeladen) { return; }
    window.nexusVoiceGeladen = true;

    var MAX_SEKUNDEN = 5 * 60;          // Josh, 26.09.2026: hoechstens 5 Minuten
    var BITRATE = 48000;                // Sprachqualitaet: ~1,8 MB fuer 5 Minuten
    var PRAEFIX = 'Sprachnachricht-';   // Dateiname = Erkennungszeichen fuer den Abspieler

    var TEXTE = {
        de: { knopf: 'Sprachnachricht aufnehmen', stopp: 'Stopp', abbrechen: 'Abbrechen', anhaengen: 'Anhängen',
              verwerfen: 'Verwerfen', laeuft: 'Aufnahme läuft', fertig: 'Anhören und anhängen:',
              keinMikro: 'Kein Zugriff auf das Mikrofon. Bitte im Browser erlauben (Schloss-Symbol neben der Adresse).',
              nichtMoeglich: 'Dieser Browser kann keine Sprachnachrichten aufnehmen.',
              uploadFehlt: 'Anhängen hat nicht geklappt – bitte die Seite neu laden und nochmal versuchen.',
              schreibt: 'Mitschrift wird erstellt, bitte noch nicht senden …',
              hochladen: 'Wird hochgeladen … bitte mit dem Senden warten.', hochgeladen: 'Hochgeladen ✓ – du kannst jetzt senden.',
              nochNichtFertig: 'Die Aufnahme wird noch hochgeladen. Bitte warte, bis „Hochgeladen ✓“ erscheint, und sende dann.',
              ohneMitschrift: 'Ohne Mitschrift',
              nichtsVerstanden: 'In der Aufnahme wurde kein Text erkannt. Die Aufnahme ist trotzdem angehängt.',
              mitschriftFehlt: 'Die Mitschrift hat nicht geklappt. Die Aufnahme ist trotzdem angehängt.',
              anhoeren: 'Anhören', pause: 'Pause', probeGehtNicht: 'Probehören geht in diesem Browser leider nicht – die Aufnahme lässt sich trotzdem anhängen.',
              videoKnopf: 'Videonachricht aufnehmen', videoBereit: 'Bereit – höchstens 3 Minuten', videoStart: 'Aufnahme starten',
              kameraWechseln: 'Kamera wechseln', videoFertig: 'Aufgenommen:', neu: 'Neu aufnehmen',
              keineKamera: 'Kein Zugriff auf Kamera oder Mikrofon. Bitte im Browser erlauben (Schloss-Symbol neben der Adresse).',
              videoAngehaengt: 'Videonachricht angehängt ✓', videoAbspielen: 'Videonachricht abspielen',
              leer: 'Die Aufnahme ist leer – die Kamera hat kein Bild geliefert. Ist sie gerade in einem anderen Programm geöffnet (z. B. einem Videotreffen)? Bitte schließen und noch einmal versuchen.',
              kameraWeg: 'Die Kamera liefert kein Bild mehr. Bitte die Aufnahme stoppen und neu beginnen.' },
        en: { knopf: 'Record voice message', stopp: 'Stop', abbrechen: 'Cancel', anhaengen: 'Attach',
              verwerfen: 'Discard', laeuft: 'Recording', fertig: 'Listen and attach:',
              keinMikro: 'No access to the microphone. Please allow it in your browser (lock icon next to the address).',
              nichtMoeglich: 'This browser cannot record voice messages.',
              uploadFehlt: 'Attaching failed – please reload the page and try again.',
              schreibt: 'Creating transcript, please do not send yet …',
              hochladen: 'Uploading … please wait before sending.', hochgeladen: 'Uploaded ✓ – you can send now.',
              nochNichtFertig: 'The recording is still uploading. Please wait until “Uploaded ✓” appears, then send.',
              ohneMitschrift: 'Without transcript',
              nichtsVerstanden: 'No speech was recognised. The recording is attached anyway.',
              mitschriftFehlt: 'The transcript did not work. The recording is attached anyway.',
              anhoeren: 'Listen', pause: 'Pause', probeGehtNicht: 'Playback is not possible in this browser – you can still attach the recording.',
              videoKnopf: 'Record video message', videoBereit: 'Ready – up to 3 minutes', videoStart: 'Start recording',
              kameraWechseln: 'Switch camera', videoFertig: 'Recorded:', neu: 'Record again',
              keineKamera: 'No access to camera or microphone. Please allow it in your browser (lock icon next to the address).',
              videoAngehaengt: 'Video message attached ✓', videoAbspielen: 'Play video message',
              leer: 'The recording is empty – the camera delivered no picture. Is it open in another program (e.g. a video call)? Please close it and try again.',
              kameraWeg: 'The camera stopped delivering a picture. Please stop the recording and start again.' },
        es: { knopf: 'Grabar mensaje de voz', stopp: 'Detener', abbrechen: 'Cancelar', anhaengen: 'Adjuntar',
              verwerfen: 'Descartar', laeuft: 'Grabando', fertig: 'Escuchar y adjuntar:',
              keinMikro: 'Sin acceso al micrófono. Permítelo en el navegador (icono del candado junto a la dirección).',
              nichtMoeglich: 'Este navegador no puede grabar mensajes de voz.',
              uploadFehlt: 'No se pudo adjuntar – recarga la página e inténtalo de nuevo.',
              schreibt: 'Creando la transcripción, no envíes todavía …',
              hochladen: 'Subiendo … espera antes de enviar.', hochgeladen: 'Subido ✓ – ya puedes enviar.',
              nochNichtFertig: 'La grabación todavía se está subiendo. Espera a que aparezca «Subido ✓» y envía después.',
              ohneMitschrift: 'Sin transcripción',
              nichtsVerstanden: 'No se reconoció texto. La grabación está adjuntada de todos modos.',
              mitschriftFehlt: 'La transcripción no funcionó. La grabación está adjuntada de todos modos.',
              anhoeren: 'Escuchar', pause: 'Pausa', probeGehtNicht: 'No se puede escuchar en este navegador – la grabación se puede adjuntar igualmente.',
              videoKnopf: 'Grabar mensaje de vídeo', videoBereit: 'Listo – 3 minutos como máximo', videoStart: 'Empezar a grabar',
              kameraWechseln: 'Cambiar cámara', videoFertig: 'Grabado:', neu: 'Grabar de nuevo',
              keineKamera: 'Sin acceso a la cámara o al micrófono. Permítelo en el navegador (icono del candado junto a la dirección).',
              videoAngehaengt: 'Mensaje de vídeo adjuntado ✓', videoAbspielen: 'Reproducir mensaje de vídeo',
              leer: 'La grabación está vacía: la cámara no envió imagen. ¿Está abierta en otro programa (p. ej. una videollamada)? Ciérrala e inténtalo de nuevo.',
              kameraWeg: 'La cámara dejó de enviar imagen. Detén la grabación y empieza de nuevo.' }
    };
    var sprache = (document.documentElement.lang || 'de').toLowerCase().split('-')[0];
    var T = TEXTE[sprache] || TEXTE.en;

    var kannAufnehmen = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);

    // MP4/AAC zuerst: spielt auf iPhone UND Android/PC. Firefox kann es nicht aufnehmen,
    // dort WebM/Opus (spielt ueberall ausser auf sehr alten iPhones).
    function formatWahl() {
        var kandidaten = [
            { mime: 'audio/mp4', endung: 'm4a' },
            { mime: 'audio/webm;codecs=opus', endung: 'webm' },
            { mime: 'audio/webm', endung: 'webm' },
            { mime: 'audio/ogg;codecs=opus', endung: 'ogg' }
        ];
        for (var i = 0; i < kandidaten.length; i++) {
            if (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(kandidaten[i].mime)) { return kandidaten[i]; }
        }
        return { mime: '', endung: 'webm' };
    }

    function zeit(s) {
        s = Math.floor(s);
        return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2);
    }

    function dateiname(endung, praefix) {
        var d = new Date();
        function z(n) { return ('0' + n).slice(-2); }
        return (praefix || PRAEFIX) + d.getFullYear() + '-' + z(d.getMonth() + 1) + '-' + z(d.getDate()) + '-' + z(d.getHours()) + z(d.getMinutes()) + z(d.getSeconds()) + '.' + endung;
    }

    function knopf(text, klasse) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-sm ' + klasse;
        b.textContent = text;
        return b;
    }

    // ── Aufnahme-Leiste (unten am Bildschirm, fuer alle Formulare gleich) ─────
    var aktiv = null;

    function schliesse() {
        if (!aktiv) { return; }
        clearInterval(aktiv.uhr);
        if (aktiv.bild) { aktiv.bild.srcObject = null; }
        if (aktiv.probeStopp) { aktiv.probeStopp(); }
        if (aktiv.kontext && aktiv.kontext.close) { aktiv.kontext.close(); }
        if (aktiv.recorder && aktiv.recorder.state !== 'inactive') { aktiv.abgebrochen = true; aktiv.recorder.stop(); }
        if (aktiv.tonRecorder && aktiv.tonRecorder.state !== 'inactive') { aktiv.tonRecorder.stop(); }
        if (aktiv.stream) { aktiv.stream.getTracks().forEach(function (t) { t.stop(); }); }
        if (aktiv.url) { URL.revokeObjectURL(aktiv.url); }
        aktiv.leiste.remove();
        aktiv = null;
    }

    function meldung(text) {
        if (window.humhub && humhub.modules && humhub.modules.ui && humhub.modules.ui.status) {
            humhub.modules.ui.status.error(text);
        } else {
            alert(text);
        }
    }

    function status(art, text) {
        if (window.humhub && humhub.modules && humhub.modules.ui && humhub.modules.ui.status) {
            humhub.modules.ui.status[art](text);
        }
    }

    // ── Senden erst, wenn die Aufnahme wirklich hochgeladen ist (10.10.2026) ──
    // Ein Video braucht zum Hochladen und Umwandeln oft 20-40 Sekunden, die Mitschrift ist meist
    // schneller fertig. Wer dann auf "Senden" klickte, schickte den Text OHNE Video ab -- das Video
    // landete verwaist auf dem Server. Deshalb sind die Senden-Knoepfe des Formulars gesperrt,
    // bis HumHubs Upload-Widget die Datei zurueckmeldet.
    function sperrBereich(uploadInput) {
        return uploadInput.closest('.modal') || uploadInput.closest('form') || document.body;
    }

    function sendenSperren(bereich, an) {
        bereich.querySelectorAll('[type="submit"]').forEach(function (b) {
            if (an) {
                if (!b.disabled) { b.disabled = true; b.dataset.nexusVoiceGesperrt = '1'; }
            } else if (b.dataset.nexusVoiceGesperrt) {
                b.disabled = false;
                delete b.dataset.nexusVoiceGesperrt;
            }
        });
    }

    // Faengt auch ein Absenden ohne Knopf ab (Enter / Tastenkuerzel), solange etwas hochlaedt.
    document.addEventListener('submit', function (e) {
        var bereich = e.target.closest('.modal') || e.target;
        if (bereich.nexusVoiceUploads > 0) {
            e.preventDefault();
            e.stopImmediatePropagation();
            meldung(T.nochNichtFertig);
        }
    }, true);

    function hochladen(uploadInput, datei) {
        var $input = window.jQuery(uploadInput);
        var bereich = sperrBereich(uploadInput);
        var name = 'fileuploadalways.nexusvoice' + Date.now();
        bereich.nexusVoiceUploads = (bereich.nexusVoiceUploads || 0) + 1;
        sendenSperren(bereich, true);
        status('info', T.hochladen);
        $input.on(name, function (e, data) {
            if (!data || !data.files || data.files.indexOf(datei) === -1) { return; }
            $input.off(name);
            bereich.nexusVoiceUploads = Math.max(0, bereich.nexusVoiceUploads - 1);
            if (!bereich.nexusVoiceUploads) { sendenSperren(bereich, false); }
            if (data.textStatus === 'success') {
                status('success', T.hochgeladen);
            } else {
                meldung(T.uploadFehlt);
            }
        });
        // Genau der Weg eines ausgewaehlten Fotos: HumHubs Upload-Widget
        // (jQuery-fileupload) prueft, laedt hoch und haengt die Datei ans Formular.
        try {
            $input.fileupload('add', { files: [datei] });
        } catch (fehler) {
            $input.off(name);
            bereich.nexusVoiceUploads = Math.max(0, bereich.nexusVoiceUploads - 1);
            if (!bereich.nexusVoiceUploads) { sendenSperren(bereich, false); }
            throw fehler;
        }
    }

    function starte(uploadInput) {
        if (!kannAufnehmen) { meldung(T.nichtMoeglich); return; }
        if (aktiv) { return; }
        var format = formatWahl();
        var leiste = document.createElement('div');
        leiste.className = 'nexus-voice-leiste';
        leiste.setAttribute('role', 'dialog');
        leiste.setAttribute('aria-label', T.knopf);
        document.body.appendChild(leiste);
        aktiv = { leiste: leiste, stuecke: [], abgebrochen: false };
        var sitzung = aktiv;

        navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
            if (aktiv !== sitzung) { stream.getTracks().forEach(function (t) { t.stop(); }); return; }
            sitzung.stream = stream;
            var optionen = { audioBitsPerSecond: BITRATE };
            if (format.mime) { optionen.mimeType = format.mime; }
            var recorder = new MediaRecorder(stream, optionen);
            sitzung.recorder = recorder;
            recorder.ondataavailable = function (e) { if (e.data && e.data.size) { sitzung.stuecke.push(e.data); } };
            recorder.onstop = function () {
                stream.getTracks().forEach(function (t) { t.stop(); });
                clearInterval(sitzung.uhr);
                if (sitzung.abgebrochen || aktiv !== sitzung) { return; }
                zeigeVorschau(sitzung, uploadInput, format);
            };

            leiste.innerHTML = '';
            var punkt = document.createElement('span');
            punkt.className = 'nexus-voice-punkt';
            var anzeige = document.createElement('span');
            anzeige.className = 'nexus-voice-zeit';
            anzeige.textContent = T.laeuft + ' 0:00 / ' + zeit(MAX_SEKUNDEN);
            var stopp = knopf('■ ' + T.stopp, 'btn-primary');
            var ab = knopf(T.abbrechen, 'btn-light');
            leiste.append(punkt, anzeige, stopp, ab);
            stopp.addEventListener('click', function () { if (recorder.state !== 'inactive') { recorder.stop(); } });
            ab.addEventListener('click', schliesse);

            var beginn = Date.now();
            sitzung.uhr = setInterval(function () {
                var s = (Date.now() - beginn) / 1000;
                anzeige.textContent = T.laeuft + ' ' + zeit(s) + ' / ' + zeit(MAX_SEKUNDEN);
                if (s >= MAX_SEKUNDEN && recorder.state !== 'inactive') { recorder.stop(); }
            }, 250);
            recorder.start(1000);
            stopp.focus();
        }).catch(function () {
            schliesse();
            meldung(T.keinMikro);
        });
    }

    function zeigeVorschau(sitzung, uploadInput, format) {
        var typ = format.mime ? format.mime.split(';')[0] : (sitzung.stuecke[0] && sitzung.stuecke[0].type) || 'audio/webm';
        var blob = new Blob(sitzung.stuecke, { type: typ });
        var leiste = sitzung.leiste;
        leiste.innerHTML = '';
        var hinweis = document.createElement('span');
        hinweis.className = 'nexus-voice-zeit';
        hinweis.textContent = T.fertig;
        var audio = probeAbspieler(sitzung, blob);
        var an = knopf('📎 ' + T.anhaengen, 'btn-primary');
        var weg = knopf(T.verwerfen, 'btn-light');
        leiste.append(hinweis, audio, an, weg);
        weg.addEventListener('click', schliesse);
        an.addEventListener('click', function () {
            var datei = new File([blob], dateiname(format.endung), { type: typ });
            try {
                hochladen(uploadInput, datei);
            } catch (e) {
                meldung(T.uploadFehlt);
                return;
            }
            mitschreiben(sitzung, uploadInput, datei);
        });
        an.focus();
    }

    // ── Probehoeren VOR dem Anhaengen ───────────────────────────────────
    // NICHT <audio src="blob:...">: die Sicherheitsregel des Portals (Content-Security-Policy
    // "default-src *", kein eigenes media-src) laesst "*" nicht fuer blob:/data:-Adressen gelten --
    // der Browser blockierte das Abspielen auf PC und Handy (Josh, 26.09.2026). Web Audio
    // (AudioContext) unterliegt dieser Regel nicht; deshalb ein eigener schlichter Abspieler.
    // Die Regel selbst bleibt bewusst unangetastet.
    function probeAbspieler(sitzung, blob) {
        var box = document.createElement('div');
        box.className = 'nexus-voice-probe';
        var play = knopf('▶ ' + T.anhoeren, 'btn-light');
        var zeitAnz = document.createElement('span');
        zeitAnz.className = 'nexus-voice-zeit';
        zeitAnz.textContent = '0:00';
        box.append(play, zeitAnz);

        var puffer = null, quelle = null, start = 0, uhr = null;
        var Kontext = window.AudioContext || window.webkitAudioContext;
        function stopp() {
            if (quelle) { quelle.onended = null; try { quelle.stop(); } catch (e) { /* schon aus */ } quelle = null; }
            clearInterval(uhr);
            play.textContent = '▶ ' + T.anhoeren;
            if (puffer) { zeitAnz.textContent = zeit(puffer.duration); }
        }
        sitzung.probeStopp = stopp;
        play.addEventListener('click', function () {
            if (quelle) { stopp(); return; }
            if (!Kontext) { meldung(T.probeGehtNicht); return; }
            // Erst beim Tippen erzeugen -- iPhones erlauben Ton nur aus einem Fingertipp heraus.
            sitzung.kontext = sitzung.kontext || new Kontext();
            var ctx = sitzung.kontext;
            var bereit = puffer ? Promise.resolve(puffer) : blob.arrayBuffer().then(function (roh) {
                return new Promise(function (ok, fehler) { ctx.decodeAudioData(roh, ok, fehler); });
            });
            bereit.then(function (p) {
                puffer = p;
                if (ctx.state === 'suspended') { ctx.resume(); }
                quelle = ctx.createBufferSource();
                quelle.buffer = p;
                quelle.connect(ctx.destination);
                quelle.onended = stopp;
                start = ctx.currentTime;
                quelle.start();
                play.textContent = '❚❚ ' + T.pause;
                uhr = setInterval(function () {
                    zeitAnz.textContent = zeit(ctx.currentTime - start) + ' / ' + zeit(p.duration);
                }, 250);
            }).catch(function () { meldung(T.probeGehtNicht); });
        });
        return box;
    }

    // ── Mitschrift: Text der Aufnahme ins Schreibfeld (Josh, 26.09.2026) ─────────
    // Laeuft auf unserem eigenen Server (Dienst nexus-whisper), die Aufnahme geht nicht
    // nach aussen. Der Text landet im Schreibfeld und kann vor dem Senden korrigiert
    // werden; nach dem Senden uebersetzt nexus-translate ihn wie jeden anderen Text.
    function findeEditor(input) {
        for (var el = input; el; el = el.parentElement) {
            var ed = el.querySelector && el.querySelector('[contenteditable="true"]');
            if (ed) { return ed; }
        }
        return null;
    }

    function textEinsetzen(editor, text) {
        editor.focus();
        var auswahl = window.getSelection();
        var bereich = document.createRange();
        bereich.selectNodeContents(editor);
        bereich.collapse(false);
        auswahl.removeAllRanges();
        auswahl.addRange(bereich);
        if (editor.textContent.trim() !== '') { text = ' ' + text; }
        // insertText laeuft ueber den normalen Eingabeweg -- der Editor (ProseMirror)
        // uebernimmt den Text wie getippt, inkl. Rueckgaengig.
        if (!document.execCommand('insertText', false, text)) {
            editor.appendChild(document.createTextNode(text));
        }
    }

    function mitschreiben(sitzung, uploadInput, datei) {
        var leiste = sitzung.leiste;
        leiste.innerHTML = '';
        var punkt = document.createElement('span');
        punkt.className = 'nexus-voice-punkt nexus-voice-punkt-arbeit';
        var anzeige = document.createElement('span');
        anzeige.className = 'nexus-voice-zeit';
        var ohne = knopf(T.ohneMitschrift, 'btn-light');
        leiste.append(punkt, anzeige, ohne);
        var beginn = Date.now();
        function tick() { anzeige.textContent = T.schreibt + ' ' + zeit((Date.now() - beginn) / 1000); }
        tick();
        sitzung.uhr = setInterval(tick, 500);

        var abbruch = window.AbortController ? new AbortController() : null;
        ohne.addEventListener('click', function () { if (abbruch) { abbruch.abort(); } schliesse(); });

        var daten = new FormData();
        daten.append('audio', datei, datei.name);
        var meta = document.querySelector('meta[name="csrf-token"]');
        fetch('/nexus-voice/mitschrift', {
            method: 'POST',
            headers: { 'X-CSRF-Token': meta ? meta.getAttribute('content') : '', 'X-Requested-With': 'XMLHttpRequest' },
            body: daten,
            signal: abbruch ? abbruch.signal : undefined
        })
            .then(function (antwort) { return antwort.ok ? antwort.json() : Promise.reject(new Error('HTTP ' + antwort.status)); })
            .then(function (ergebnis) {
                if (aktiv !== sitzung) { return; }
                var text = (ergebnis && ergebnis.text || '').trim();
                var editor = findeEditor(uploadInput);
                schliesse();
                if (!text) { meldung(T.nichtsVerstanden); return; }
                if (!editor) { meldung(T.mitschriftFehlt); return; }
                textEinsetzen(editor, text);
            })
            .catch(function (fehler) {
                if (fehler && fehler.name === 'AbortError') { return; }
                if (aktiv === sitzung) { schliesse(); }
                meldung(T.mitschriftFehlt);
            });
    }

    // ── Videonachrichten (Josh, 02.10.2026): hoechstens 3 Minuten, runder Kreis wie bei Telegram ──
    // Gleicher Weg wie die Sprachnachricht: aufnehmen, als ganz normaler Anhang ueber HumHubs
    // Upload-Widget hochladen, beim Anzeigen am Dateinamen erkennen und rund darstellen.
    // KEIN Probe-Abspielen vor dem Anhaengen: ein Video aus dem Speicher des Browsers (blob:) blockt
    // die Sicherheitsregel des Portals genauso wie beim Ton (siehe probeAbspieler). Stattdessen zeigt
    // der Kreis nach dem Stopp das letzte Bild als Standbild. Die Live-Vorschau beim Aufnehmen
    // laeuft ueber srcObject (keine Adresse) und ist davon nicht betroffen.
    var VIDEO_MAX_SEKUNDEN = 3 * 60;
    var VIDEO_BITRATE = 600000;          // ~4,5 MB pro Minute mit Ton, ~14 MB fuer 3 Minuten
    var VIDEO_TON = 64000;
    var VIDEO_PRAEFIX = 'Videonachricht-';
    var VIDEO_KANTE = 480;               // quadratisch angefragt; nicht jede Kamera kann das, der Kreis schneidet zu

    function videoFormatWahl() {
        // WebM zuerst (Chrome, Edge, Firefox, Android), MP4 nur fuer Safari/iPhone, das kein WebM
        // aufnimmt. Abspielbar fuer alle macht es erst der Server: nexus-video-faststart kodiert
        // jede Videonachricht in H.264/AAC-MP4 um. (Chromes "video/mp4" enthaelt VP9 -- das
        // spielt das iPhone nicht; am 02.10.2026 im Browser nachgemessen.)
        var kandidaten = [
            { mime: 'video/webm;codecs=vp8,opus', endung: 'webm' },
            { mime: 'video/webm', endung: 'webm' },
            { mime: 'video/mp4;codecs=avc1,mp4a', endung: 'mp4' },
            { mime: 'video/mp4', endung: 'mp4' }
        ];
        for (var i = 0; i < kandidaten.length; i++) {
            if (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(kandidaten[i].mime)) { return kandidaten[i]; }
        }
        return { mime: '', endung: 'webm' };
    }

    function standbildVon(video) {
        var leinwand = document.createElement('canvas');
        var b = video.videoWidth, h = video.videoHeight;
        var kante = Math.min(b, h) || VIDEO_KANTE;
        leinwand.width = kante;
        leinwand.height = kante;
        try {
            leinwand.getContext('2d').drawImage(video, (b - kante) / 2, (h - kante) / 2, kante, kante, 0, 0, kante, kante);
        } catch (e) { /* kein Bild -- der Kreis bleibt dunkel */ }
        return leinwand;
    }

    function starteVideo(uploadInput) {
        if (!kannAufnehmen) { meldung(T.nichtMoeglich); return; }
        if (aktiv) { return; }
        var format = videoFormatWahl();
        var leiste = document.createElement('div');
        leiste.className = 'nexus-voice-leiste nexus-video-leiste';
        leiste.setAttribute('role', 'dialog');
        leiste.setAttribute('aria-label', T.videoKnopf);
        document.body.appendChild(leiste);

        var buehne = document.createElement('div');
        buehne.className = 'nexus-video-buehne';
        var kreis = document.createElement('div');
        kreis.className = 'nexus-video-kreis';
        var bild = document.createElement('video');
        bild.muted = true;
        bild.autoplay = true;
        bild.playsInline = true;
        bild.setAttribute('playsinline', '');
        kreis.appendChild(bild);
        buehne.appendChild(kreis);
        var anzeige = document.createElement('span');
        anzeige.className = 'nexus-voice-zeit';
        anzeige.textContent = T.videoBereit;
        var los = knopf('● ' + T.videoStart, 'btn-primary');
        var wechsel = knopf('🔄', 'btn-light');
        wechsel.title = T.kameraWechseln;
        wechsel.setAttribute('aria-label', T.kameraWechseln);
        var ab = knopf(T.abbrechen, 'btn-light');
        leiste.append(buehne, anzeige, los, wechsel, ab);
        ab.addEventListener('click', schliesse);

        aktiv = { leiste: leiste, stuecke: [], abgebrochen: false, kamera: 'user', bild: bild };
        var sitzung = aktiv;

        function kameraAn() {
            if (sitzung.stream) { sitzung.stream.getTracks().forEach(function (t) { t.stop(); }); }
            return navigator.mediaDevices.getUserMedia({
                audio: true,
                video: {
                    facingMode: sitzung.kamera,
                    width: { ideal: VIDEO_KANTE }, height: { ideal: VIDEO_KANTE },
                    aspectRatio: { ideal: 1 }, frameRate: { ideal: 24, max: 30 }
                }
            }).then(function (stream) {
                if (aktiv !== sitzung) { stream.getTracks().forEach(function (t) { t.stop(); }); return; }
                sitzung.stream = stream;
                bild.srcObject = stream;
                // Die Frontkamera gespiegelt zeigen, wie ein Spiegel -- aufgenommen wird ungespiegelt.
                kreis.classList.toggle('gespiegelt', sitzung.kamera === 'user');
                // Kamera faellt aus (anderes Programm greift zu, Kabel ab): sichtbar melden statt still einfrieren.
                stream.getVideoTracks().forEach(function (spur) {
                    spur.addEventListener('ended', function () { if (aktiv === sitzung) { meldung(T.kameraWeg); } });
                    // "mute" = laenger keine Bilder mehr (Kamera von einem anderen Programm belegt).
                    spur.addEventListener('mute', function () { if (aktiv === sitzung && !sitzung.stummGemeldet) { sitzung.stummGemeldet = true; meldung(T.kameraWeg); } });
                });
                var spielt = bild.play();
                if (spielt && spielt.catch) { spielt.catch(function () { /* startet beim naechsten Tipp */ }); }
            });
        }

        kameraAn().catch(function () { schliesse(); meldung(T.keineKamera); });
        wechsel.addEventListener('click', function () {
            sitzung.kamera = sitzung.kamera === 'user' ? 'environment' : 'user';
            kameraAn().catch(function () { meldung(T.keineKamera); });
        });

        los.addEventListener('click', function () {
            if (!sitzung.stream) { return; }
            los.remove();
            wechsel.remove();
            var stopp = knopf('■ ' + T.stopp, 'btn-primary');
            leiste.insertBefore(stopp, ab);
            kreis.classList.add('nimmt-auf');

            var optionen = { videoBitsPerSecond: VIDEO_BITRATE, audioBitsPerSecond: VIDEO_TON };
            if (format.mime) { optionen.mimeType = format.mime; }
            var recorder;
            try {
                recorder = new MediaRecorder(sitzung.stream, optionen);
            } catch (e) {
                delete optionen.mimeType;
                recorder = new MediaRecorder(sitzung.stream, optionen);
                format = { mime: '', endung: 'webm' };
            }
            sitzung.recorder = recorder;
            tonspurStarten(sitzung);
            var beginn = Date.now();
            recorder.ondataavailable = function (e) { if (e.data && e.data.size) { sitzung.stuecke.push(e.data); } };
            recorder.onstop = function () {
                clearInterval(sitzung.uhr);
                if (sitzung.tonRecorder && sitzung.tonRecorder.state !== 'inactive') { sitzung.tonRecorder.stop(); }
                var dauer = (Date.now() - beginn) / 1000;
                var standbild = standbildVon(bild);
                if (sitzung.stream) { sitzung.stream.getTracks().forEach(function (t) { t.stop(); }); }
                bild.srcObject = null;
                if (sitzung.abgebrochen || aktiv !== sitzung) { return; }
                zeigeVideoFertig(sitzung, uploadInput, format, recorder, kreis, standbild, dauer);
            };
            stopp.addEventListener('click', function () { if (recorder.state !== 'inactive') { recorder.stop(); } });
            sitzung.uhr = setInterval(function () {
                var s = (Date.now() - beginn) / 1000;
                anzeige.textContent = T.laeuft + ' ' + zeit(s) + ' / ' + zeit(VIDEO_MAX_SEKUNDEN);
                if (s >= VIDEO_MAX_SEKUNDEN && recorder.state !== 'inactive') { recorder.stop(); }
            }, 250);
            anzeige.textContent = T.laeuft + ' 0:00 / ' + zeit(VIDEO_MAX_SEKUNDEN);
            recorder.start(1000);
            stopp.focus();
        });
        los.focus();
    }

    // Neben dem Video laeuft eine reine Tonaufnahme mit (~1 MB fuer 3 Minuten) -- nur fuer die Mitschrift,
    // sie wird nirgends gespeichert. Klappt sie nicht, gibt es eben keine Mitschrift; das Video bleibt.
    function tonspurStarten(sitzung) {
        sitzung.tonFertig = Promise.resolve(null);
        try {
            var spuren = sitzung.stream.getAudioTracks();
            if (!spuren.length) { return; }
            var tonFormat = formatWahl();
            var optionen = { audioBitsPerSecond: BITRATE };
            if (tonFormat.mime) { optionen.mimeType = tonFormat.mime; }
            var ton = new MediaRecorder(new MediaStream(spuren), optionen);
            var stuecke = [];
            ton.ondataavailable = function (e) { if (e.data && e.data.size) { stuecke.push(e.data); } };
            sitzung.tonFertig = new Promise(function (fertig) {
                ton.onstop = function () {
                    var typ = tonFormat.mime ? tonFormat.mime.split(';')[0] : (ton.mimeType || 'audio/webm').split(';')[0];
                    var blob = new Blob(stuecke, { type: typ });
                    fertig(blob.size > 2000 ? new File([blob], dateiname(tonFormat.endung, 'Mitschrift-'), { type: typ }) : null);
                };
                ton.onerror = function () { fertig(null); };
            });
            sitzung.tonRecorder = ton;
            ton.start(1000);
        } catch (e) {
            sitzung.tonFertig = Promise.resolve(null);
        }
    }

    function zeigeVideoFertig(sitzung, uploadInput, format, recorder, kreis, standbild, dauer) {
        var typ = format.mime ? format.mime.split(';')[0] : (recorder.mimeType || 'video/webm').split(';')[0];
        var endung = typ.indexOf('mp4') !== -1 ? 'mp4' : 'webm';
        var blob = new Blob(sitzung.stuecke, { type: typ });
        // Leere Aufnahme erkennen, statt sie anzuhaengen (Josh, 02.10.2026: Datei mit 1,4 KB, Kamera
        // lieferte kein Bild). Echte Aufnahmen bringen 20-75 KB pro Sekunde, selbst bei ruhigem Bild;
        // die Grenze liegt bewusst weit darunter, damit nie eine echte Aufnahme verworfen wird.
        if (blob.size < Math.max(10000, dauer * 4000)) {
            schliesse();
            meldung(T.leer);
            return;
        }
        var leiste = sitzung.leiste;
        kreis.classList.remove('nimmt-auf', 'gespiegelt');
        kreis.innerHTML = '';
        kreis.appendChild(standbild);
        var buehne = kreis.parentNode;
        leiste.innerHTML = '';
        var hinweis = document.createElement('span');
        hinweis.className = 'nexus-voice-zeit';
        hinweis.textContent = T.videoFertig + ' ' + zeit(dauer);
        var an = knopf('📎 ' + T.anhaengen, 'btn-primary');
        var neu = knopf(T.neu, 'btn-light');
        var weg = knopf(T.verwerfen, 'btn-light');
        leiste.append(buehne, hinweis, an, neu, weg);
        weg.addEventListener('click', schliesse);
        neu.addEventListener('click', function () { schliesse(); starteVideo(uploadInput); });
        an.addEventListener('click', function () {
            var datei = new File([blob], dateiname(endung, VIDEO_PRAEFIX), { type: typ });
            try {
                hochladen(uploadInput, datei);
            } catch (e) {
                meldung(T.uploadFehlt);
                return;
            }
            // Mitschrift wie bei der Sprachnachricht -- aus der kleinen Tonspur, nicht aus dem Video
            // (das waere bis zu 15 MB und wuerde die Grenze des Mitschrift-Dienstes reissen).
            sitzung.tonFertig.then(function (tonDatei) {
                if (aktiv !== sitzung) { return; }
                if (tonDatei) {
                    mitschreiben(sitzung, uploadInput, tonDatei);
                    return;
                }
                // Die Erfolgsmeldung kommt aus hochladen(), sobald das Video wirklich oben ist.
                schliesse();
            });
        });
        an.focus();
    }

    // ── Runder Abspieler fuer fertige Videonachrichten ──────────────────────
    // Ein Tipp spielt mit Ton ab, noch ein Tipp pausiert; ein goldener Ring zeigt den Fortschritt.
    var RING_UMFANG = 2 * Math.PI * 48;

    function videoKreisEinsetzen(link, quelle) {
        var bereich = link.closest('.hideOnEdit, .post-files, .mail-conversation-entry, .media, .comment') || document;
        if (bereich.querySelector('.nexus-videonachricht[data-quelle="' + quelle.replace(/"/g, '') + '"]')) {
            if (link.querySelector('video')) { link.style.display = 'none'; }
            return;
        }
        var box = document.createElement('div');
        box.className = 'nexus-videonachricht';
        box.setAttribute('data-quelle', quelle);
        box.setAttribute('role', 'button');
        box.setAttribute('tabindex', '0');
        box.setAttribute('aria-label', T.videoAbspielen);
        var v = document.createElement('video');
        v.preload = 'metadata';
        v.playsInline = true;
        v.setAttribute('playsinline', '');
        v.src = quelle + (quelle.indexOf('#') === -1 ? '#t=0.1' : '');   // erstes Bild als Vorschau
        box.innerHTML = '<svg viewBox="0 0 100 100" aria-hidden="true"><circle class="spur" cx="50" cy="50" r="48"></circle>'
            + '<circle class="fortschritt" cx="50" cy="50" r="48" stroke-dasharray="' + RING_UMFANG + '" stroke-dashoffset="' + RING_UMFANG + '"></circle></svg>'
            + '<span class="nexus-videonachricht-symbol" aria-hidden="true">▶</span><span class="nexus-videonachricht-dauer"></span>';
        box.insertBefore(v, box.firstChild);
        var ring = box.querySelector('.fortschritt');
        var dauerAnz = box.querySelector('.nexus-videonachricht-dauer');

        function laenge() { return isFinite(v.duration) && v.duration > 0 ? v.duration : 0; }
        function zeigeDauer() { var d = laenge(); dauerAnz.textContent = d ? zeit(d - (v.paused ? 0 : v.currentTime)) : ''; }
        // Browser-Aufnahmen (v. a. WebM) melden ihre Laenge oft erst nach einem Sprung ans Ende.
        v.addEventListener('loadedmetadata', function () {
            if (v.duration === Infinity) {
                v.addEventListener('timeupdate', function ermittelt() {
                    v.removeEventListener('timeupdate', ermittelt);
                    v.currentTime = 0.1;
                    zeigeDauer();
                });
                v.currentTime = 1e101;
            } else {
                zeigeDauer();
            }
        });
        v.addEventListener('timeupdate', function () {
            var d = laenge();
            if (!d || v.paused) { return; }
            ring.setAttribute('stroke-dashoffset', String(RING_UMFANG * (1 - v.currentTime / d)));
            dauerAnz.textContent = zeit(d - v.currentTime);
        });
        v.addEventListener('ended', function () {
            box.classList.remove('spielt');
            ring.setAttribute('stroke-dashoffset', String(RING_UMFANG));
            v.currentTime = 0.1;
            zeigeDauer();
        });
        function umschalten() {
            if (v.paused) {
                var spielt = v.play();
                if (spielt && spielt.catch) { spielt.catch(function () { box.classList.remove('spielt'); }); }
                box.classList.add('spielt');
            } else {
                v.pause();
                box.classList.remove('spielt');
            }
        }
        box.addEventListener('click', function (e) { e.preventDefault(); umschalten(); });
        box.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); umschalten(); }
        });

        if (link.querySelector('video')) {
            // Im Video-Raster: HumHubs schwarzen Kasten samt Galerie-Link durch den Kreis ersetzen.
            link.parentNode.replaceChild(box, link);
        } else {
            var zeile = link.closest('li') || link;
            zeile.parentNode.insertBefore(box, zeile.nextSibling);
        }
    }

    // ── Mikrofon-Knopf NUR an den drei Schreibfeldern ──────────────────────
    // Erkannt am Vorschaubereich ihres Upload-Knopfs (data-upload-preview, aus den HumHub-/Mail-
    // Vorlagen): Beitrag, Kommentar, private Nachricht (neu + Antwort). Anfangs stand das Mikrofon
    // an JEDEM Upload -- auch am versteckten Bild-Upload des Texteditors und an Kreis-/Titelbild,
    // wo sich zwar aufnehmen, aber nichts anhaengen liess (Josh, 26.09.2026, Screenshot).
    var ERLAUBTE_VORSCHAU = [
        '#contentFormFiles_preview',            // Beitrag (auch im Fenster: ..._previewModal)
        '#comment_create_upload_preview_',      // Kommentar
        '#mail-preview',                        // neue private Nachricht
        '#mail-create-upload-preview-'          // Antwort in einer Unterhaltung
    ];

    function istSchreibfeld(input) {
        var vorschau = input.getAttribute('data-upload-preview') || '';
        return ERLAUBTE_VORSCHAU.some(function (p) { return vorschau.indexOf(p) === 0; });
    }

    function knopfEinsetzen(input) {
        input.setAttribute('data-nexus-voice', '1');
        if (!kannAufnehmen || !istSchreibfeld(input)) { return; }
        var gruppe = input.closest('.btn-group');
        var upload = input.closest('.fileinput-button') || input.parentElement;
        var anker = gruppe || upload;
        if (!anker || !anker.parentNode) { return; }
        var vorlage = (gruppe && gruppe.querySelector('.btn')) || upload;
        var klassen = (vorlage.className || 'btn btn-light btn-sm').split(/\s+/).filter(function (k) {
            return k && k !== 'fileinput-button' && k.indexOf('dropdown') !== 0;
        });
        if (klassen.indexOf('btn') === -1) { klassen.unshift('btn'); }
        function neuerKnopf(titel, symbol, aktion) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = klassen.join(' ') + ' nexus-voice-btn';
            b.title = titel;
            b.setAttribute('aria-label', titel);
            b.innerHTML = '<i class="fa ' + symbol + '" aria-hidden="true"></i>';
            b.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                aktion(input);
            });
            return b;
        }
        var mikro = neuerKnopf(T.knopf, 'fa-microphone', starte);
        var kamera = neuerKnopf(T.videoKnopf, 'fa-video-camera', starteVideo);
        anker.parentNode.insertBefore(mikro, anker.nextSibling);
        anker.parentNode.insertBefore(kamera, mikro.nextSibling);
    }

    // ── Abspieler fuer fertige Sprachnachrichten ─────────────────────────
    // HumHub zeigt WebM als Video (schwarzer Kasten) und M4A nur als Link. Beides wird
    // bei Dateien "Sprachnachricht-..." durch einen schlanken Audio-Abspieler ersetzt.
    function abspielerEinsetzen(link) {
        link.setAttribute('data-nexus-voice', '1');
        var name = (link.getAttribute('title') || link.textContent || '').trim();
        var href = link.getAttribute('href') || '';
        if (href.indexOf('file/file/download') === -1) { return; }
        if (name.indexOf(VIDEO_PRAEFIX) === 0) {
            videoKreisEinsetzen(link, href.split('#')[0]);
            return;
        }
        if (name.indexOf(PRAEFIX) !== 0) { return; }
        var quelle = href.split('#')[0];
        var bereich = link.closest('.hideOnEdit, .post-files, .mail-conversation-entry, .media, .comment') || document;
        if (bereich.querySelector('audio.nexus-voice-player[data-quelle="' + quelle.replace(/"/g, '') + '"]')) {
            // Dieselbe Datei hat schon einen Abspieler (Video-Raster + Dateiliste): nur Link ausblenden.
            if (link.querySelector('video')) { link.style.display = 'none'; }
            return;
        }
        var audio = document.createElement('audio');
        audio.className = 'nexus-voice-player';
        audio.controls = true;
        audio.preload = 'metadata';
        audio.src = quelle;
        audio.setAttribute('data-quelle', quelle);
        if (link.querySelector('video')) {
            // WebM im Video-Raster: Kasten samt Galerie-Link durch den Abspieler ersetzen.
            link.parentNode.replaceChild(audio, link);
        } else {
            var zeile = link.closest('li') || link;
            zeile.parentNode.insertBefore(audio, zeile.nextSibling);
        }
    }

    function durchsuchen() {
        var inputs = document.querySelectorAll('input[type="file"][data-ui-widget="file.Upload"]:not([data-nexus-voice])');
        for (var i = 0; i < inputs.length; i++) { knopfEinsetzen(inputs[i]); }
        var links = document.querySelectorAll('a[href*="file/file/download"]:not([data-nexus-voice])');
        for (var j = 0; j < links.length; j++) { abspielerEinsetzen(links[j]); }
    }

    var timer = null;
    if (typeof MutationObserver !== 'undefined' && document.body) {
        new MutationObserver(function () {
            clearTimeout(timer);
            timer = setTimeout(durchsuchen, 200);
        }).observe(document.body, { childList: true, subtree: true });
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && aktiv) { schliesse(); } });
    durchsuchen();
})();
