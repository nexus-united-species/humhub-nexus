/* Projekt-Tafeln: Feld fuer den Aenderungswunsch auf- und zuklappen, Rueckfrage vor dem Loeschen.
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
