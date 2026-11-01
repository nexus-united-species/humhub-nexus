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
    // Zu grosse Dateien gleich sagen: Sonst laedt jemand minutenlang hoch, und erst danach kommt die Absage.
    function zuGross(form, dateien) {
        var grenze = parseInt(form.getAttribute('data-nx-pj-max-bytes') || '0', 10);
        return grenze > 0 ? dateien.filter(function (d) { return d.size > grenze; }).map(function (d) {
            return vorlage(form.getAttribute('data-nx-pj-t-zugross'), { name: d.name, max: form.getAttribute('data-nx-pj-max-mb') });
        }).join(' ') : '';
    }
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
        var hinweis = zuGross(form, dateien);
        if (hinweis) { text += ' ' + hinweis; }
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
        var absage = zuGross(form, Array.prototype.slice.call(feld.files));
        if (absage) { melden(absage, true); return; }
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
    // Uebersetzen auf Knopfdruck: holt je Text die Uebersetzung (hoechstens drei gleichzeitig) und tauscht sie aus.
    // Original und Uebersetzung bleiben am Element, ein weiterer Klick schaltet ohne neue Anfrage um.
    var TR_GLEICHZEITIG = 3;
    function trBereich(knopf) {
        return knopf.closest('.nx-pj-detail') || knopf.closest('.nx-pj');
    }
    function trZeigen(el, uebersetzt) {
        var inhalt = uebersetzt ? el.nxPjUebersetzt : el.nxPjOriginal;
        if (!inhalt) { return; }
        if (inhalt.html !== undefined) { el.innerHTML = inhalt.html; } else { el.textContent = inhalt.text; }
        el.setAttribute('data-nx-pj-tr-an', uebersetzt ? '1' : '0');
    }
    function trBeschriften() {
        document.querySelectorAll('[data-nx-pj-tr-knopf]').forEach(function (k) {
            if (k.disabled) { return; }
            var an = trBereich(k).querySelector('[data-nx-pj-tr-an="1"]') !== null;
            k.querySelector('span').textContent = k.getAttribute(an ? 'data-t-aus' : 'data-t-an');
            var hinweis = k.parentNode.querySelector('.nx-pj-tr-hinweis');
            if (!an && !hinweis.hasAttribute('data-fehler')) { hinweis.hidden = true; }
            if (an && !hinweis.hasAttribute('data-fehler')) { hinweis.textContent = k.getAttribute('data-t-hinweis'); hinweis.hidden = false; }
        });
    }
    function trHolen(k, el) {
        var teile = el.getAttribute('data-nx-pj-tr').split(':');
        var koerper = new URLSearchParams();
        koerper.set('art', teile[0]);
        koerper.set('id', teile[1]);
        var meta = document.querySelector('meta[name="csrf-token"]');
        return fetch(k.getAttribute('data-url'), {
            method: 'POST', body: koerper, credentials: 'same-origin',
            headers: { 'X-CSRF-Token': meta ? meta.getAttribute('content') : '', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.ok ? r.json() : Promise.reject(new Error(String(r.status)));
        }).then(function (daten) {
            if (daten.html !== undefined) {
                el.nxPjOriginal = { html: el.innerHTML };
                el.nxPjUebersetzt = { html: daten.html };
            } else {
                el.nxPjOriginal = { text: el.textContent };
                el.nxPjUebersetzt = { text: daten.text };
            }
            trZeigen(el, true);
        });
    }
    document.addEventListener('click', function (e) {
        var k = e.target.closest('[data-nx-pj-tr-knopf]');
        if (!k || k.disabled) { return; }
        e.preventDefault();
        var elemente = Array.prototype.slice.call(trBereich(k).querySelectorAll('[data-nx-pj-tr]'));
        var hinweis = k.parentNode.querySelector('.nx-pj-tr-hinweis');
        hinweis.removeAttribute('data-fehler');
        if (elemente.some(function (el) { return el.getAttribute('data-nx-pj-tr-an') === '1'; })) {
            elemente.forEach(function (el) { trZeigen(el, false); });
            trBeschriften();
            return;
        }
        var offen = [];
        elemente.forEach(function (el) {
            if (el.nxPjUebersetzt) { trZeigen(el, true); } else { offen.push(el); }
        });
        var gesamt = offen.length, fertig = 0, fehler = 0;
        function stand() {
            hinweis.textContent = k.getAttribute('data-t-laeuft').replace('{a}', fertig).replace('{b}', gesamt);
            hinweis.hidden = false;
        }
        function schluss() {
            k.disabled = false;
            if (fehler > 0) {
                hinweis.setAttribute('data-fehler', '1');
                hinweis.textContent = k.getAttribute('data-t-fehler');
                hinweis.hidden = false;
            }
            trBeschriften();
        }
        if (gesamt === 0) { trBeschriften(); return; }
        k.disabled = true;
        stand();
        var laufend = 0;
        function weiter() {
            while (laufend < TR_GLEICHZEITIG && offen.length > 0) {
                laufend++;
                trHolen(k, offen.shift()).catch(function () { fehler++; }).then(function () {
                    laufend--;
                    fertig++;
                    stand();
                    if (offen.length === 0 && laufend === 0) { schluss(); } else { weiter(); }
                });
            }
        }
        weiter();
    });
})();
