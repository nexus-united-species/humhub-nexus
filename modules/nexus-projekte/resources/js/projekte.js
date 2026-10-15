/* Projekt-Tafeln: Felder auf- und zuklappen, Rueckfrage vor dem Loeschen, weitere Textaenderung im Vorschlag.
   Ueber Delegation am Dokument, weil das Portal Seiten ohne Neuladen wechselt (PJAX). */
(function () {
    if (window.nexusProjekteGeladen) { return; }
    window.nexusProjekteGeladen = true;
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
    document.addEventListener('submit', function (e) {
        var frage = e.target.getAttribute('data-nx-pj-frage');
        if (frage && !window.confirm(frage)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);
})();
