/**
 * Meetings live (Josh, 02.10.2026): Hinweis auf laufende Online-Treffen + kleines Meeting-Fenster.
 *
 * - Fragt alle 60 Sekunden /nexus-meeting/live, welche Treffen gerade laufen (nur eigene Kreise).
 * - Hinweis: am PC eine kleine Karte unten links, am Handy eine schmale Leiste unten.
 * - "Beitreten": kMeet im schwebenden Fenster. Das Fenster haengt direkt an <body>, ausserhalb des
 *   Bereichs, den das Portal beim Weiterklicken austauscht -- es bleibt also offen. Am PC klein in der
 *   Ecke, am Handy gleich gross (Josh: "erst bei Beitritt gross") mit Knopf zum Verkleinern.
 * - Links auf die Meeting-Seite des Jitsi-Moduls werden abgefangen: mit Fenster -> Fenster, ohne
 *   Fenster -> volles Neuladen. Beides verhindert den Ladefehler des Jitsi-Moduls (siehe Module.php).
 */
(function () {
    'use strict';

    if (window.nexusMeetingGeladen) { return; }
    window.nexusMeetingGeladen = true;

    var ABFRAGE = '/nexus-meeting/live';
    var TAKT_MS = 60000;
    var HANDY_PX = 600;
    var SPEICHER = 'nexus-meeting-ausgeblendet';
    var SPEICHER_ORT = 'nexus-meeting-ort';   // wo das kleine Fenster zuletzt stand (nur dieser Browser)
    var RAND_PX = 8;                          // so nah darf das Fenster an den Bildschirmrand

    var TEXTE = {
        de: { live: 'Jetzt live', beitreten: 'Beitreten', ausblenden: 'Ausblenden', gross: 'Groß', klein: 'Klein', vollbild: 'Vollbild', vollbildAus: 'Vollbild beenden', ziehen: 'Zum Verschieben hier festhalten und ziehen',
              schliessen: 'Verlassen', schliessenFrage: 'Das Treffen verlassen?', laedt: 'Treffen wird geladen …',
              fehler: 'Das Treffen ließ sich nicht im Fenster öffnen. Es öffnet sich jetzt auf einer eigenen Seite.' },
        en: { live: 'Live now', beitreten: 'Join', ausblenden: 'Hide', gross: 'Large', klein: 'Small', vollbild: 'Full screen', vollbildAus: 'Exit full screen', ziehen: 'Hold here and drag to move',
              schliessen: 'Leave', schliessenFrage: 'Leave the meeting?', laedt: 'Loading meeting …',
              fehler: 'The meeting could not be opened in the window. It will now open on its own page.' },
        es: { live: 'En directo', beitreten: 'Unirse', ausblenden: 'Ocultar', gross: 'Grande', klein: 'Pequeño', vollbild: 'Pantalla completa', vollbildAus: 'Salir de pantalla completa', ziehen: 'Mantén pulsado aquí y arrastra para mover',
              schliessen: 'Salir', schliessenFrage: '¿Salir de la reunión?', laedt: 'Cargando la reunión …',
              fehler: 'No se pudo abrir la reunión en la ventana. Se abrirá ahora en su propia página.' }
    };
    // Offene Tuer (Josh, 18.10.2026): "Menschen sehen, wenn ich am Rechner bin, und koennen einfach reinkommen"
    var TUER_TEXTE = {
        de: { offeneTuer: 'Offene Tür', istDa: '%s ist gerade da', kommRein: 'komm einfach rein', klopfAn: 'klopf an – du wirst hereingelassen',
              reinkommen: 'Reinkommen', anklopfen: 'Anklopfen', gespraechMit: 'Gespräch mit %s', meineOffen: 'Deine Tür ist offen', rein: 'Rein', tuerZu: 'Tür zu',
              frageTitel: 'Offene Tür', frageText: 'Alle Mitglieder sehen dann auf jeder Seite, dass du da bist, und kommen mit einem Klick zu dir ins Gespräch. Die Tür schließt sich von selbst, wenn du den Browser zumachst, spätestens nach %d Stunden.',
              direkt: 'Alle kommen direkt herein', warteraum: 'Ich lasse jeden einzeln herein (Warteraum)', oeffnen: 'Tür öffnen', abbrechen: 'Abbrechen',
              zuFrage: 'Die Tür schließen? Dein Gesprächsfenster schließt sich dann auch.', verlassen: 'Gespräch verlassen? Deine Tür wird dann geschlossen.',
              abgelaufen: 'Deine offene Tür hat sich nach %d Stunden von selbst geschlossen.', fehlerTuer: 'Die Tür ließ sich gerade nicht öffnen. Bitte versuch es noch einmal.',
              warteraumAn: 'Warteraum ist an – wer anklopft, erscheint bei dir zum Hereinlassen.' },
        en: { offeneTuer: 'Open door', istDa: '%s is here right now', kommRein: 'just come in', klopfAn: 'knock – you will be let in',
              reinkommen: 'Come in', anklopfen: 'Knock', gespraechMit: 'Chat with %s', meineOffen: 'Your door is open', rein: 'Go in', tuerZu: 'Close door',
              frageTitel: 'Open door', frageText: 'All members will then see on every page that you are here and can join you with one click. The door closes by itself when you close the browser, after %d hours at the latest.',
              direkt: 'Everyone comes straight in', warteraum: 'I let people in one by one (waiting room)', oeffnen: 'Open door', abbrechen: 'Cancel',
              zuFrage: 'Close the door? Your chat window will close too.', verlassen: 'Leave the chat? Your door will then be closed.',
              abgelaufen: 'Your open door closed by itself after %d hours.', fehlerTuer: 'The door could not be opened just now. Please try again.',
              warteraumAn: 'Waiting room is on – whoever knocks appears for you to let in.' },
        es: { offeneTuer: 'Puerta abierta', istDa: '%s está aquí ahora', kommRein: 'entra sin más', klopfAn: 'llama a la puerta – te dejarán entrar',
              reinkommen: 'Entrar', anklopfen: 'Llamar', gespraechMit: 'Charla con %s', meineOffen: 'Tu puerta está abierta', rein: 'Entrar', tuerZu: 'Cerrar puerta',
              frageTitel: 'Puerta abierta', frageText: 'Todos los miembros verán en cada página que estás aquí y podrán unirse con un clic. La puerta se cierra sola al cerrar el navegador, como muy tarde tras %d horas.',
              direkt: 'Todos entran directamente', warteraum: 'Dejo entrar a cada persona (sala de espera)', oeffnen: 'Abrir puerta', abbrechen: 'Cancelar',
              zuFrage: '¿Cerrar la puerta? Tu ventana de charla también se cerrará.', verlassen: '¿Salir de la charla? Tu puerta se cerrará.',
              abgelaufen: 'Tu puerta abierta se cerró sola tras %d horas.', fehlerTuer: 'No se pudo abrir la puerta ahora. Inténtalo de nuevo.',
              warteraumAn: 'La sala de espera está activa: quien llame aparecerá para que le dejes entrar.' }
    };
    var sprache = (document.documentElement.lang || 'de').toLowerCase().split('-')[0];
    var T = TEXTE[sprache] || TEXTE.en;
    var TT = TUER_TEXTE[sprache] || TUER_TEXTE.en;
    var MEINE_TUER_ID = 'tuer-mein';
    var LEBENSZEICHEN_MS = 60000;
    var lebenszeichenUhr = null;

    var stand = { fenster: true, domain: '', praefix: '', name: '', meetings: [], tueren: [], meineTuer: null, darfTuer: false, tuerStunden: 4 };

    function fmt(text, wert) { return text.replace('%s', wert).replace('%d', wert); }

    // Rueckfrage im Fenster des Portals statt window.confirm (Firefox kann Browser-Dialoge abschalten,
    // dann antwortet confirm() still mit "Abbrechen"). Im echten Vollbild waere sie unsichtbar -- vorher raus.
    function frage(text) {
        var raus = document.fullscreenElement ? document.exitFullscreen().catch(function () {}) : Promise.resolve();
        return raus.then(function () {
            try {
                var modal = window.humhub && humhub.require('ui.modal');
                if (modal && modal.confirm) { return modal.confirm({ body: text }); }
            } catch (x) { /* weiter mit dem Browser-Fenster */ }
            return window.confirm(text);
        });
    }
    var hinweis = null;
    var offen = null;           // { box, api, id }
    var apiLaden = null;        // Promise fuer kMeets external_api.js

    function handy() { return window.innerWidth < HANDY_PX; }

    function el(tag, klasse, text) {
        var e = document.createElement(tag);
        if (klasse) { e.className = klasse; }
        if (text !== undefined) { e.textContent = text; }
        return e;
    }

    function ausgeblendet() {
        try { return JSON.parse(sessionStorage.getItem(SPEICHER) || '[]'); } catch (e) { return []; }
    }
    function ausblenden(id) {
        var liste = ausgeblendet();
        liste.push(id);
        try { sessionStorage.setItem(SPEICHER, JSON.stringify(liste)); } catch (e) { /* egal */ }
    }

    // ── kMeet-Programm VOLLSTAENDIG laden, bevor ein Treffen startet ─────────
    // kMeets external_api.js bringt die Nachruest-Bibliothek core-js mit. Haelt sie das Promise des
    // Browsers fuer unvollstaendig (so im Yandex-Browser, 23.10.2026), ersetzt sie es fuer die GANZE
    // Seite durch ihr eigenes -- ohne das, was das Portal braucht ("u.done is not a function",
    // "...finally is not a function"). Danach luden Feed und "Letzte Aktivitaeten" nicht mehr, solange
    // das Fenster offen war (Rueckmeldung 19.10. und 23.10.2026). Deshalb vorher merken und nach dem
    // Laden zuruecksetzen; kMeet arbeitet intern mit seiner eigenen Fassung weiter.
    // core-js tauscht nicht nur window.Promise aus, sondern ueberschreibt auch Promise.prototype.then des
    // Originals -- jede Kette lieferte danach ein Promise ohne finally/done (23.10.2026, zweiter Anlauf).
    // Deshalb auch alle Eigenschaften von Promise und Promise.prototype merken und wiederherstellen.
    var GESCHUETZT = ['Promise', 'queueMicrotask'];

    function globaleMerken() {
        var gemerkt = { werte: {} };
        GESCHUETZT.forEach(function (name) { gemerkt.werte[name] = window[name]; });
        gemerkt.statisch = Object.getOwnPropertyDescriptors(window.Promise);
        gemerkt.methoden = Object.getOwnPropertyDescriptors(window.Promise.prototype);
        gemerkt.Promise = window.Promise;
        return gemerkt;
    }

    function eigenschaftenZurueck(ziel, beschreibungen) {
        Object.keys(beschreibungen).forEach(function (name) {
            var jetzt = Object.getOwnPropertyDescriptor(ziel, name);
            var vorher = beschreibungen[name];
            if (jetzt && jetzt.value === vorher.value && jetzt.get === vorher.get) { return; }
            try { Object.defineProperty(ziel, name, vorher); } catch (e) { /* nicht aenderbar -- dann bleibt es */ }
        });
    }

    function globaleZurueck(gemerkt) {
        GESCHUETZT.forEach(function (name) {
            if (window[name] === gemerkt.werte[name]) { return; }
            try {
                if (gemerkt.werte[name] === undefined) { delete window[name]; } else { window[name] = gemerkt.werte[name]; }
            } catch (e) {
                Object.defineProperty(window, name, { value: gemerkt.werte[name], writable: true, configurable: true });
            }
        });
        eigenschaftenZurueck(gemerkt.Promise, gemerkt.statisch);
        eigenschaftenZurueck(gemerkt.Promise.prototype, gemerkt.methoden);
    }

    function kmeetProgramm() {
        if (window.JitsiMeetExternalAPI) { return Promise.resolve(); }
        if (apiLaden) { return apiLaden; }
        var gemerkt = globaleMerken();
        apiLaden = new gemerkt.Promise(function (fertig, fehler) {
            var s = document.createElement('script');
            s.src = 'https://' + stand.domain + '/external_api.js';
            s.async = true;
            s.onload = function () {
                globaleZurueck(gemerkt);
                if (window.JitsiMeetExternalAPI) { fertig(); } else { fehler(new Error('keine API')); }
            };
            s.onerror = function () { globaleZurueck(gemerkt); apiLaden = null; fehler(new Error('nicht geladen')); };
            document.head.appendChild(s);
        });
        return apiLaden;
    }

    // ── Hinweis "Jetzt live" ────────────────────────────────────────────
    function zeigeHinweis() {
        var weg = ausgeblendet();
        var sichtbar = stand.meetings.filter(function (m) {
            return weg.indexOf(m.id) === -1 && !(offen && offen.id === m.id);
        });
        var tueren = (stand.tueren || []).filter(function (t) {
            return weg.indexOf(t.id) === -1 && !(offen && offen.id === t.id);
        });
        var meineZeigen = !!stand.meineTuer && !(offen && offen.id === MEINE_TUER_ID);
        if (hinweis) { hinweis.remove(); hinweis = null; }
        if (!sichtbar.length && !tueren.length && !meineZeigen) { return; }
        hinweis = el('div', 'nexus-meeting-hinweis');
        hinweis.setAttribute('role', 'status');
        if (meineZeigen) {
            // Tuer offen, aber das eigene Fenster nicht (z. B. nach dem Neuladen der Seite)
            var meine = el('div', 'nexus-meeting-zeile nexus-tuer-zeile');
            var mt = el('div', 'nexus-meeting-text');
            var mp = el('span', 'nexus-meeting-punkt nexus-tuer-punkt');
            mp.setAttribute('aria-hidden', 'true');
            mt.append(mp, el('strong', '', TT.meineOffen));
            var rein = el('button', 'btn btn-sm nexus-meeting-los', TT.rein);
            rein.type = 'button';
            rein.addEventListener('click', meinenRaumBetreten);
            var zu = el('button', 'btn btn-sm btn-light nexus-tuer-zu', TT.tuerZu);
            zu.type = 'button';
            zu.addEventListener('click', function () { frage(TT.zuFrage).then(function (ja) { if (ja) { tuerSchliessen(); } }); });
            meine.append(mt, rein, zu);
            hinweis.appendChild(meine);
        }
        tueren.slice(0, 3).forEach(function (t) {
            var zeile = el('div', 'nexus-meeting-zeile nexus-tuer-zeile');
            var text = el('div', 'nexus-meeting-text');
            var punkt = el('span', 'nexus-meeting-punkt nexus-tuer-punkt');
            punkt.setAttribute('aria-hidden', 'true');
            text.append(punkt, el('strong', '', '☕ ' + fmt(TT.istDa, t.name)), el('span', 'nexus-meeting-titel', ' – ' + (t.warteraum ? TT.klopfAn : TT.kommRein)));
            text.title = fmt(TT.istDa, t.name);
            var los = el('button', 'btn btn-sm nexus-meeting-los', t.warteraum ? TT.anklopfen : TT.reinkommen);
            los.type = 'button';
            los.addEventListener('click', function () {
                if (!stand.fenster || !stand.domain) { return; }
                oeffneFenster(t.raum, fmt(TT.gespraechMit, t.name), t.id, null);
            });
            var x = el('button', 'nexus-meeting-x', '×');
            x.type = 'button';
            x.title = T.ausblenden;
            x.setAttribute('aria-label', T.ausblenden + ': ' + t.name);
            x.addEventListener('click', function () { ausblenden(t.id); zeigeHinweis(); });
            zeile.append(text, los, x);
            hinweis.appendChild(zeile);
        });
        sichtbar.slice(0, 3).forEach(function (m) {
            var zeile = el('div', 'nexus-meeting-zeile');
            var text = el('div', 'nexus-meeting-text');
            var punkt = el('span', 'nexus-meeting-punkt');
            punkt.setAttribute('aria-hidden', 'true');
            text.append(punkt, el('strong', '', T.live + ': '), el('span', 'nexus-meeting-titel', m.titel));
            text.title = m.kreis;
            var los = el('button', 'btn btn-sm nexus-meeting-los', T.beitreten);
            los.type = 'button';
            los.addEventListener('click', function () { beitreten(m); });
            var x = el('button', 'nexus-meeting-x', '×');
            x.type = 'button';
            x.title = T.ausblenden;
            x.setAttribute('aria-label', T.ausblenden + ': ' + m.titel);
            x.addEventListener('click', function () { ausblenden(m.id); zeigeHinweis(); });
            // Termin als "online" markiert, aber ohne Link (gibt es, z. B. Kreis 8): Hinweis ja, Knopf nein.
            if (m.raum || m.link) { zeile.append(text, los, x); } else { zeile.append(text, x); }
            hinweis.appendChild(zeile);
        });
        document.body.appendChild(hinweis);
    }

    // ── Beitreten ─────────────────────────────────────────────────────
    function seiteOeffnen(link) {
        // Volles Neuladen statt Seitenwechsel ohne Neuladen -- dann laedt die Meeting-Seite ihr
        // Programm zuverlaessig vor dem Treffen.
        window.location.href = link;
    }

    function beitreten(m) {
        if (!stand.fenster || !m.raum || !stand.domain) { seiteOeffnen(m.link); return; }
        oeffneFenster(m.raum, m.titel, m.id, m.link);
    }

    /**
     * optionen (fuer die offene Tuer): beimBeitritt(api) nach dem Start, beimSchliessen() nach dem
     * Schliessen, schliessenFrage statt der normalen Rueckfrage.
     */
    function oeffneFenster(raum, titel, id, rueckfall, optionen) {
        optionen = optionen || {};
        if (offen) {
            if (offen.id === id) { groesse(handy() ? 'voll' : 'klein'); return; }
            schliesseFenster();
        }
        var box = el('div', 'nexus-meeting-fenster');
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-label', titel);
        var kopf = el('div', 'nexus-meeting-kopf');
        var name = el('span', 'nexus-meeting-name', titel);
        var umschalten = el('button', 'nexus-meeting-knopf', '');
        umschalten.type = 'button';
        // Echter Vollbildmodus (Josh, 06.10.2026: "auf Vollbildschirm kann man nicht stellen").
        // Nur wo der Browser ihn fuer ein Element erlaubt -- iPhone-Safari z. B. nicht.
        var vollbild = el('button', 'nexus-meeting-knopf', '⛶');
        vollbild.type = 'button';
        vollbild.title = T.vollbild;
        vollbild.setAttribute('aria-label', T.vollbild);
        var kannVollbild = !!(document.fullscreenEnabled && box.requestFullscreen);
        var raus = el('button', 'nexus-meeting-knopf nexus-meeting-raus', '✕');
        raus.type = 'button';
        raus.title = T.schliessen;
        raus.setAttribute('aria-label', T.schliessen);
        kopf.append(name, umschalten);
        if (kannVollbild) { kopf.append(vollbild); }
        kopf.append(raus);
        var buehne = el('div', 'nexus-meeting-buehne');
        buehne.appendChild(el('div', 'nexus-meeting-laedt', T.laedt));
        box.append(kopf, buehne);
        document.body.appendChild(box);
        offen = { box: box, api: null, id: id, umschalten: umschalten, beimSchliessen: optionen.beimSchliessen || null };
        verschiebbar(box, kopf, buehne);
        groesse(handy() ? 'voll' : 'klein');
        zeigeHinweis();

        umschalten.addEventListener('click', function () {
            groesse(box.classList.contains('ist-klein') ? (handy() ? 'voll' : 'gross') : 'klein');
        });
        vollbild.addEventListener('click', function () {
            if (document.fullscreenElement) {
                document.exitFullscreen().catch(function () {});
            } else {
                box.requestFullscreen().catch(function () {});
            }
        });
        box.addEventListener('fullscreenchange', function () {
            var an = document.fullscreenElement === box;
            box.classList.toggle('ist-vollbild', an);
            vollbild.textContent = an ? '🗗' : '⛶';
            vollbild.title = an ? T.vollbildAus : T.vollbild;
            vollbild.setAttribute('aria-label', vollbild.title);
        });
        raus.addEventListener('click', function () {
            frage(optionen.schliessenFrage || T.schliessenFrage).then(function (ja) { if (ja) { schliesseFenster(); } });
        });

        kmeetProgramm().then(function () {
            if (!offen || offen.box !== box) { return; }
            buehne.innerHTML = '';
            var direkt = raum.indexOf('direkt:') === 0;
            offen.api = new window.JitsiMeetExternalAPI(stand.domain, {
                roomName: direkt ? raum.slice(7) : (stand.praefix || '') + raum,
                parentNode: buehne,
                width: '100%',
                height: '100%',
                userInfo: { displayName: stand.name },
                configOverwrite: { disableDeepLinking: true },
                interfaceConfigOverwrite: { RECENT_LIST_ENABLED: false, DISPLAY_WELCOME_PAGE_CONTENT: false }
            });
            offen.api.addEventListeners({ readyToClose: schliesseFenster });
            if (optionen.beimBeitritt) { optionen.beimBeitritt(offen.api); }
            // Auch kMeets eigener Vollbild-Knopf soll funktionieren: das braucht die Erlaubnis am iframe.
            try {
                var rahmen = offen.api.getIFrame();
                rahmen.setAttribute('allowfullscreen', 'true');
                var erlaubt = rahmen.getAttribute('allow') || '';
                if (erlaubt.indexOf('fullscreen') === -1) {
                    rahmen.setAttribute('allow', (erlaubt ? erlaubt + '; ' : '') + 'fullscreen');
                }
            } catch (e) { /* ohne iframe kein Vollbild von kMeet -- unser Knopf geht trotzdem */ }
        }).catch(function () {
            schliesseFenster();
            if (window.humhub && humhub.modules && humhub.modules.ui && humhub.modules.ui.status) {
                humhub.modules.ui.status.warn(T.fehler);
            }
            if (rueckfall) { seiteOeffnen(rueckfall); }
        });
    }

    // ── Kleines Fenster verschieben (Josh, 16.10.2026: "lässt sich nicht verschieben, so kann man
    // nicht auf der Plattform nebenbei arbeiten"). Gezogen wird an der Kopfzeile, mit Maus oder
    // Finger. Nur im kleinen Zustand -- gross/voll fuellen ohnehin alles.
    function leseOrt() {
        try { return JSON.parse(localStorage.getItem(SPEICHER_ORT) || 'null'); } catch (e) { return null; }
    }
    function merkeOrt(ort) {
        try { localStorage.setItem(SPEICHER_ORT, JSON.stringify(ort)); } catch (e) { /* egal */ }
    }

    // Haelt das Fenster im sichtbaren Bereich (auch nach dem Verkleinern des Browserfensters).
    function setzeOrt(box, links, oben) {
        var breite = box.offsetWidth, hoehe = box.offsetHeight;
        links = Math.min(Math.max(RAND_PX, links), Math.max(RAND_PX, window.innerWidth - breite - RAND_PX));
        oben = Math.min(Math.max(RAND_PX, oben), Math.max(RAND_PX, window.innerHeight - hoehe - RAND_PX));
        box.style.left = links + 'px';
        box.style.top = oben + 'px';
        box.style.right = 'auto';
        box.style.bottom = 'auto';
    }

    // Ohne gemerkten Ort gilt die Ecke aus dem Stylesheet (unten rechts).
    function ortAnwenden(box) {
        var ort = leseOrt();
        if (ort && box.classList.contains('ist-klein')) { setzeOrt(box, ort.links, ort.oben); }
        else { box.style.left = box.style.top = box.style.right = box.style.bottom = ''; }
    }

    function verschiebbar(box, kopf, buehne) {
        kopf.classList.add('nexus-meeting-ziehbar');
        kopf.title = T.ziehen;
        var zug = null;
        kopf.addEventListener('pointerdown', function (e) {
            if (!box.classList.contains('ist-klein') || e.button !== 0 || e.target.closest('button')) { return; }
            var r = box.getBoundingClientRect();
            zug = { dx: e.clientX - r.left, dy: e.clientY - r.top, id: e.pointerId };
            kopf.setPointerCapture(e.pointerId);
            // Sonst "verschluckt" das Video (iframe) die Mausbewegung, sobald man darueber zieht.
            buehne.style.pointerEvents = 'none';
            box.classList.add('wird-gezogen');
            e.preventDefault();
        });
        kopf.addEventListener('pointermove', function (e) {
            if (!zug || e.pointerId !== zug.id) { return; }
            setzeOrt(box, e.clientX - zug.dx, e.clientY - zug.dy);
        });
        function loslassen(e) {
            if (!zug || e.pointerId !== zug.id) { return; }
            zug = null;
            buehne.style.pointerEvents = '';
            box.classList.remove('wird-gezogen');
            var r = box.getBoundingClientRect();
            merkeOrt({ links: Math.round(r.left), oben: Math.round(r.top) });
        }
        kopf.addEventListener('pointerup', loslassen);
        kopf.addEventListener('pointercancel', loslassen);
    }

    window.addEventListener('resize', function () {
        if (offen && offen.box.classList.contains('ist-klein') && leseOrt()) {
            var r = offen.box.getBoundingClientRect();
            setzeOrt(offen.box, r.left, r.top);
        }
    });

    // klein = Ecke (PC) bzw. Bildchen (Handy) -- gross = ganzes Browserfenster (PC) -- voll = ganzer Bildschirm (Handy)
    // Daneben der Knopf Vollbild: echter Vollbildmodus ohne Browser-Leisten (requestFullscreen).
    function groesse(art) {
        if (!offen) { return; }
        var box = offen.box;
        box.classList.remove('ist-klein', 'ist-gross', 'ist-voll');
        box.classList.add('ist-' + art);
        ortAnwenden(box);
        offen.umschalten.textContent = art === 'klein' ? '⤢' : '–';
        var titel = art === 'klein' ? T.gross : T.klein;
        offen.umschalten.title = titel;
        offen.umschalten.setAttribute('aria-label', titel);
    }

    function schliesseFenster() {
        if (!offen) { return; }
        if (document.fullscreenElement === offen.box) { document.exitFullscreen().catch(function () {}); }
        try { if (offen.api) { offen.api.dispose(); } } catch (e) { /* schon weg */ }
        offen.box.remove();
        var danach = offen.beimSchliessen;
        offen = null;
        if (danach) { danach(); }
        zeigeHinweis();
    }

    // ── Bestehende Meeting-Links (Kalender, Beitraege) abfangen ───────────────
    document.addEventListener('click', function (e) {
        // Kurzadresse ".../conference/Raum" (so in unseren Terminen) und lange Adresse des Jitsi-Moduls.
        var a = e.target.closest ? e.target.closest('a[href*="/conference/"], a[href*="/jitsi-meet/room/open"]') : null;
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) { return; }
        var url = new URL(a.href, window.location.origin);
        if (url.origin !== window.location.origin) { return; }
        e.preventDefault();
        e.stopPropagation();
        var kurz = url.pathname.match(/^\/conference\/([^\/]+)/);
        var roh = kurz ? decodeURIComponent(kurz[1]) : (url.searchParams.get('name') || '');
        // Wie RoomController::fixRoomName: ucwords (nur nach Leerraum gross) und alles ausser A-Z/0-9 weg.
        var raum = roh.replace(/(^|\s)(\S)/g, function (g, vor, b) { return vor + b.toUpperCase(); }).replace(/[^A-Za-z0-9]/g, '');
        if (stand.fenster && raum && stand.domain) {
            oeffneFenster(raum, a.textContent.trim() || raum, 'link-' + raum, url.href);
        } else {
            seiteOeffnen(url.href);
        }
    }, true);

    // ── Offene Tuer ───────────────────────────────────────────────────
    function senden(pfad, daten) {
        var form = new FormData();
        Object.keys(daten || {}).forEach(function (k) { form.append(k, daten[k]); });
        var token = document.querySelector('meta[name="csrf-token"]');
        var param = document.querySelector('meta[name="csrf-param"]');
        if (token && param) { form.append(param.getAttribute('content'), token.getAttribute('content')); }
        return fetch(pfad, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); });
    }

    function meldung(text, art) {
        if (window.humhub && humhub.modules && humhub.modules.ui && humhub.modules.ui.status) {
            humhub.modules.ui.status[art || 'info'](text);
        } else {
            window.alert(text);
        }
    }

    // Jede Minute "ich bin noch da" -- auch wenn der Tab im Hintergrund liegt (sonst ginge die Tuer zu,
    // waehrend man in einem anderen Programm arbeitet). Nur solange das eigene Gespraechsfenster offen ist.
    function lebenszeichenStarten() {
        if (lebenszeichenUhr) { return; }
        lebenszeichenUhr = setInterval(function () {
            if (!offen || offen.id !== MEINE_TUER_ID) { lebenszeichenStoppen(); return; }
            senden('/nexus-meeting/tuer/lebenszeichen').then(function (antwort) {
                if (antwort && antwort.offen === false) {
                    stand.meineTuer = null;
                    lebenszeichenStoppen();
                    menueAnzeigen();
                    meldung(fmt(TT.abgelaufen, stand.tuerStunden || 4), 'warn');
                }
            }).catch(function () { /* naechste Minute neu */ });
        }, LEBENSZEICHEN_MS);
    }
    function lebenszeichenStoppen() {
        if (lebenszeichenUhr) { clearInterval(lebenszeichenUhr); lebenszeichenUhr = null; }
    }

    function meinenRaumBetreten() {
        var tuer = stand.meineTuer;
        if (!tuer || !stand.domain) { return; }
        oeffneFenster(tuer.raum, TT.offeneTuer, MEINE_TUER_ID, null, {
            schliessenFrage: TT.verlassen,
            beimSchliessen: function () { if (stand.meineTuer) { tuerSchliessen(); } },
            beimBeitritt: function (api) {
                if (!tuer.warteraum) { return; }
                // Warteraum einschalten geht erst als Moderator -- das wird man als Erster im Raum
                var erledigt = false;
                api.addEventListener('participantRoleChanged', function (e) {
                    if (erledigt || !e || e.role !== 'moderator') { return; }
                    erledigt = true;
                    try { api.executeCommand('toggleLobby', true); meldung(TT.warteraumAn); } catch (x) { /* ohne Warteraum weiter */ }
                });
            }
        });
        lebenszeichenStarten();
    }

    function tuerOeffnen(warteraum) {
        senden('/nexus-meeting/tuer/oeffnen', { warteraum: warteraum ? '1' : '0' }).then(function (antwort) {
            stand.meineTuer = { raum: antwort.raum, warteraum: !!antwort.warteraum };
            menueAnzeigen();
            meinenRaumBetreten();
        }).catch(function () { meldung(TT.fehlerTuer, 'error'); });
    }

    function tuerSchliessen() {
        stand.meineTuer = null;
        lebenszeichenStoppen();
        menueAnzeigen();
        senden('/nexus-meeting/tuer/schliessen').catch(function () { /* laeuft ohne Lebenszeichen ohnehin ab */ });
        if (offen && offen.id === MEINE_TUER_ID) { schliesseFenster(); }
        zeigeHinweis();
    }

    function tuerFragen() {
        var alt = document.querySelector('.nexus-tuer-dialog');
        if (alt) { alt.remove(); }
        var huelle = el('div', 'nexus-tuer-dialog');
        var karte = el('div', 'nexus-tuer-karte');
        karte.setAttribute('role', 'dialog');
        karte.setAttribute('aria-modal', 'true');
        karte.setAttribute('aria-label', TT.frageTitel);
        karte.appendChild(el('h3', '', '☕ ' + TT.frageTitel));
        karte.appendChild(el('p', 'nexus-tuer-erklaerung', fmt(TT.frageText, stand.tuerStunden || 4)));
        [['0', TT.direkt], ['1', TT.warteraum]].forEach(function (wahl, i) {
            var zeile = el('label', 'nexus-tuer-wahl');
            var knopf = document.createElement('input');
            knopf.type = 'radio';
            knopf.name = 'nexus-tuer-warteraum';
            knopf.value = wahl[0];
            knopf.checked = i === 0;
            zeile.append(knopf, document.createTextNode(' ' + wahl[1]));
            karte.appendChild(zeile);
        });
        var knoepfe = el('div', 'nexus-tuer-knoepfe');
        var ja = el('button', 'btn btn-primary', TT.oeffnen);
        ja.type = 'button';
        var nein = el('button', 'btn btn-light', TT.abbrechen);
        nein.type = 'button';
        knoepfe.append(ja, nein);
        karte.appendChild(knoepfe);
        huelle.appendChild(karte);
        document.body.appendChild(huelle);
        ja.focus();
        function zu() { huelle.remove(); document.removeEventListener('keydown', taste); }
        function taste(e) { if (e.key === 'Escape') { zu(); } }
        document.addEventListener('keydown', taste);
        nein.addEventListener('click', zu);
        huelle.addEventListener('click', function (e) { if (e.target === huelle) { zu(); } });
        ja.addEventListener('click', function () {
            var gewaehlt = karte.querySelector('input[name="nexus-tuer-warteraum"]:checked');
            zu();
            tuerOeffnen(gewaehlt && gewaehlt.value === '1');
        });
    }

    // Der Eintrag oben in der Leiste zeigt, ob die Tuer offen ist (gruener Punkt)
    function menueAnzeigen() {
        document.querySelectorAll('[data-nexus-tuer]').forEach(function (a) {
            a.classList.toggle('nexus-tuer-ist-offen', !!stand.meineTuer);
            a.title = stand.meineTuer ? TT.meineOffen + ' – ' + TT.tuerZu : TT.offeneTuer;
        });
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('[data-nexus-tuer]') : null;
        if (!a) { return; }
        e.preventDefault();
        e.stopPropagation();
        if (stand.meineTuer) {
            frage(TT.zuFrage).then(function (ja) { if (ja) { tuerSchliessen(); } });
        } else {
            tuerFragen();
        }
    }, true);

    // ── Abfrage ─────────────────────────────────────────────────────
    function abfragen() {
        if (document.hidden) { return; }
        fetch(ABFRAGE, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (daten) {
                stand = daten;
                menueAnzeigen();
                if (stand.meineTuer && offen && offen.id === MEINE_TUER_ID) { lebenszeichenStarten(); }
                zeigeHinweis();
            })
            .catch(function () { /* naechster Versuch im naechsten Takt */ });
    }
    abfragen();
    setInterval(abfragen, TAKT_MS);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { abfragen(); } });
})();
