/*
 * Rueckfrage vor Loeschen & Co. -- im Fenster des Portals, nicht als Browser-Dialog.
 *
 * Verwendung: data-bestaetigen="Frage" an einem Formular (vor dem Absenden) oder an einem Knopf/Link
 * (vor dem Klick). Eingebunden ueber $this->registerJs(...), damit das Portal den nonce anhaengt --
 * Code direkt im HTML (onsubmit="return confirm(...)") verwirft die Sicherheitsregel still, dann wurde
 * ohne Rueckfrage geloescht. window.confirm nur als Rueckfall: Firefox kann Browser-Dialoge fuer eine
 * Seite abschalten, danach antwortet confirm() still mit "Abbrechen".
 *
 * Dieselbe Datei liegt in mehreren Modulen (jedes Modul soll allein funktionieren); der Schalter
 * window.nexusBestaetigen sorgt dafuer, dass sie nur einmal wirkt.
 */
(function () {
    if (window.nexusBestaetigen) { return; }
    window.nexusBestaetigen = true;

    function frage(text) {
        try {
            var modal = window.humhub && humhub.require('ui.modal');
            if (modal && modal.confirm) { return Promise.resolve(modal.confirm({ body: text })); }
        } catch (x) { /* weiter mit dem Browser-Fenster */ }
        return Promise.resolve(window.confirm(text));
    }

    document.addEventListener('click', function (e) {
        var k = e.target.closest && e.target.closest('[data-bestaetigen]');
        if (!k || k.tagName === 'FORM' || k.getAttribute('data-bestaetigt') === '1') { return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        frage(k.getAttribute('data-bestaetigen')).then(function (ja) {
            if (!ja) { return; }
            k.setAttribute('data-bestaetigt', '1');
            k.click();
            k.removeAttribute('data-bestaetigt');
        });
    }, true);

    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f.hasAttribute || !f.hasAttribute('data-bestaetigen') || f.getAttribute('data-bestaetigt') === '1') { return; }
        e.preventDefault();
        e.stopImmediatePropagation();
        frage(f.getAttribute('data-bestaetigen')).then(function (ja) {
            if (!ja) { return; }
            f.setAttribute('data-bestaetigt', '1');
            if (f.requestSubmit) { f.requestSubmit(); } else { f.submit(); }
        });
    }, true);
})();
