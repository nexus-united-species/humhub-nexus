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
        var token = document.querySelector('meta[name="csrf-token"]');
        var param = document.querySelector('meta[name="csrf-param"]');
        if (token && param) { daten.append(param.getAttribute('content'), token.getAttribute('content')); }
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
            case 'tabelle': {
                var p = editor.selectionEnd;
                var vorlage = '\n\n| Spalte 1 | Spalte 2 |\n| --- | --- |\n| Inhalt | Inhalt |\n\n';
                ersetzen(editor, p, p, vorlage, p + 4, p + 12);
                break;
            }
        }
    }

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

    document.addEventListener('click', function (e) {
        var frage = e.target.closest('[data-nx-ws-bestaetigen]');
        if (frage && !window.confirm(frage.getAttribute('data-nx-ws-bestaetigen'))) {
            e.preventDefault();
            e.stopPropagation();
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
