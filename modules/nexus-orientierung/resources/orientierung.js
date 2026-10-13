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

    if (window.jQuery) {
        window.jQuery(document).on('humhub:ready', wiederherstellen);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wiederherstellen);
    } else {
        wiederherstellen();
    }
})();
