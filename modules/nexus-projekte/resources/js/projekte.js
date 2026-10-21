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
