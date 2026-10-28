/*
 * Emoji-Reaktionen und Antworten.
 *
 * 1. Beitraege und Kommentare: neben dem Gefaellt-mir-Link (.likeLinkContainer) ein Knopf mit Emoji-Auswahl,
 *    darunter die Reaktionen als kleine Chips. Der Daumen von HumHub bleibt unberuehrt.
 * 2. Private Nachrichten (.mail-conversation-entry): unter jeder Nachricht Knoepfe "Antworten" und Emoji-Auswahl,
 *    Reaktionen als Chips, und eine Nachricht, die auf eine andere antwortet, zeigt oben einen kleinen Auszug
 *    der Originalnachricht (Klick springt dorthin).
 *
 * Das Skript haengt sich auch an spaeter nachgeladene Eintraege, holt die Daten aller sichtbaren Eintraege mit je EINER
 * Anfrage und stoert die Seite nie: im Zweifel fehlen die Zusaetze einfach.
 */
(function () {
    if (window.nexusReaktionen || !window.nexusReaktionenKonfig) { return; }
    window.nexusReaktionen = true;

    var K = window.nexusReaktionenKonfig;
    var T = K.texte || {};
    var GEHOERT = 'data-nx-re';
    var behaelter = {};      // Schluessel -> Liste der Bereiche auf der Seite
    var wartend = [];        // Bereiche, fuer die noch nichts geholt wurde
    var timerHolen = null;
    var timerScan = null;
    var leiste = null;       // die eine Auswahlleiste fuer die ganze Seite
    var leisteFuer = null;   // Schluessel des Eintrags, zu dem sie gerade gehoert
    var schliessTimer = null;
    var antwortKontext = null; // {form, id, hoechsteId} solange eine Antwort vorbereitet wird

    function meta(name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : '';
    }

    function senden(url, daten) {
        var koerper = new URLSearchParams();
        Object.keys(daten).forEach(function (k) {
            // Listen muessen als "name[]" gesendet werden, sonst behaelt PHP nur den LETZTEN Wert
            // (Fehler 28.10.2026: von allen Eintraegen einer Seite kam nur einer zurueck)
            if (Array.isArray(daten[k])) {
                daten[k].forEach(function (v) { koerper.append(k + '[]', v); });
            } else {
                koerper.append(k, daten[k]);
            }
        });
        koerper.append(meta('csrf-param') || '_csrf', meta('csrf-token'));
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': meta('csrf-token'), 'Accept': 'application/json' },
            body: koerper
        }).then(function (antwort) {
            if (!antwort.ok) { throw new Error('HTTP ' + antwort.status); }
            return antwort.json();
        });
    }

    function elt(tag, klasse, text) {
        var e = document.createElement(tag);
        if (klasse) { e.className = klasse; }
        if (text !== undefined) { e.textContent = text; }
        return e;
    }

    function knopfElt(klasse, text, titel) {
        var k = elt('a', klasse, text);
        k.href = '#';
        k.setAttribute('role', 'button');
        k.setAttribute('aria-label', titel);
        k.setAttribute('title', titel);
        return k;
    }

    function istNachricht(bereich) { return bereich.getAttribute('data-nx-art') === 'nachricht'; }

    // ---- Anzeige -----------------------------------------------------------------------------------------

    function hinweis(eintrag) {
        var namen = (eintrag.namen || []).map(function (n) { return n === '' ? (T.sie || 'You') : n; });
        var text = namen.join(', ');
        if (eintrag.mehr > 0) { text += ' ' + (T.und || 'and') + ' ' + eintrag.mehr + ' ' + (T.weitere || 'more'); }
        return text;
    }

    function zeichneChips(bereich, liste) {
        var chips = bereich.querySelector('.nx-re-chips');
        if (!chips) { return; }
        chips.textContent = '';
        (liste || []).forEach(function (eintrag) {
            var chip = elt('a', 'nx-re-chip' + (eintrag.ich ? ' nx-re-ich' : ''));
            chip.href = '#';
            chip.setAttribute('role', 'button');
            chip.setAttribute('data-nx-emoji', eintrag.emoji);
            chip.setAttribute('title', hinweis(eintrag));
            chip.appendChild(elt('span', 'nx-re-emoji', eintrag.emoji));
            chip.appendChild(elt('span', 'nx-re-zahl', String(eintrag.anzahl)));
            chips.appendChild(chip);
        });
    }

    function zeichneZitat(bereich, antwort) {
        var eintrag = bereich.closest('.mail-conversation-entry');
        var ziel = eintrag && eintrag.querySelector('.markdown-render');
        if (!ziel) { return; }
        var alt = ziel.querySelector('.nx-nm-zitat');
        if (alt) { alt.remove(); }
        if (!antwort) { return; }
        var zitat = elt('a', 'nx-nm-zitat' + (antwort.id ? '' : ' nx-nm-zitat-weg'));
        zitat.href = '#';
        if (antwort.id) {
            zitat.setAttribute('data-nx-ziel', String(antwort.id));
            zitat.appendChild(elt('span', 'nx-nm-zitat-name', antwort.name));
            zitat.appendChild(elt('span', 'nx-nm-zitat-text', antwort.auszug));
        } else {
            zitat.appendChild(elt('span', 'nx-nm-zitat-text', T.geloescht || ''));
        }
        var autor = ziel.querySelector('.author-label');
        if (autor && autor.nextSibling) { ziel.insertBefore(zitat, autor.nextSibling); } else { ziel.insertBefore(zitat, ziel.firstChild); }
    }

    // daten: bei Beitraegen die Liste der Reaktionen, bei Nachrichten {reaktionen, antwort}
    function zeichneAlle(key, daten) {
        (behaelter[key] || []).forEach(function (b) {
            if (istNachricht(b)) {
                zeichneChips(b, (daten || {}).reaktionen || []);
                zeichneZitat(b, (daten || {}).antwort || null);
            } else {
                zeichneChips(b, daten || []);
            }
        });
    }

    // ---- Holen -------------------------------------------------------------------------------------------

    function holeGruppe(keys, url, feld, auswahl) {
        if (!keys.length) { return; }
        for (var i = 0; i < keys.length; i += 100) {
            (function (paket) {
                var daten = {};
                daten[feld] = paket.map(auswahl);
                senden(url, daten).then(function (antwort) {
                    paket.forEach(function (k) { zeichneAlle(k, (antwort.objekte || {})[k] || null); });
                }).catch(function () { /* dann eben ohne */ });
            })(keys.slice(i, i + 100));
        }
    }

    function holen() {
        timerHolen = null;
        var beitraege = [];
        var nachrichten = [];
        wartend.forEach(function (b) {
            var k = b.getAttribute('data-nx-key');
            if (!k) { return; }
            var liste = istNachricht(b) ? nachrichten : beitraege;
            if (liste.indexOf(k) < 0) { liste.push(k); }
        });
        wartend = [];
        holeGruppe(beitraege, K.urlListe, 'objekte', function (k) { return k; });
        holeGruppe(nachrichten, K.urlNachrichtenListe, 'eintraege', function (k) { return k.replace('MSG|', ''); });
    }

    function holenPlanen() {
        if (timerHolen === null) { timerHolen = setTimeout(holen, 80); }
    }

    function merken(bereich, key) {
        bereich.setAttribute(GEHOERT, '1');
        bereich.setAttribute('data-nx-key', key);
        (behaelter[key] = behaelter[key] || []).push(bereich);
        wartend.push(bereich);
        holenPlanen();
    }

    // ---- Bereiche vorbereiten ----------------------------------------------------------------------------

    function schluesselVonLike(bereich) {
        var anker = bereich.querySelector('a.likeAnchor[data-action-url]');
        if (!anker) { return null; }
        try {
            var u = new URL(anker.getAttribute('data-action-url'), location.href);
            var modell = u.searchParams.get('contentModel');
            var id = u.searchParams.get('contentId');
            return modell && id ? { modell: modell, id: id, key: modell + '|' + id } : null;
        } catch (e) { return null; }
    }

    function vorbereitenLike(bereich) {
        if (bereich.hasAttribute(GEHOERT)) { return; }
        var s = schluesselVonLike(bereich);
        if (!s) { return; }
        bereich.setAttribute('data-nx-modell', s.modell);
        bereich.setAttribute('data-nx-id', s.id);
        if (!bereich.querySelector('a.likeAnchor.disabled')) {
            bereich.appendChild(knopfElt('nx-re-knopf', '☺', T.knopf || 'React'));
        }
        bereich.appendChild(elt('span', 'nx-re-chips'));
        merken(bereich, s.key);
    }

    function vorbereitenNachricht(eintrag) {
        var inhalt = eintrag.querySelector('.conversation-entry-content');
        var id = eintrag.getAttribute('data-entry-id');
        if (!inhalt || !id || inhalt.querySelector('.nx-nm')) { return; }
        var leisteElt = elt('div', 'nx-nm');
        leisteElt.setAttribute('data-nx-art', 'nachricht');
        leisteElt.setAttribute('data-nx-id', id);
        leisteElt.appendChild(knopfElt('nx-nm-antwort', '↩ ' + (T.antworten || 'Reply'), T.antworten || 'Reply'));
        leisteElt.appendChild(knopfElt('nx-re-knopf', '☺', T.knopf || 'React'));
        leisteElt.appendChild(elt('span', 'nx-re-chips'));
        inhalt.appendChild(leisteElt);
        merken(leisteElt, 'MSG|' + id);

        // Eigene neue Nachricht nach dem Absenden: die vorbereitete Antwort ist damit verbraucht
        if (antwortKontext && eintrag.classList.contains('own') && parseInt(id, 10) > antwortKontext.hoechsteId) {
            antwortLoeschen();
        }
    }

    function absuchen() {
        timerScan = null;
        Object.keys(behaelter).forEach(function (k) {
            behaelter[k] = behaelter[k].filter(function (b) { return document.body.contains(b); });
            if (!behaelter[k].length) { delete behaelter[k]; }
        });
        if (antwortKontext && !document.body.contains(antwortKontext.form)) { antwortKontext = null; }
        document.querySelectorAll('.likeLinkContainer').forEach(vorbereitenLike);
        document.querySelectorAll('.mail-conversation-entry[data-entry-id]').forEach(vorbereitenNachricht);
    }

    function absuchenPlanen() {
        if (timerScan === null) { timerScan = setTimeout(absuchen, 120); }
    }

    // ---- Auswahlleiste -----------------------------------------------------------------------------------

    function leisteBauen() {
        leiste = elt('div', 'nx-re-leiste');
        leiste.setAttribute('role', 'menu');
        K.emojis.forEach(function (emoji) {
            var b = elt('a', 'nx-re-wahl', emoji);
            b.href = '#';
            b.setAttribute('role', 'menuitem');
            b.setAttribute('data-nx-emoji', emoji);
            leiste.appendChild(b);
        });
        leiste.addEventListener('mouseenter', function () { clearTimeout(schliessTimer); });
        leiste.addEventListener('mouseleave', schliessenSpaeter);
        document.body.appendChild(leiste);
    }

    function oeffnen(bereich, knopf) {
        if (!leiste) { leisteBauen(); }
        clearTimeout(schliessTimer);
        leisteFuer = bereich.getAttribute('data-nx-key');
        leiste._bereich = bereich;
        var gewaehlt = null;
        var mein = bereich.querySelector('.nx-re-chip.nx-re-ich');
        if (mein) { gewaehlt = mein.getAttribute('data-nx-emoji'); }
        leiste.querySelectorAll('.nx-re-wahl').forEach(function (w) {
            w.classList.toggle('nx-re-gewaehlt', w.getAttribute('data-nx-emoji') === gewaehlt);
        });
        leiste.style.display = 'flex';
        var r = knopf.getBoundingClientRect();
        var breite = leiste.offsetWidth;
        var links = Math.max(8, Math.min(window.scrollX + r.left - 6, window.scrollX + document.documentElement.clientWidth - breite - 8));
        leiste.style.left = links + 'px';
        var oben = window.scrollY + r.top - leiste.offsetHeight - 6;
        if (oben < window.scrollY + 4) { oben = window.scrollY + r.bottom + 6; }   // kein Platz oben: darunter
        leiste.style.top = oben + 'px';
    }

    function schliessen() {
        if (leiste) { leiste.style.display = 'none'; }
        leisteFuer = null;
    }

    function schliessenSpaeter() {
        clearTimeout(schliessTimer);
        schliessTimer = setTimeout(schliessen, 350);
    }

    // ---- Reaktion setzen ---------------------------------------------------------------------------------

    function setzen(bereich, emoji) {
        var key = bereich.getAttribute('data-nx-key');
        var id = bereich.getAttribute('data-nx-id');
        var anfrage;
        if (istNachricht(bereich)) {
            anfrage = senden(K.urlNachrichtSetzen, { eintrag: id, emoji: emoji });
        } else {
            var modell = bereich.getAttribute('data-nx-modell');
            var sep = K.urlSetzen.indexOf('?') >= 0 ? '&' : '?';
            anfrage = senden(K.urlSetzen + sep + 'contentModel=' + encodeURIComponent(modell) + '&contentId=' + encodeURIComponent(id), { emoji: emoji });
        }
        bereich.classList.add('nx-re-laeuft');
        anfrage.then(function (antwort) {
            // bei Nachrichten bleibt das Zitat, wie es ist: nur die Chips neu zeichnen
            (behaelter[key] || []).forEach(function (b) { zeichneChips(b, antwort.reaktionen || []); });
        }).catch(function () {
            window.alert(T.fehler || 'Error');
        }).then(function () {
            bereich.classList.remove('nx-re-laeuft');
        });
    }

    // ---- Antworten auf eine Nachricht --------------------------------------------------------------------

    function antwortLoeschen() {
        if (antwortKontext) {
            var leisteAlt = antwortKontext.form.querySelector('.nx-nm-antwortleiste');
            if (leisteAlt) { leisteAlt.remove(); }
            var feld = antwortKontext.form.querySelector('input[name="nexusAntwortAuf"]');
            if (feld) { feld.remove(); }
        }
        antwortKontext = null;
    }

    function hoechsteEintragsId() {
        var m = 0;
        document.querySelectorAll('.mail-conversation-entry[data-entry-id]').forEach(function (e) {
            m = Math.max(m, parseInt(e.getAttribute('data-entry-id'), 10) || 0);
        });
        return m;
    }

    function antwortVorbereiten(eintrag) {
        var form = document.querySelector('.mail-message-form form');
        if (!form) { return; }
        antwortLoeschen();
        var id = eintrag.getAttribute('data-entry-id');
        var autor = eintrag.querySelector('.author-label');
        var name = autor ? autor.textContent.trim() : (T.eigene || '');
        var text = eintrag.querySelector('.markdown-render');
        var auszug = '';
        if (text) {
            var kopie = text.cloneNode(true);
            kopie.querySelectorAll('.author-label, .nx-nm-zitat').forEach(function (n) { n.remove(); });
            auszug = kopie.textContent.replace(/\s+/g, ' ').trim();
            if (auszug.length > 140) { auszug = auszug.slice(0, 140).trim() + '…'; }
        }

        var feld = document.createElement('input');
        feld.type = 'hidden';
        feld.name = 'nexusAntwortAuf';
        feld.value = id;
        form.appendChild(feld);

        var leisteNeu = elt('div', 'nx-nm-antwortleiste');
        var inhalt = elt('div', 'nx-nm-antwortinhalt');
        inhalt.appendChild(elt('span', 'nx-nm-antwortkopf', (T.antwortAuf || 'Reply to') + (name ? ' ' + name : '')));
        inhalt.appendChild(elt('span', 'nx-nm-zitat-text', auszug));
        leisteNeu.appendChild(inhalt);
        leisteNeu.appendChild(knopfElt('nx-nm-abbrechen', '✕', T.abbrechen || 'Cancel'));
        var vor = form.querySelector('.richtext-create-input-group') || form.firstChild;
        vor.parentNode.insertBefore(leisteNeu, vor);

        antwortKontext = { form: form, id: id, hoechsteId: hoechsteEintragsId() };
        var schreibfeld = form.querySelector('[contenteditable="true"], textarea');
        if (schreibfeld) { schreibfeld.focus(); }
    }

    function zumOriginal(id) {
        var ziel = document.querySelector('.mail-conversation-entry[data-entry-id="' + id + '"]');
        if (!ziel) { return; }
        ziel.scrollIntoView({ block: 'center', behavior: 'smooth' });
        ziel.classList.add('nx-nm-blinken');
        setTimeout(function () { ziel.classList.remove('nx-nm-blinken'); }, 1800);
    }

    // ---- Ereignisse --------------------------------------------------------------------------------------

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t.closest) { return; }
        var knopf = t.closest('.nx-re-knopf');
        if (knopf) {
            e.preventDefault();
            e.stopPropagation();
            var bereich = knopf.closest('[' + GEHOERT + ']');
            if (leiste && leiste.style.display === 'flex' && leisteFuer === bereich.getAttribute('data-nx-key')) {
                schliessen();
            } else { oeffnen(bereich, knopf); }
            return;
        }
        var wahl = t.closest('.nx-re-wahl');
        if (wahl && leiste && leiste._bereich) {
            e.preventDefault();
            e.stopPropagation();
            var b = leiste._bereich;
            schliessen();
            setzen(b, wahl.getAttribute('data-nx-emoji'));
            return;
        }
        var chip = t.closest('.nx-re-chip');
        if (chip) {
            e.preventDefault();
            e.stopPropagation();
            setzen(chip.closest('[' + GEHOERT + ']'), chip.getAttribute('data-nx-emoji'));
            return;
        }
        var antwort = t.closest('.nx-nm-antwort');
        if (antwort) {
            e.preventDefault();
            e.stopPropagation();
            antwortVorbereiten(antwort.closest('.mail-conversation-entry'));
            return;
        }
        if (t.closest('.nx-nm-abbrechen')) {
            e.preventDefault();
            e.stopPropagation();
            antwortLoeschen();
            return;
        }
        var zitat = t.closest('.nx-nm-zitat');
        if (zitat) {
            e.preventDefault();
            e.stopPropagation();
            if (zitat.getAttribute('data-nx-ziel')) { zumOriginal(zitat.getAttribute('data-nx-ziel')); }
            return;
        }
        if (leiste && leiste.style.display === 'flex' && !t.closest('.nx-re-leiste')) { schliessen(); }
    }, true);

    // Mit der Maus: Auswahl beim Darueberfahren (am Handy nur per Antippen)
    var mitMaus = window.matchMedia && window.matchMedia('(hover: hover)').matches;
    if (mitMaus) {
        document.addEventListener('mouseover', function (e) {
            var knopf = e.target.closest && e.target.closest('.nx-re-knopf');
            if (knopf) { oeffnen(knopf.closest('[' + GEHOERT + ']'), knopf); }
        });
        document.addEventListener('mouseout', function (e) {
            var knopf = e.target.closest && e.target.closest('.nx-re-knopf');
            if (knopf) { schliessenSpaeter(); }
        });
    }

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { schliessen(); } });

    new MutationObserver(absuchenPlanen).observe(document.body, { childList: true, subtree: true });
    absuchen();
})();
