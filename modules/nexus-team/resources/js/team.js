/* Community-Team: "Text kopieren" -- ohne Inline-Code (CSP), per Delegation. */
(function () {
    'use strict';
    if (window.nexusTeamGeladen) { return; }
    window.nexusTeamGeladen = true;

    function kopiere(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (ok, fehler) {
            var feld = document.createElement('textarea');
            feld.value = text;
            feld.setAttribute('readonly', '');
            feld.style.position = 'fixed';
            feld.style.opacity = '0';
            document.body.appendChild(feld);
            feld.select();
            try { document.execCommand('copy') ? ok() : fehler(); } catch (e) { fehler(e); }
            document.body.removeChild(feld);
        });
    }

    document.addEventListener('click', function (ev) {
        var knopf = ev.target.closest('[data-nx-team-kopieren]');
        if (!knopf) { return; }
        ev.preventDefault();
        var text = knopf.getAttribute('data-nx-team-kopieren');
        var alt = knopf.textContent;
        kopiere(text).then(function () {
            knopf.textContent = knopf.getAttribute('data-nx-team-kopiert') || '✓';
            setTimeout(function () { knopf.textContent = alt; }, 2500);
        }, function () {
            window.prompt('Strg+C', text);
        });
    });
})();

/* Rueckfrage im Portal-Fenster statt window.confirm (Firefox kann Browser-Dialoge abschalten -- dann kaeme "Abbrechen"). */
(function () {
    'use strict';
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        var knopf = ev.submitter || form.querySelector('[data-nx-team-frage]');
        var frage = knopf && knopf.getAttribute ? knopf.getAttribute('data-nx-team-frage') : null;
        if (!frage || form.getAttribute('data-nx-team-bestaetigt')) { return; }
        ev.preventDefault();
        var weiter = function () {
            form.setAttribute('data-nx-team-bestaetigt', '1');
            if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
        };
        try {
            var modal = window.humhub && window.humhub.require('ui.modal');
            if (modal && modal.confirm) {
                modal.confirm({ body: frage }).then(function (ok) { if (ok) { weiter(); } });
                return;
            }
        } catch (e) { /* Rueckfall unten */ }
        if (window.confirm(frage)) { weiter(); }
    }, true);
})();
