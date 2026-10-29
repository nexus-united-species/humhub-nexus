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
