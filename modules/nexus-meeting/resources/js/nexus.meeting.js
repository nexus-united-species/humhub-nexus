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

    var TEXTE = {
        de: { live: 'Jetzt live', beitreten: 'Beitreten', ausblenden: 'Ausblenden', gross: 'Groß', klein: 'Klein', vollbild: 'Vollbild', vollbildAus: 'Vollbild beenden',
              schliessen: 'Verlassen', schliessenFrage: 'Das Treffen verlassen?', laedt: 'Treffen wird geladen …',
              fehler: 'Das Treffen ließ sich nicht im Fenster öffnen. Es öffnet sich jetzt auf einer eigenen Seite.' },
        en: { live: 'Live now', beitreten: 'Join', ausblenden: 'Hide', gross: 'Large', klein: 'Small', vollbild: 'Full screen', vollbildAus: 'Exit full screen',
              schliessen: 'Leave', schliessenFrage: 'Leave the meeting?', laedt: 'Loading meeting …',
              fehler: 'The meeting could not be opened in the window. It will now open on its own page.' },
        es: { live: 'En directo', beitreten: 'Unirse', ausblenden: 'Ocultar', gross: 'Grande', klein: 'Pequeño', vollbild: 'Pantalla completa', vollbildAus: 'Salir de pantalla completa',
              schliessen: 'Salir', schliessenFrage: '¿Salir de la reunión?', laedt: 'Cargando la reunión …',
              fehler: 'No se pudo abrir la reunión en la ventana. Se abrirá ahora en su propia página.' }
    };
    var sprache = (document.documentElement.lang || 'de').toLowerCase().split('-')[0];
    var T = TEXTE[sprache] || TEXTE.en;

    var stand = { fenster: true, domain: '', praefix: '', name: '', meetings: [] };
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
    function kmeetProgramm() {
        if (window.JitsiMeetExternalAPI) { return Promise.resolve(); }
        if (apiLaden) { return apiLaden; }
        apiLaden = new Promise(function (fertig, fehler) {
            var s = document.createElement('script');
            s.src = 'https://' + stand.domain + '/external_api.js';
            s.async = true;
            s.onload = function () { window.JitsiMeetExternalAPI ? fertig() : fehler(new Error('keine API')); };
            s.onerror = function () { apiLaden = null; fehler(new Error('nicht geladen')); };
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
        if (hinweis) { hinweis.remove(); hinweis = null; }
        if (!sichtbar.length) { return; }
        hinweis = el('div', 'nexus-meeting-hinweis');
        hinweis.setAttribute('role', 'status');
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

    function oeffneFenster(raum, titel, id, rueckfall) {
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
        offen = { box: box, api: null, id: id, umschalten: umschalten };
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
            if (window.confirm(T.schliessenFrage)) { schliesseFenster(); }
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

    // klein = Ecke (PC) bzw. Bildchen (Handy) -- gross = ganzes Browserfenster (PC) -- voll = ganzer Bildschirm (Handy)
    // Daneben der Knopf Vollbild: echter Vollbildmodus ohne Browser-Leisten (requestFullscreen).
    function groesse(art) {
        if (!offen) { return; }
        var box = offen.box;
        box.classList.remove('ist-klein', 'ist-gross', 'ist-voll');
        box.classList.add('ist-' + art);
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
        offen = null;
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

    // ── Abfrage ─────────────────────────────────────────────────────
    function abfragen() {
        if (document.hidden) { return; }
        fetch(ABFRAGE, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (daten) {
                stand = daten;
                zeigeHinweis();
            })
            .catch(function () { /* naechster Versuch im naechsten Takt */ });
    }
    abfragen();
    setInterval(abfragen, TAKT_MS);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) { abfragen(); } });
})();
