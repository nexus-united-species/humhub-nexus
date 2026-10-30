/* Projekt-Tafeln: Felder auf- und zuklappen, Rueckfrage vor dem Loeschen, weitere Textaenderung im Vorschlag.
   Ueber Delegation am Dokument, weil das Portal Seiten ohne Neuladen wechselt (PJAX). */
(function () {
    if (window.nexusProjekteGeladen) { return; }
    window.nexusProjekteGeladen = true;
    // Hilfe-Symbole: Antippen/Klicken klappt den Text auf bzw. zu, ein Klick daneben oder Esc schliesst ihn
    function hilfeZu(ausser) {
        document.querySelectorAll('.nx-pj-info.offen').forEach(function (h) {
            if (h !== ausser) {
                h.classList.remove('offen');
                var k = h.querySelector('.nx-pj-info-knopf');
                if (k) { k.setAttribute('aria-expanded', 'false'); }
            }
        });
    }
    document.addEventListener('click', function (e) {
        var hk = e.target.closest('.nx-pj-info-knopf');
        var h = hk ? hk.parentNode : null;
        hilfeZu(h);
        if (h) {
            e.preventDefault();
            var offen = h.classList.toggle('offen');
            hk.setAttribute('aria-expanded', offen ? 'true' : 'false');
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { hilfeZu(null); }
    });
    document.addEventListener('click', function (e) {
        var knopf = e.target.closest('[data-nx-pj-oeffne]');
        if (knopf) {
            e.preventDefault();
            var ziel = document.getElementById(knopf.getAttribute('data-nx-pj-oeffne'));
            if (ziel) {
                ziel.hidden = !ziel.hidden;
                var feld = ziel.querySelector('textarea');
                if (!ziel.hidden && feld) { feld.focus(); }
            }
            return;
        }
        var weitere = e.target.closest('[data-nx-pj-weitere]');
        if (weitere) {
            e.preventDefault();
            var liste = document.getElementById(weitere.getAttribute('data-nx-pj-weitere'));
            var vorlage = document.getElementById('nx-pj-aenderung-vorlage');
            if (liste && vorlage && liste.children.length < parseInt(liste.getAttribute('data-max') || '20', 10)) {
                liste.appendChild(vorlage.content.cloneNode(true));
                var neu = liste.lastElementChild && liste.lastElementChild.querySelector('input');
                if (neu) { neu.focus(); }
            }
            return;
        }
        var entfernen = e.target.closest('[data-nx-pj-entfernen]');
        if (entfernen) {
            e.preventDefault();
            var feldset = entfernen.closest('fieldset');
            if (feldset) {
                // Das letzte Feld wird nur geleert, damit man wieder anfangen kann
                if (feldset.parentNode.children.length > 1) {
                    feldset.parentNode.removeChild(feldset);
                } else {
                    feldset.querySelectorAll('input, textarea').forEach(function (f) { f.value = ''; });
                }
            }
        }
    });
    // Rueckfrage im Fenster des Portals statt mit window.confirm: Firefox bietet bei Browser-Rueckfragen
    // "Diese Seite daran hindern, weitere Dialoge zu erzeugen" an -- danach antwortet confirm() still mit
    // "Abbrechen", und der Knopf tat scheinbar nichts (Rueckmeldung eines Mitglieds, 20.10.2026).
    function portalRueckfrage(frage) {
        try {
            var modal = window.humhub && humhub.require('ui.modal');
            if (modal && modal.confirm) { return Promise.resolve(modal.confirm({ body: frage })); }
        } catch (x) { /* weiter mit dem Browser-Fenster */ }
        return Promise.resolve(window.confirm(frage));
    }
    // ── Hochladen mit Fortschritt (Rueckmeldung eines Mitglieds, 26.10.2026: "den Hochladeprozess nicht verfolgen") ──
    // Zeigt vorher, was ausgewaehlt ist, und beim Hochladen einen Balken mit Prozent; danach laedt die Seite neu.
    function mb(bytes) { return (bytes / 1048576).toFixed(1).replace('.', ','); }
    function vorlage(text, werte) {
        return (text || '').replace(/\{(\w+)\}/g, function (all, name) { return werte[name] !== undefined ? werte[name] : all; });
    }
    document.addEventListener('change', function (e) {
        var feld = e.target;
        if (!feld.matches || !feld.matches('form.nx-pj-hochladen input[type=file]')) { return; }
        var form = feld.closest('form');
        var anzeige = form.querySelector('.nx-pj-gewaehlt');
        if (!anzeige) { return; }
        var dateien = Array.prototype.slice.call(feld.files || []);
        if (dateien.length === 0) { anzeige.hidden = true; anzeige.textContent = ''; return; }
        var summe = dateien.reduce(function (s, d) { return s + d.size; }, 0);
        var max = parseInt(form.getAttribute('data-nx-pj-max') || '10', 10);
        var text = vorlage(form.getAttribute('data-nx-pj-t-gewaehlt'), { n: dateien.length, mb: mb(summe) })
            + ': ' + dateien.slice(0, 12).map(function (d) { return d.name; }).join(', ') + (dateien.length > 12 ? ' …' : '');
        if (dateien.length > max) { text += ' ' + vorlage(form.getAttribute('data-nx-pj-t-zuviele'), { max: max }); }
        anzeige.textContent = text;
        anzeige.hidden = false;
    });
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.classList || !form.classList.contains('nx-pj-hochladen') || !window.XMLHttpRequest || !window.FormData) { return; }
        var feld = form.querySelector('input[type=file]');
        if (!feld || !feld.files || feld.files.length === 0) { return; }
        e.preventDefault();
        var knopf = form.querySelector('button[type=submit]');
        var kasten = form.querySelector('.nx-pj-fortschritt');
        var balken = kasten && kasten.querySelector('.nx-pj-balken span');
        var text = kasten && kasten.querySelector('.nx-pj-fortschritt-text');
        function melden(t, fehler) {
            if (!kasten) { return; }
            kasten.hidden = false;
            kasten.classList.toggle('nx-pj-fehler', !!fehler);
            if (text) { text.textContent = t; }
        }
        function zurueck() {
            if (knopf) { knopf.disabled = false; }
        }
        if (knopf) { knopf.disabled = true; }
        melden(vorlage(form.getAttribute('data-nx-pj-t-laeuft'), { p: 0, a: '0,0', b: '…' }));
        if (balken) { balken.style.width = '0%'; }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.getAttribute('action') || window.location.href);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.onprogress = function (ev) {
            if (!ev.lengthComputable) { return; }
            var p = Math.min(100, Math.round(100 * ev.loaded / ev.total));
            if (balken) { balken.style.width = p + '%'; }
            melden(p >= 100
                ? form.getAttribute('data-nx-pj-t-verarbeite')
                : vorlage(form.getAttribute('data-nx-pj-t-laeuft'), { p: p, a: mb(ev.loaded), b: mb(ev.total) }));
        };
        xhr.onload = function () {
            var antwort = null;
            try { antwort = JSON.parse(xhr.responseText); } catch (x) { /* keine JSON-Antwort */ }
            if (xhr.status === 200 && antwort && antwort.url) {
                // Die Meldung ("Fassung 2 hochgeladen") steht schon in der Sitzung und erscheint nach dem Neuladen
                var ziel = String(antwort.url);
                if (ziel.split('#')[0] === window.location.href.split('#')[0]) { window.location.reload(); }
                else { window.location.href = ziel; }
                return;
            }
            melden(form.getAttribute('data-nx-pj-t-fehler'), true);
            zurueck();
        };
        xhr.onerror = xhr.ontimeout = function () { melden(form.getAttribute('data-nx-pj-t-fehler'), true); zurueck(); };
        xhr.send(new FormData(form));
    });
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var frage = form.getAttribute('data-nx-pj-frage');
        if (!frage || form.getAttribute('data-nx-pj-bestaetigt') === '1') { return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        portalRueckfrage(frage).then(function (ja) {
            if (!ja) { return; }
            form.setAttribute('data-nx-pj-bestaetigt', '1');
            if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
        });
    }, true);
})();
