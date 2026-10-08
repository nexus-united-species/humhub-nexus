/**
 * Karte der Gemeinschaften. Die Punkte stehen als Daten am Kartenelement (kein Inline-Skript),
 * die Kartenbilder kommen ueber unseren eigenen Server (data-kachel).
 */
(function () {
    'use strict';

    var EINZEL_ZOOM = 8;          // ein einzelner Punkt: Region sichtbar, nicht der ganze Globus
    var RAND_PX = 60;
    var NADEL_PX = 40;            // Kopf der Stecknadel
    var NADEL_SPITZE_PX = 48;
    var SUCHT_PX = 30;            // kleinere Nadel fuer Suchende
    var SUCHT_SPITZE_PX = 36;     // Abstand Oberkante -> Spitze (Kopf um 45 Grad gedreht)
    var OHNE_PUNKTE_MITTE = [48.5, 8.0];   // noch keine Gemeinschaft: Mitteleuropa zeigen
    var OHNE_PUNKTE_ZOOM = 4;
    var karte = null;
    var marker = {};

    function text(wert) {
        var el = document.createElement('div');
        el.textContent = wert == null ? '' : String(wert);
        return el.innerHTML;
    }

    function sichereAdresse(url) {
        // Nur Adressen auf dem eigenen Portal -- nie ein fremdes oder ein "javascript:"-Ziel.
        if (typeof url !== 'string' || url === '') {
            return '';
        }
        try {
            var ziel = new URL(url, window.location.origin);
            return ziel.origin === window.location.origin ? ziel.href : '';
        } catch (e) {
            return '';
        }
    }

    // Stecknadel mit dem Bild des Kreises; die Spitze zeigt genau auf den Ort.
    function nadel(p) {
        var bild = sichereAdresse(p.bild);
        return L.divIcon({
            className: 'nka-nadel',
            html: '<span class="nka-nadel-form">' + (bild ? '<img src="' + text(bild) + '" alt="">' : '<i></i>') + '</span>',
            iconSize: [NADEL_PX, NADEL_PX],
            iconAnchor: [NADEL_PX / 2, NADEL_SPITZE_PX],
            popupAnchor: [0, -NADEL_SPITZE_PX + 4]
        });
    }

    function aufbauen() {
        var el = document.getElementById('nexus-karte');
        if (!el || el.getAttribute('data-bereit') === '1' || typeof L === 'undefined') {
            return;
        }
        el.setAttribute('data-bereit', '1');

        var punkte = [];
        try {
            punkte = JSON.parse(el.getAttribute('data-punkte') || '[]');
        } catch (e) {
            el.textContent = '';
            return;
        }
        var minZoom = parseInt(el.getAttribute('data-min-zoom'), 10);
        var maxZoom = parseInt(el.getAttribute('data-max-zoom'), 10);
        var kachel = el.getAttribute('data-kachel');
        var trenner = kachel.indexOf('?') === -1 ? '?' : '&';

        karte = L.map(el, { minZoom: minZoom, maxZoom: maxZoom, scrollWheelZoom: false, worldCopyJump: true });
        karte.attributionControl.setPrefix(false);
        L.tileLayer(kachel + trenner + 'z={z}&x={x}&y={y}', {
            minZoom: minZoom,
            maxZoom: maxZoom,
            attribution: text(el.getAttribute('data-quelle')) + ': © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
        }).addTo(karte);
        // Mausrad erst nach einem Klick in die Karte -- sonst bleibt man beim Scrollen der Seite haengen.
        karte.on('click', function () { karte.scrollWheelZoom.enable(); });

        var grenzen = [];
        marker = {};
        punkte.forEach(function (p) {
            // Gaeste (Willkommensseite) bekommen nur Name und Ort, keinen Link in den Kreis.
            var inhalt = '<strong>' + text(p.name) + '</strong><br>📍 ' + text(p.ort)
                + (p.text ? '<p>' + text(p.text) + '</p>' : '<br>')
                + (p.url
                    ? '<a class="btn btn-primary btn-sm nka-popup-knopf" href="' + text(sichereAdresse(p.url) || '#') + '">' + text(el.getAttribute('data-zum-kreis')) + '</a>'
                    : '<span class="nka-klein">' + text(el.getAttribute('data-gast-hinweis')) + '</span>');
            marker[p.id] = L.marker([p.lat, p.lng], { icon: nadel(p), title: p.name + ' – ' + p.ort, alt: p.name })
                .addTo(karte).bindPopup(inhalt, { maxWidth: 260 });
            grenzen.push([p.lat, p.lng]);
        });

        // Suchende (nur fuer Angemeldete vorhanden): Menschen am selben Ort teilen sich eine Nadel,
        // sonst laegen sie genau uebereinander.
        var suchende = [];
        try {
            suchende = JSON.parse(el.getAttribute('data-suchende') || '[]');
        } catch (e) {
            suchende = [];
        }
        var orte = {};
        suchende.forEach(function (m) {
            var schluessel = m.lat + '|' + m.lng;
            orte[schluessel] = orte[schluessel] || { lat: m.lat, lng: m.lng, ort: m.ort, leute: [] };
            orte[schluessel].leute.push(m);
        });
        var suchEbene = L.layerGroup();
        Object.keys(orte).forEach(function (schluessel) {
            var o = orte[schluessel];
            var inhalt = '<strong>📍 ' + text(o.ort) + '</strong><br><span class="nka-klein">' + text(el.getAttribute('data-sucht-titel')) + '</span>'
                + o.leute.map(function (m) {
                    var adresse = sichereAdresse(m.url);
                    var name = '<strong>' + text(m.name) + '</strong>';
                    return '<p class="nka-mensch">' + (adresse ? '<a href="' + text(adresse) + '">' + name + '</a>' : name)
                        + (m.text ? '<br>' + text(m.text) : '') + '</p>';
                }).join('');
            var symbol = L.divIcon({
                className: 'nka-nadel nka-sucht',
                html: '<span class="nka-nadel-form"><b' + (o.leute.length > 1 ? '>' + o.leute.length : ' class="fa fa-user">') + '</b></span>',
                iconSize: [SUCHT_PX, SUCHT_PX],
                iconAnchor: [SUCHT_PX / 2, SUCHT_SPITZE_PX],
                popupAnchor: [0, -SUCHT_SPITZE_PX + 4]
            });
            L.marker([o.lat, o.lng], { icon: symbol, title: el.getAttribute('data-sucht-titel') + ' – ' + o.ort, zIndexOffset: -200 })
                .addTo(suchEbene).bindPopup(inhalt, { maxWidth: 260, maxHeight: 240 });
            grenzen.push([o.lat, o.lng]);
        });
        suchEbene.addTo(karte);
        var schalter = document.getElementById('nka-suchende-zeigen');
        if (schalter) {
            schalter.addEventListener('change', function () {
                if (schalter.checked) {
                    suchEbene.addTo(karte);
                } else {
                    karte.removeLayer(suchEbene);
                }
            });
        }

        function einpassen() {
            if (grenzen.length === 0) {
                karte.setView(OHNE_PUNKTE_MITTE, OHNE_PUNKTE_ZOOM);
            } else if (grenzen.length === 1) {
                karte.setView(grenzen[0], Math.min(EINZEL_ZOOM, maxZoom));
            } else {
                karte.fitBounds(grenzen, { padding: [RAND_PX, RAND_PX], maxZoom: Math.min(EINZEL_ZOOM, maxZoom) });
            }
        }
        einpassen();

        // Aendert sich die Groesse der Karte (Handy gedreht, Seite erst spaeter sichtbar), neu
        // einpassen -- aber nur, solange der Mensch die Karte noch nicht selbst bewegt hat.
        var selbstBewegt = false;
        ['mousedown', 'touchstart', 'wheel', 'keydown'].forEach(function (art) {
            el.addEventListener(art, function () { selbstBewegt = true; }, { passive: true });
        });
        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(function () {
                karte.invalidateSize();
                if (!selbstBewegt) {
                    einpassen();
                }
            }).observe(el);
        }
    }

    // Liste unter der Karte: "auf der Karte zeigen".
    document.addEventListener('click', function (ereignis) {
        var knopf = ereignis.target.closest ? ereignis.target.closest('[data-nka-zeigen]') : null;
        var m = knopf ? marker[knopf.getAttribute('data-nka-zeigen')] : null;
        if (!m || !karte) {
            return;
        }
        karte.setView(m.getLatLng(), Math.min(EINZEL_ZOOM, karte.getMaxZoom()));
        m.openPopup();
        karte.getContainer().scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', aufbauen);
    } else {
        aufbauen();
    }
    // Falls die Seite doch einmal ohne Neuladen gewechselt wird (PJAX).
    if (window.jQuery) {
        window.jQuery(document).on('humhub:ready', aufbauen);
    }
})();
