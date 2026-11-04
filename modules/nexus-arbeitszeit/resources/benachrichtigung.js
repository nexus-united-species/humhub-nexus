/*
 * Benachrichtigungen des Assistenten (siehe services/BenachrichtigungAnzeige.php). Der Server haengt
 * Knoepfe hinter jede Nachricht und einen Hinweis ans Ende der Unterhaltung; das Antwortfeld blendet
 * benachrichtigung.css aus. Dieses Skript rueckt beides nur an die richtige Stelle: die Knoepfe in die
 * Sprechblase der Nachricht, den Hinweis dorthin, wo sonst das Antwortfeld steht. Ohne Skript bleibt
 * alles sichtbar, nur etwas weniger schoen platziert.
 */
(function () {
    'use strict';
    var timer = null;

    function einordnen() {
        timer = null;
        document.querySelectorAll('.nx-bn-knoepfe:not(.nx-bn-fertig)').forEach(function (k) {
            var id = k.getAttribute('data-nx-bn-eintrag');
            var ziel = document.querySelector('.mail-conversation-entry[data-entry-id="' + id + '"] .conversation-entry-content');
            if (ziel) {
                ziel.appendChild(k);
            }
            k.classList.add('nx-bn-fertig');
        });
        document.querySelectorAll('.nx-bn-hinweis:not(.nx-bn-fertig)').forEach(function (h) {
            var behaelter = h.closest('.conversation-entry-container');
            var form = behaelter && behaelter.parentNode.querySelector('.mail-message-form');
            if (form) {
                // Ein frueherer Hinweis (z. B. nach dem Nachladen) wird ersetzt, nicht verdoppelt
                behaelter.parentNode.querySelectorAll('.nx-bn-hinweis.nx-bn-fertig').forEach(function (alt) { alt.remove(); });
                form.parentNode.insertBefore(h, form);
            }
            h.classList.add('nx-bn-fertig');
        });
    }

    new MutationObserver(function () {
        if (timer === null) { timer = setTimeout(einordnen, 100); }
    }).observe(document.body, { childList: true, subtree: true });
    einordnen();
})();
