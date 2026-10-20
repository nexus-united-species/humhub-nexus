/*
 * Zurueck an dieselbe Stelle in "Letzte Aktivitaeten" (Kasten rechts auf der Uebersicht).
 *
 * HumHub laedt den Kasten beim Zurueckkommen neu -- mit nur den ersten 10 Eintraegen, ganz oben.
 * Wer weit hinuntergescrollt hatte, verlor die Stelle. Deshalb: beim Anklicken eines Eintrags merken,
 * welcher es war (nur im Browser, nur fuer diesen Tab), und beim Zurueckkommen so lange nachladen,
 * bis er wieder da ist, dorthin scrollen und ihn kurz hervorheben.
 */
(function () {
    if (window.nexusOrientierung) { return; }
    window.nexusOrientierung = true;

    var SCHLUESSEL = 'nexus-orientierung-aktivitaet';
    var MAX_ALTER_MS = 60 * 60 * 1000;     // nach einer Stunde zaehlt es nicht mehr als "zurueck"
    var MAX_NACHLADEN = 30;                // hoechstens 30 x 10 Eintraege nachladen
    var WARTEN_MS = 200;
    var MAX_WARTEN = 75;                   // ~15 Sekunden auf den Kasten warten
    var MARKIERT_MS = 2500;
    var zurueck = false;

    try {
        var nav = performance.getEntriesByType('navigation')[0];
        zurueck = !!nav && nav.type === 'back_forward';
    } catch (e) { /* aeltere Browser: nur Seitenwechsel ohne Neuladen */ }
    window.addEventListener('popstate', function () { zurueck = true; });

    function lesen() {
        try { return JSON.parse(sessionStorage.getItem(SCHLUESSEL) || 'null'); } catch (e) { return null; }
    }
    function schreiben(wert) {
        try {
            if (wert) { sessionStorage.setItem(SCHLUESSEL, JSON.stringify(wert)); } else { sessionStorage.removeItem(SCHLUESSEL); }
        } catch (e) { /* privates Fenster o. ae.: dann eben ohne */ }
    }
    function hier() { return location.pathname + location.search; }

    // Klick auf einen Eintrag im Kasten: Stelle merken
    document.addEventListener('click', function (e) {
        var eintrag = e.target.closest && e.target.closest('#activityStream [data-stream-entry]');
        if (!eintrag) { return; }
        var inhalt = document.getElementById('activityContents');
        schreiben({
            seite: hier(),
            key: eintrag.getAttribute('data-content-key'),
            oben: inhalt ? inhalt.scrollTop : 0,
            fenster: window.scrollY,
            zeit: Date.now()
        });
    }, true);

    function strom() {
        try {
            var activity = humhub.require('activity');
            return activity && activity.getStream ? activity.getStream() : null;
        } catch (e) { return null; }
    }

    function zeigen(stream, el, stelle) {
        var inhalt = stream.$content && stream.$content[0];
        if (inhalt && inhalt.scrollHeight > inhalt.clientHeight + 5) {
            inhalt.scrollTop += el.getBoundingClientRect().top - inhalt.getBoundingClientRect().top - inhalt.clientHeight / 3;
            try { stream.$content.getNiceScroll().resize(); } catch (e) { /* ohne Zierleiste */ }
        }
        if (stelle.fenster > 0) { window.scrollTo(0, stelle.fenster); }
        var r = el.getBoundingClientRect();
        if (r.top < 0 || r.bottom > window.innerHeight) {
            window.scrollBy(0, r.top - window.innerHeight / 3);
        }
        el.classList.add('nx-or-markiert');
        setTimeout(function () { el.classList.remove('nx-or-markiert'); }, MARKIERT_MS);
    }

    function wiederherstellen() {
        var stelle = lesen();
        if (!zurueck || !stelle || stelle.seite !== hier() || Date.now() - stelle.zeit > MAX_ALTER_MS) {
            zurueck = false;
            return;
        }
        zurueck = false;
        schreiben(null);
        var gewartet = 0, nachgeladen = 0;
        (function versuch() {
            var stream = strom();
            if (!stream || !stream.$ || !stream.state) {
                if (gewartet++ < MAX_WARTEN) { setTimeout(versuch, WARTEN_MS); }
                return;
            }
            var el = stream.$.find('[data-stream-entry][data-content-key="' + stelle.key + '"]')[0];
            if (el) { zeigen(stream, el, stelle); return; }
            if (stream.isLoading() || !stream.state.firstRequest) {
                if (gewartet++ < MAX_WARTEN) { setTimeout(versuch, WARTEN_MS); }
                return;
            }
            if (!stream.canLoadMore() || nachgeladen++ >= MAX_NACHLADEN) {
                if (stelle.fenster > 0) { window.scrollTo(0, stelle.fenster); }
                return;
            }
            stream.load().then(function () { setTimeout(versuch, 50); }, function () { /* aufgeben */ });
        })();
    }

    // ── Vorschau-Fenster mit "Zurück" schließen ─────────────────────────
    // PDFs (Modul pdf-viewer) und die Datei-Ansicht öffnen sich als Fenster über der Seite, nicht als
    // neue Seite. "Zurück" im Browser verließ deshalb gleich die ganze Seite -- aus der Übersicht
    // landete man z. B. bei "Kreise" und musste die Stelle neu suchen (Rückmeldung eines Mitglieds).
    // Jetzt bekommt das geöffnete Fenster einen eigenen Schritt im Verlauf: "Zurück" schließt nur das
    // Fenster. Wird es über das Kreuz geschlossen, wird dieser Schritt wieder entfernt.
    var VORSCHAU_MARKE = 'nexusVorschau';
    var VORSCHAU_NACHLAUF_MS = 300;        // Datei-Ansicht -> PDF: kurz ist kein Fenster offen
    var DATEI_KLICK_GILT_MS = 10000;
    var dateiAnsichtGeklickt = 0;
    var schliesstUeberZurueck = false;

    document.addEventListener('click', function (e) {
        if (e.target.closest && e.target.closest('a[href*="/file/view"]')) { dateiAnsichtGeklickt = Date.now(); }
    }, true);

    function istVorschau(fenster) {
        if (fenster.id === 'pdf-viewer-modal') { return true; }
        return fenster.id === 'globalModal' && Date.now() - dateiAnsichtGeklickt < DATEI_KLICK_GILT_MS;
    }
    function offeneVorschauen() {
        return Array.prototype.filter.call(document.querySelectorAll('.modal.show'), function (m) {
            return m.getAttribute('data-nexus-vorschau') === '1';
        });
    }
    function unserSchritt() { return !!(history.state && history.state[VORSCHAU_MARKE]); }

    function fensterZu(fenster) {
        try {
            if (window.bootstrap && window.bootstrap.Modal) { window.bootstrap.Modal.getOrCreateInstance(fenster).hide(); return; }
        } catch (e) { /* weiter unten versuchen */ }
        var knopf = fenster.querySelector('[data-bs-dismiss="modal"], [data-dismiss="modal"]');
        if (knopf) { knopf.click(); }
    }

    document.addEventListener('shown.bs.modal', function (e) {
        var fenster = e.target;
        if (!fenster || !fenster.classList || !fenster.classList.contains('modal') || !istVorschau(fenster)) { return; }
        fenster.setAttribute('data-nexus-vorschau', '1');
        if (fenster.id === 'globalModal') { dateiAnsichtGeklickt = 0; }
        if (unserSchritt()) { return; }   // Datei-Ansicht -> PDF: ein Schritt reicht für beide
        var stand = {};
        if (history.state && typeof history.state === 'object') {
            Object.keys(history.state).forEach(function (k) { stand[k] = history.state[k]; });
        }
        stand[VORSCHAU_MARKE] = true;
        history.pushState(stand, '', location.href);
    });

    document.addEventListener('hidden.bs.modal', function (e) {
        var fenster = e.target;
        if (!fenster || fenster.getAttribute('data-nexus-vorschau') !== '1') { return; }
        fenster.removeAttribute('data-nexus-vorschau');
        if (schliesstUeberZurueck) { return; }
        // Erst nachsehen, ob gleich das nächste Vorschau-Fenster aufgeht oder die Seite wechselt --
        // dann den Schritt nicht entfernen.
        setTimeout(function () {
            if (offeneVorschauen().length === 0 && unserSchritt()) { history.back(); }
        }, VORSCHAU_NACHLAUF_MS);
    });

    window.addEventListener('popstate', function () {
        if (unserSchritt()) { return; }
        var offen = offeneVorschauen();
        if (offen.length === 0) { return; }
        zurueck = false;                  // nur ein Fenster geschlossen, keine Seite zurück
        schliesstUeberZurueck = true;
        offen.forEach(fensterZu);
        setTimeout(function () { schliesstUeberZurueck = false; }, VORSCHAU_NACHLAUF_MS * 2);
    });

    if (window.jQuery) {
        window.jQuery(document).on('humhub:ready', wiederherstellen);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wiederherstellen);
    } else {
        wiederherstellen();
    }
})();
