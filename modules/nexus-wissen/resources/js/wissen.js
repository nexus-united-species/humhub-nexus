/* Wissenssammlungen: Formulare "ergaenzen / aendern / im Kreis sprechen" auf- und zuklappen.
   Ueber Delegation am Dokument, weil das Portal Seiten ohne Neuladen wechselt (PJAX). */
(function () {
    if (window.nexusWissenGeladen) { return; }
    window.nexusWissenGeladen = true;
    document.addEventListener('click', function (e) {
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
})();
