/**
 * Themen wie bei Telegram (Josh, 18.10.2026).
 *
 * - Leiste ueber dem Feed eines Kreises: "Alle" + die Themen des Kreises. Ein Klick filtert den Feed
 *   ueber HumHubs eigenen Themenfilter (humhub.require('topic').setTopics) -- die eingebaute Filterung
 *   bleibt also dieselbe, nur sichtbar.
 * - Beim Schreiben: Auswahl "Thema" im Formular (Vorgabe "Allgemein"; ist der Feed gerade auf ein Thema
 *   gefiltert, ist dieses vorausgewaehlt -- man schreibt dorthin, wo man gerade ist).
 * Laeuft nach jedem Seitenwechsel ohne Neuladen (PJAX) erneut. Kein Code im HTML (CSP).
 */
(function () {
    'use strict';

    if (window.nexusThemenGeladen) { return; }
    window.nexusThemenGeladen = true;

    var TEXTE = {
        de: { alle: 'Alle', thema: 'Thema', verwalten: 'Themen verwalten', leiste: 'Themen dieses Kreises' },
        en: { alle: 'All', thema: 'Topic', verwalten: 'Manage topics', leiste: 'Topics of this space' },
        es: { alle: 'Todo', thema: 'Tema', verwalten: 'Gestionar temas', leiste: 'Temas de este círculo' }
    };
    var sprache = (document.documentElement.lang || 'de').toLowerCase().split('-')[0];
    var T = TEXTE[sprache] || TEXTE.en;

    var stand = { guid: null, themen: [], vorgabe: null, aktiv: null };

    function el(tag, klasse, text) {
        var e = document.createElement(tag);
        if (klasse) { e.className = klasse; }
        if (text !== undefined) { e.textContent = text; }
        return e;
    }

    function kreisGuid() {
        try {
            var c = window.humhub && humhub.config && humhub.config.get('content.container');
            return c && c.guid && /^\/s\//.test(window.location.pathname) ? c.guid : null;
        } catch (e) { return null; }
    }

    function themenModul() {
        try { return humhub.require('topic'); } catch (e) { return null; }
    }

    // ── Leiste ────────────────────────────────────────────────────────
    function leisteZeichnen() {
        var alt = document.querySelector('.nexus-themen-leiste');
        if (alt) { alt.remove(); }
        var feed = document.getElementById('wall-stream-filter-nav');
        if (!feed || stand.themen.length === 0) { return; }
        var leiste = el('nav', 'nexus-themen-leiste');
        leiste.setAttribute('aria-label', T.leiste);
        var liste = el('div', 'nexus-themen-chips');
        liste.appendChild(chip(null, T.alle));
        stand.themen.forEach(function (t) { liste.appendChild(chip(t, t.name)); });
        leiste.appendChild(liste);
        if (stand.verwalten) {
            var link = el('a', 'nexus-themen-verwalten', '⚙');
            link.href = stand.verwalten;
            link.title = T.verwalten;
            link.setAttribute('aria-label', T.verwalten);
            leiste.appendChild(link);
        }
        // Zwischen Schreibfeld und Feed -- ohne Schreibfeld (Nicht-Mitglieder) direkt ueber dem Feed
        var schreiben = document.querySelector('[data-stream-create-content]');
        if (schreiben && schreiben.parentNode) { schreiben.insertAdjacentElement('afterend', leiste); }
        else { feed.insertAdjacentElement('beforebegin', leiste); }
        markieren();
    }

    function chip(thema, text) {
        var b = el('button', 'nexus-themen-chip', text);
        b.type = 'button';
        b.setAttribute('data-nexus-thema', thema ? String(thema.id) : '');
        b.addEventListener('click', function () {
            var modul = themenModul();
            if (!modul) { return; }
            // Loest humhub:topic:updated aus -- der Feed laedt sich gefiltert neu
            modul.setTopics(thema ? [{ id: thema.id, name: thema.name }] : []);
        });
        return b;
    }

    function markieren() {
        document.querySelectorAll('.nexus-themen-chip').forEach(function (b) {
            var an = (b.getAttribute('data-nexus-thema') || null) === (stand.aktiv ? String(stand.aktiv) : null);
            b.classList.toggle('ist-aktiv', an);
            b.setAttribute('aria-pressed', an ? 'true' : 'false');
        });
        auswahlVorbelegen();
    }

    // ── Auswahl beim Schreiben ──────────────────────────────────────────
    function auswahlEinsetzen() {
        // Das (versteckte) Themenfeld von HumHub steht in jedem Schreibformular -- daneben kommt unsere Auswahl
        document.querySelectorAll('[id^="postTopicContainer"]').forEach(function (versteckt) {
            var form = versteckt.closest('form');
            if (!form || form.querySelector('.nexus-themen-wahl') || stand.themen.length === 0) { return; }
            var auswahl = auswahlBauen('postTopicInput[]', 'nexus-themen-select');   // dasselbe Feld wie HumHubs eigene Themenwahl
            versteckt.insertAdjacentElement('afterend', auswahl.zeile);
            // Nur die NEUE Auswahl vorbelegen. Frueher geschah das bei jedem Klick ins Formular -- dann
            // sprang eine gewaehlte Auswahl beim Klick auf "Senden" auf "Allgemein" zurueck (Fehler 18.10.2026).
            vorbelegen(auswahl.select, stand.aktiv || stand.vorgabe);
        });
    }

    function auswahlBauen(name, klasse) {
        var zeile = el('div', 'nexus-themen-wahl');
        var id = 'nexus-themen-wahl-' + Math.random().toString(36).slice(2, 8);
        var label = el('label', '', T.thema + ':');
        label.setAttribute('for', id);
        var auswahl = document.createElement('select');
        auswahl.id = id;
        auswahl.name = name;
        auswahl.className = 'form-select form-select-sm ' + klasse;
        // HumHub merkt sich den Formularinhalt beim Laden und warnt beim Verlassen der Seite, wenn er sich
        // geaendert hat ("Nicht gespeicherte Aenderungen gehen verloren"). Unsere Auswahl kommt erst danach
        // dazu -- ohne dieses Kennzeichen kam die Warnung bei jedem Seitenwechsel im Kreis (19.10.2026).
        auswahl.setAttribute('data-prevent-statechange', '');
        stand.themen.forEach(function (t) {
            var o = document.createElement('option');
            o.value = String(t.id);
            o.textContent = t.name;
            auswahl.appendChild(o);
        });
        zeile.append(label, auswahl);
        return { zeile: zeile, select: auswahl };
    }

    function vorbelegen(auswahl, wert) {
        if (!wert) { return; }
        Array.prototype.forEach.call(auswahl.options, function (o) {
            o.defaultSelected = o.value === String(wert);   // gilt auch nach dem Zuruecksetzen des Formulars
            o.selected = o.value === String(wert);
        });
    }

    // Anderes Thema oben angeklickt: die Schreibformulare ziehen mit (nicht die Bearbeiten-Formulare)
    function auswahlVorbelegen() {
        document.querySelectorAll('.nexus-themen-select').forEach(function (auswahl) {
            vorbelegen(auswahl, stand.aktiv || stand.vorgabe);
        });
    }

    // ── Thema beim Bearbeiten eines Beitrags ändern ─────────────────────
    // HumHubs Bearbeiten-Formular kennt keine Themen. Unsere Auswahl schickt "nexusThema" mit; das Modul
    // setzt das Thema beim Speichern (Module::onBeitragGespeichert).
    function bearbeitenEinsetzen() {
        if (!stand.guid || stand.themen.length === 0) { return; }
        document.querySelectorAll('form[id^="post-edit-form_"]').forEach(function (form) {
            if (form.querySelector('.nexus-themen-wahl')) { return; }
            var postId = form.id.replace('post-edit-form_', '');
            var auswahl = auswahlBauen('nexusThema', 'nexus-themen-select-bearbeiten');
            var ziel = form.querySelector('.richtext-create-input-group');
            if (ziel) { ziel.insertAdjacentElement('afterend', auswahl.zeile); } else { form.appendChild(auswahl.zeile); }
            auswahl.select.disabled = true;   // erst nach dem Laden des aktuellen Themas -- sonst wuerde "Allgemein" gesetzt
            fetch('/nexus-themen/leiste/beitrag?cguid=' + encodeURIComponent(stand.guid) + '&id=' + encodeURIComponent(postId),
                { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
                .then(function (daten) {
                    var wert = daten.thema || '';
                    if (!wert) {
                        // Beitrag ohne Thema: leere Wahl "–", damit Speichern nichts ungefragt aendert
                        var leer = document.createElement('option');
                        leer.value = '';
                        leer.textContent = '–';
                        auswahl.select.insertBefore(leer, auswahl.select.firstChild);
                    }
                    vorbelegen(auswahl.select, wert || '');
                    if (!wert) { auswahl.select.value = ''; }
                    auswahl.select.disabled = false;
                })
                .catch(function () { auswahl.zeile.remove(); });
        });
    }

    new MutationObserver(function () { bearbeitenEinsetzen(); })
        .observe(document.documentElement, { childList: true, subtree: true });

    // ── Laden ─────────────────────────────────────────────────────────
    function start() {
        var guid = kreisGuid();
        if (!guid || !document.getElementById('wall-stream-filter-nav')) {
            stand.guid = null;
            return;
        }
        if (guid === stand.guid) { leisteZeichnen(); auswahlEinsetzen(); return; }
        stand = { guid: guid, themen: [], vorgabe: null, aktiv: null };
        fetch('/nexus-themen/leiste/index?cguid=' + encodeURIComponent(guid), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (daten) {
                if (stand.guid !== guid) { return; }
                stand.themen = daten.themen || [];
                stand.vorgabe = daten.vorgabe || null;
                stand.verwalten = daten.verwalten || null;
                // Feed schon gefiltert (z. B. ueber ?topicId= oder ein Themen-Etikett am Beitrag)?
                var modul = themenModul();
                if (modul && modul.getTopicIds) {
                    var ids = modul.getTopicIds();
                    stand.aktiv = ids.length === 1 ? ids[0] : null;
                }
                leisteZeichnen();
                auswahlEinsetzen();
            })
            .catch(function () { /* ohne Leiste geht alles wie bisher */ });
    }

    // Filter geaendert -- auch ueber HumHubs eigenes Filtermenue oder ein Etikett am Beitrag
    function themenGeaendert(evt, themen) {
        var liste = Array.isArray(themen) ? themen : [];
        stand.aktiv = liste.length === 1 ? String(liste[0].id) : null;
        markieren();
    }

    function anmelden() {
        try {
            var ereignis = humhub.require('event');
            ereignis.on('humhub:topic:updated', themenGeaendert);
            ereignis.on('humhub:ready', function () { setTimeout(start, 0); });
        } catch (e) { /* HumHub-Module noch nicht da */ }
    }

    // Das Schreibformular wird teils erst beim Aufklappen gebaut -- dann nachziehen
    document.addEventListener('focusin', function (e) {
        if (stand.themen.length && e.target.closest && e.target.closest('[data-stream-create-content]')) { auswahlEinsetzen(); }
    });

    anmelden();
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
