(function () {
    'use strict';

    if (typeof Notification === 'undefined' || Notification.__nexusUebernommen) {
        return;
    }

    var SPEICHER_SCHLUESSEL = 'nexusNotifHinweisErledigt';
    var echteBrowserAnfrage = Notification.requestPermission.bind(Notification);

    function zeigeHinweis() {
        return new Promise(function (resolve) {
            // Schutz gegen doppeltes Einblenden -- falls Notification.requestPermission()
            // aus irgendeinem Grund zweimal kurz hintereinander aufgerufen wird
            // (z.B. sowohl vom passiven Seitenaufruf als auch von einem
            // ausdruecklichen "Benachrichtigungen aktivieren"-Knopf), wuerde
            // sonst ein zweites, unsichtbar ueberlagerndes Fenster entstehen,
            // dessen Knoepfe dann nicht mehr erreichbar waeren.
            var vorhanden = document.getElementById('nexus-notif-hinweis');
            if (vorhanden) {
                vorhanden.remove();
            }

            var vorlage = document.createElement('div');
            vorlage.id = 'nexus-notif-hinweis';
            vorlage.setAttribute('role', 'dialog');
            vorlage.style.cssText = [
                // Oben statt unten platziert: auf dem Handy liegt am unteren
                // Bildschirmrand oft eine feste Navigationsleiste von HumHub
                // selbst -- ein Fenster dort ueberlappt sie und Antippen trifft
                // dann die Leiste darunter statt unsere Schaltflaechen (Meldung
                // von einem Mitglied am iPhone, 09.09.2026: Fenster erscheint, laesst
                // sich aber nicht bedienen).
                'position:fixed', 'top:20px', 'right:20px', 'left:20px',
                'max-width:360px', 'margin-left:auto',
                'background:#0A1628', 'color:#EDEFF3',
                'padding:18px 20px', 'border-radius:12px',
                'border-top:3px solid #D4AF37',
                'box-shadow:0 12px 40px rgba(0,0,0,.35)',
                // Hoechstmoeglicher Wert statt einer beliebigen hohen Zahl --
                // schliesst zuverlaessig aus, dass irgendein anderes Element
                // der Seite (z.B. eine mobile Navigationsleiste) trotzdem
                // darueber liegt.
                'z-index:2147483647', 'font-size:14px', 'line-height:1.5',
                'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif'
            ].join(';');

            var knopfStil = 'border:none;padding:9px 16px;border-radius:6px;cursor:pointer;'
                // touch-action verhindert, dass iOS/Safari ein Antippen als
                // Scroll-/Zoom-Geste statt als Klick deutet -- bekannte Ursache
                // fuer "Fenster erscheint, reagiert aber nicht" auf dem iPhone.
                + 'touch-action:manipulation;-webkit-tap-highlight-color:transparent;'
                + 'user-select:none;-webkit-user-select:none;pointer-events:auto;';

            vorlage.innerHTML =
                '<div style="margin-bottom:14px;">'
                + '<strong>🔔 Nichts mehr verpassen?</strong><br>'
                + 'Aktiviert Benachrichtigungen, damit ihr mitbekommt, wenn sich in euren Kreisen etwas tut.'
                + '</div>'
                + '<button type="button" id="nexus-notif-ja" style="' + knopfStil
                + 'background:#D4AF37;color:#0A1628;font-weight:600;margin-right:10px;">'
                + 'Aktivieren</button>'
                + '<button type="button" id="nexus-notif-nein" style="' + knopfStil
                + 'background:transparent;color:#9AA6B8;padding:9px 8px;">Nicht jetzt</button>';

            document.body.appendChild(vorlage);

            document.getElementById('nexus-notif-ja').addEventListener('click', function () {
                vorlage.remove();
                window.localStorage.setItem(SPEICHER_SCHLUESSEL, '1');
                resolve(echteBrowserAnfrage());
            });
            document.getElementById('nexus-notif-nein').addEventListener('click', function () {
                vorlage.remove();
                window.localStorage.setItem(SPEICHER_SCHLUESSEL, '1');
                resolve('default');
            });
        });
    }

    // Faengt die Browser-eigene Funktion selbst ab, statt eine bestimmte
    // Anwendungsfunktion zu ueberschreiben (siehe Events.php fuer die
    // ausfuehrliche Begruendung des ersten, verworfenen Ansatzes: der war
    // von der Ladereihenfolge der Skripte abhaengig und griff deshalb nur
    // unzuverlaessig). Egal welches Modul spaeter
    // Notification.requestPermission() aufruft -- unser Hinweis kommt
    // garantiert zuerst, weil wir die Funktion selbst austauschen, bevor
    // irgendjemand sie tatsaechlich aufruft.
    Notification.requestPermission = function (rueckruf) {
        var schonEntschieden = Notification.permission !== 'default';
        var schonGesehen = window.localStorage.getItem(SPEICHER_SCHLUESSEL);

        if (schonEntschieden || schonGesehen) {
            return echteBrowserAnfrage(rueckruf);
        }

        var ergebnis = zeigeHinweis();
        if (typeof rueckruf === 'function') {
            ergebnis.then(rueckruf);
        }
        return ergebnis;
    };
    Notification.__nexusUebernommen = true;
})();
