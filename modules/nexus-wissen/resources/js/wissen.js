/* Wissenssammlungen im Browser:
   - Formulare "ergaenzen / aendern / im Kreis sprechen" auf- und zuklappen
   - Rueckfragen vor dem Loeschen (data-nx-ws-bestaetigen="Frage")
   - Editor: Tab-Taste rueckt ein, Knoepfe zum Formatieren, Vorschau neben dem Text
   Alles ueber Delegation am Dokument, weil das Portal Seiten ohne Neuladen wechselt (PJAX). Kein
   onclick= im HTML: die Sicherheitsregel des Portals (CSP mit nonce) verwirft so etwas still. */
(function () {
    if (window.nexusWissenGeladen) { return; }
    window.nexusWissenGeladen = true;

    var EINRUECKEN = '    ';
    var VORSCHAU_WARTEN = 600;
    var escGedrueckt = false;
    var vorschauUhr = null;
    var vorschauNummer = 0;

    // ── Vorschau ──────────────────────────────────────────────────────────
    function vorschauFeld(editor) {
        var rahmen = editor.closest('.nx-ws-editor-rahmen');
        return rahmen ? rahmen.querySelector('.nx-ws-vorschau') : null;
    }

    function vorschauLaden(editor) {
        var feld = vorschauFeld(editor);
        var adresse = editor.getAttribute('data-nx-ws-vorschau');
        if (!feld || !adresse) { return; }
        if (editor.value.trim() === '') {
            feld.innerHTML = '';
            feld.setAttribute('data-leer-zeigen', '1');
            return;
        }
        var nummer = ++vorschauNummer;
        var daten = new FormData();
        daten.append('inhalt', editor.value);
        csrfDazu(daten);
        fetch(adresse, { method: 'POST', body: daten, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (antwort) { return antwort.ok ? antwort.json() : null; })
            .then(function (json) {
                // Nur die Antwort auf die letzte Anfrage zeigen -- aeltere koennen spaeter ankommen
                if (!json || nummer !== vorschauNummer) { return; }
                feld.removeAttribute('data-leer-zeigen');
                feld.innerHTML = json.html; // auf dem Server mit HtmlPurifier gereinigt (Darstellung::nurText)
            })
            .catch(function () { /* Vorschau ist eine Hilfe -- ohne sie geht das Schreiben weiter */ });
    }

    function vorschauBald(editor) {
        clearTimeout(vorschauUhr);
        vorschauUhr = setTimeout(function () { vorschauLaden(editor); }, VORSCHAU_WARTEN);
    }

    function alleVorschauenLaden() {
        document.querySelectorAll('textarea.nx-ws-editor[data-nx-ws-vorschau]').forEach(function (editor) {
            if (!editor.getAttribute('data-nx-ws-geladen')) {
                editor.setAttribute('data-nx-ws-geladen', '1');
                vorschauLaden(editor);
            }
        });
    }

    // ── Text im Editor veraendern ─────────────────────────────────────────
    function ersetzen(editor, von, bis, neu, auswahlVon, auswahlBis) {
        editor.focus();
        editor.setSelectionRange(von, bis);
        // execCommand behaelt "Rueckgaengig" (Strg+Z); wo es fehlt, direkt setzen
        if (!document.execCommand || !document.execCommand('insertText', false, neu)) {
            editor.value = editor.value.slice(0, von) + neu + editor.value.slice(bis);
        }
        editor.setSelectionRange(auswahlVon, auswahlBis);
        editor.dispatchEvent(new Event('input', { bubbles: true }));
    }

    /** Beginn der ersten und Ende der letzten markierten Zeile */
    function zeilen(editor) {
        var t = editor.value;
        var von = t.lastIndexOf('\n', editor.selectionStart - 1) + 1;
        var bis = t.indexOf('\n', editor.selectionEnd);
        if (bis === -1) { bis = t.length; }
        return { von: von, bis: bis, text: t.slice(von, bis) };
    }

    function einruecken(editor, zurueck) {
        var a = editor.selectionStart;
        var b = editor.selectionEnd;
        if (!zurueck && a === b) {
            ersetzen(editor, a, b, EINRUECKEN, a + EINRUECKEN.length, a + EINRUECKEN.length);
            return;
        }
        var z = zeilen(editor);
        var neu = z.text.split('\n').map(function (zeile) {
            return zurueck ? zeile.replace(/^( {1,4}|\t)/, '') : EINRUECKEN + zeile;
        }).join('\n');
        ersetzen(editor, z.von, z.bis, neu, z.von, z.von + neu.length);
    }

    function umschliessen(editor, vorn, hinten, platzhalter) {
        var a = editor.selectionStart;
        var b = editor.selectionEnd;
        var innen = editor.value.slice(a, b) || platzhalter;
        ersetzen(editor, a, b, vorn + innen + hinten, a + vorn.length, a + vorn.length + innen.length);
    }

    function zeilenAnfang(editor, zeichen) {
        var z = zeilen(editor);
        var nr = 0;
        var neu = z.text.split('\n').map(function (zeile) {
            if (zeile.trim() === '' && z.text.indexOf('\n') !== -1) { return zeile; }
            var ohne = zeile.replace(/^(#{1,6} |- |\d+\. )/, '');
            nr++;
            return (zeichen === '1.' ? nr + '. ' : zeichen) + ohne;
        }).join('\n');
        ersetzen(editor, z.von, z.bis, neu, z.von + neu.length, z.von + neu.length);
    }

    function formatieren(editor, art) {
        var auswahl = editor.value.slice(editor.selectionStart, editor.selectionEnd);
        switch (art) {
            case 'h2': zeilenAnfang(editor, '## '); break;
            case 'h3': zeilenAnfang(editor, '### '); break;
            case 'fett': umschliessen(editor, '**', '**', 'fett'); break;
            case 'kursiv': umschliessen(editor, '*', '*', 'kursiv'); break;
            case 'liste': zeilenAnfang(editor, '- '); break;
            case 'nummer': zeilenAnfang(editor, '1.'); break;
            case 'link': umschliessen(editor, '[', '](https://)', 'Linktext'); break;
            case 'code':
                if (auswahl.indexOf('\n') !== -1) { umschliessen(editor, '\n```\n', '\n```\n', ''); }
                else { umschliessen(editor, '`', '`', 'befehl'); }
                break;
            case 'bild': {
                var eingabe = bildEingabe(editor);
                if (eingabe) { eingabe.click(); }
                break;
            }
            case 'tabelle': {
                var p = editor.selectionEnd;
                var vorlage = '\n\n| Spalte 1 | Spalte 2 |\n| --- | --- |\n| Inhalt | Inhalt |\n\n';
                ersetzen(editor, p, p, vorlage, p + 4, p + 12);
                break;
            }
        }
    }

    // ── Bilder: Knopf, Einfuegen (Strg+V) und Hineinziehen ────────────────
    function csrfDazu(daten) {
        var token = document.querySelector('meta[name="csrf-token"]');
        var param = document.querySelector('meta[name="csrf-param"]');
        if (token && param) { daten.append(param.getAttribute('content'), token.getAttribute('content')); }
    }

    function bildEingabe(editor) {
        var leiste = document.querySelector('[aria-controls="' + editor.id + '"]');
        return leiste ? leiste.querySelector('.nx-ws-bild-datei') : null;
    }

    function bildHochladen(editor, datei) {
        var eingabe = bildEingabe(editor);
        if (!eingabe || !datei || !/^image\//.test(datei.type)) { return; }
        var stelle = editor.selectionEnd;
        var hinweis = eingabe.parentNode.querySelector('.nx-ws-tab-hinweis');
        var vorher = hinweis ? hinweis.textContent : '';
        if (hinweis) { hinweis.textContent = eingabe.getAttribute('data-nx-ws-laedt'); }
        var daten = new FormData();
        daten.append('bild', datei, datei.name || 'bild.png');
        csrfDazu(daten);
        fetch(eingabe.getAttribute('data-nx-ws-bild-ziel'), { method: 'POST', body: daten, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (antwort) { return antwort.json(); })
            .then(function (json) {
                if (json && json.markdown) {
                    var baustein = '\n\n' + json.markdown + '\n\n';
                    ersetzen(editor, stelle, stelle, baustein, stelle + baustein.length, stelle + baustein.length);
                } else if (json && json.fehler) {
                    window.alert(json.fehler);
                }
            })
            .catch(function () { window.alert('Das Bild konnte nicht hochgeladen werden.'); })
            .then(function () { if (hinweis) { hinweis.textContent = vorher; } });
    }

    document.addEventListener('change', function (e) {
        if (!e.target.classList || !e.target.classList.contains('nx-ws-bild-datei')) { return; }
        var editor = document.getElementById(e.target.closest('[aria-controls]').getAttribute('aria-controls'));
        Array.prototype.forEach.call(e.target.files || [], function (datei) { bildHochladen(editor, datei); });
        e.target.value = '';
    });

    document.addEventListener('paste', function (e) {
        var editor = e.target;
        if (!editor.classList || !editor.classList.contains('nx-ws-editor') || !e.clipboardData) { return; }
        var bilder = Array.prototype.filter.call(e.clipboardData.files || [], function (d) { return /^image\//.test(d.type); });
        if (bilder.length === 0) { return; }   // normaler Text: Browser fuegt ihn selbst ein
        e.preventDefault();
        bilder.forEach(function (datei) { bildHochladen(editor, datei); });
    });

    document.addEventListener('dragover', function (e) {
        if (e.target.classList && e.target.classList.contains('nx-ws-editor')) { e.preventDefault(); }
    });

    document.addEventListener('drop', function (e) {
        var editor = e.target;
        if (!editor.classList || !editor.classList.contains('nx-ws-editor') || !e.dataTransfer) { return; }
        var bilder = Array.prototype.filter.call(e.dataTransfer.files || [], function (d) { return /^image\//.test(d.type); });
        if (bilder.length === 0) { return; }
        e.preventDefault();
        bilder.forEach(function (datei) { bildHochladen(editor, datei); });
    });

    // ── Ereignisse ────────────────────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        var editor = e.target;
        if (!editor.classList || !editor.classList.contains('nx-ws-editor')) { return; }
        if (e.key === 'Escape') { escGedrueckt = true; return; }
        if (e.key === 'Tab' && !e.ctrlKey && !e.altKey && !e.metaKey) {
            // Nach Esc darf Tab wie gewohnt zum naechsten Feld springen (Tastatur-Bedienung)
            if (escGedrueckt) { escGedrueckt = false; return; }
            e.preventDefault();
            einruecken(editor, e.shiftKey);
            return;
        }
        escGedrueckt = false;
    });

    document.addEventListener('input', function (e) {
        if (e.target.classList && e.target.classList.contains('nx-ws-editor')) { vorschauBald(e.target); }
    });

    // Rueckfrage im Fenster des Portals statt window.confirm: Firefox kann Browser-Dialoge fuer eine
    // Seite abschalten, danach antwortet confirm() still mit "Abbrechen" und der Knopf tut nichts.
    function portalFrage(text) {
        try {
            var modal = window.humhub && humhub.require('ui.modal');
            if (modal && modal.confirm) { return Promise.resolve(modal.confirm({ body: text })); }
        } catch (x) { /* weiter mit dem Browser-Fenster */ }
        return Promise.resolve(window.confirm(text));
    }

    // ── Link weitergeben ──────────────────────────────────────────────────
    // Handy: das Teilen-Menue des Geraets (Signal, WhatsApp, E-Mail ...). PC: Link in die Zwischenablage.
    // Klappt beides nicht (aeltere Browser, kein https), erscheint der Link zum Selbst-Kopieren -- ohne Dialog,
    // denn Browser koennen Dialoge fuer eine Seite abschalten.
    function linkWeitergeben(knopf) {
        var adresse = knopf.getAttribute('data-nx-ws-kopieren');
        var titel = knopf.getAttribute('data-nx-ws-titel') || '';
        var beschriftung = knopf.querySelector('span');
        function bestaetigen() {
            if (!beschriftung) { return; }
            var vorher = beschriftung.textContent;
            beschriftung.textContent = knopf.getAttribute('data-nx-ws-kopiert') || vorher;
            setTimeout(function () { beschriftung.textContent = vorher; }, 2500);
        }
        function feldZeigen() {
            var id = knopf.getAttribute('data-nx-ws-feld');
            var zeile = id ? document.getElementById(id) : null;
            if (!zeile) { return; }
            zeile.hidden = false;
            var feld = zeile.querySelector('input');
            if (feld) { feld.focus(); feld.select(); }
        }
        var handy = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
        if (handy && navigator.share) {
            navigator.share({ title: titel, url: adresse }).catch(function () { /* abgebrochen */ });
            return;
        }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(adresse).then(bestaetigen, feldZeigen);
            return;
        }
        feldZeigen();
    }

    document.addEventListener('click', function (e) {
        var frage = e.target.closest('[data-nx-ws-bestaetigen]');
        if (frage && frage.getAttribute('data-nx-ws-bestaetigt') !== '1') {
            e.preventDefault();
            e.stopPropagation();
            portalFrage(frage.getAttribute('data-nx-ws-bestaetigen')).then(function (ja) {
                if (!ja) { return; }
                frage.setAttribute('data-nx-ws-bestaetigt', '1');
                frage.click();
                frage.removeAttribute('data-nx-ws-bestaetigt');
            });
            return;
        }
        var kopieren = e.target.closest('[data-nx-ws-kopieren]');
        if (kopieren) {
            e.preventDefault();
            linkWeitergeben(kopieren);
            return;
        }
        var format = e.target.closest('[data-nx-ws-format]');
        if (format) {
            e.preventDefault();
            var editor = document.getElementById(format.closest('[aria-controls]').getAttribute('aria-controls'));
            if (editor) { formatieren(editor, format.getAttribute('data-nx-ws-format')); }
            return;
        }
        var knopf = e.target.closest('[data-nx-ws-oeffne]');
        if (knopf) {
            e.preventDefault();
            var ziel = document.getElementById(knopf.getAttribute('data-nx-ws-oeffne'));
            if (!ziel) { return; }
            document.querySelectorAll('.nx-ws-formular').forEach(function (f) { if (f !== ziel) { f.hidden = true; } });
            ziel.hidden = !ziel.hidden;
            var art = knopf.getAttribute('data-nx-ws-art');
            if (art) { ziel.querySelector('input[name="art"]').value = art; }
            if (!ziel.hidden) { var feld = ziel.querySelector('textarea'); if (feld) { feld.focus(); } }
            return;
        }
        var zu = e.target.closest('[data-nx-ws-schliesse]');
        if (zu) {
            e.preventDefault();
            var form = zu.closest('.nx-ws-formular');
            if (form) { form.hidden = true; }
        }
    });

    // Vorschau beim Oeffnen der Seite -- auch nach einem Seitenwechsel ohne Neuladen
    if (document.readyState !== 'loading') { alleVorschauenLaden(); }
    document.addEventListener('DOMContentLoaded', alleVorschauenLaden);
    if (window.jQuery) { window.jQuery(document).on('humhub:ready', alleVorschauenLaden); }
    setTimeout(alleVorschauenLaden, 300);
})();
