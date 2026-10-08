/*
 * N.E.X.U.S. Weitergeben (02.10.2026).
 *
 * "Weitergeben" unter einem Beitrag:
 *   - Oeffentlich lesbar: Handy = Teilen-Menue des Handys (Signal, WhatsApp, ...), PC = Text + Link
 *     in die Zwischenablage. Klappt beides nicht, kleines Fenster mit "Weitergeben ..." / "Kopieren".
 *   - NICHT oeffentlich: erst ein Hinweis (Josh: "muss eine Meldung kommen"), dass Aussenstehende nur
 *     die Anmeldeseite sehen und wie man den Beitrag ueber "..." oeffentlich stellt. Wer darf, kann es
 *     dort gleich mit einem Knopf tun; sonst "Nur an Mitglieder weitergeben".
 * "Oeffentlich lesbar machen" / "Freigabe aufheben" im "..."-Menue: Rueckfrage, dann speichern.
 *
 * Alle Texte kommen vom Server (in der Sprache des Menschen) -- hier steht keine Beschriftung.
 */
(function () {
    'use strict';

    var BASIS = '/nexus-teilen/teilen/';
    var vorrat = {};

    function holeInfo(id) {
        if (!vorrat[id]) {
            vorrat[id] = fetch(BASIS + 'info?id=' + encodeURIComponent(id), {
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (r) {
                if (!r.ok) { throw new Error('HTTP ' + r.status); }
                return r.json();
            }).catch(function (e) {
                delete vorrat[id];
                throw e;
            });
        }
        return vorrat[id];
    }

    function sende(aktion, id) {
        var daten = new FormData();
        daten.append('id', id);
        if (window.yii && yii.getCsrfParam) {
            daten.append(yii.getCsrfParam(), yii.getCsrfToken());
        }
        return fetch(BASIS + aktion + '?id=' + encodeURIComponent(id), {
            method: 'POST',
            body: daten,
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.json();
        });
    }

    function istHandy() {
        return !!navigator.share && window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
    }

    function meldung(text, dauer) {
        var alt = document.querySelector('.nexus-teilen-meldung');
        if (alt) { alt.remove(); }
        var el = document.createElement('div');
        el.className = 'nexus-teilen-meldung';
        el.setAttribute('role', 'status');
        el.textContent = text;
        document.body.appendChild(el);
        setTimeout(function () { el.classList.add('weg'); }, dauer || 5000);
        setTimeout(function () { el.remove(); }, (dauer || 5000) + 600);
    }

    function zusammen(info) {
        return info.text + '\n\n' + info.link;
    }

    function kopiereText(text) {
        if (navigator.clipboard && window.isSecureContext) {
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
            var geklappt = false;
            try { geklappt = document.execCommand('copy'); } catch (e) { geklappt = false; }
            feld.remove();
            if (geklappt) { ok(); } else { fehler(new Error('kopieren')); }
        });
    }

    function teileUeberHandy(info) {
        return navigator.share({title: info.titel, text: info.text, url: info.link});
    }

    /* Grundgeruest eines kleinen Fensters; jeder Knopf darin ist ein frischer Fingertipp. */
    function neuesFenster(titelText) {
        var alt = document.querySelector('.nexus-teilen-fenster');
        if (alt) { alt.remove(); }
        var huelle = document.createElement('div');
        huelle.className = 'nexus-teilen-fenster';
        huelle.setAttribute('role', 'dialog');
        huelle.setAttribute('aria-modal', 'true');
        var kasten = document.createElement('div');
        kasten.className = 'nexus-teilen-kasten';
        var titel = document.createElement('h3');
        titel.textContent = titelText;
        kasten.appendChild(titel);
        var leiste = document.createElement('div');
        leiste.className = 'nexus-teilen-knoepfe';

        function zu() {
            huelle.remove();
            document.removeEventListener('keydown', esc);
        }
        function esc(e) { if (e.key === 'Escape') { zu(); } }

        huelle.addEventListener('click', function (e) { if (e.target === huelle) { zu(); } });
        document.addEventListener('keydown', esc);
        huelle.appendChild(kasten);
        document.body.appendChild(huelle);

        return {
            kasten: kasten,
            zu: zu,
            knopf: function (text, klasse, aktion) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'btn ' + klasse;
                b.textContent = text;
                b.addEventListener('click', aktion);
                leiste.appendChild(b);
                return b;
            },
            fertig: function () {
                kasten.appendChild(leiste);
                var erster = leiste.querySelector('button');
                if (erster) { erster.focus(); }
            }
        };
    }

    /* Text + Knoepfe "Weitergeben ..." / "Kopieren" -- Ersatzweg, wenn der direkte Weg nicht ging. */
    function fenster(info) {
        var f = neuesFenster(info.texte.fenster_titel);
        var feld = document.createElement('textarea');
        feld.readOnly = true;
        feld.rows = 6;
        feld.value = zusammen(info);
        f.kasten.appendChild(feld);
        if (navigator.share) {
            f.knopf(info.texte.fenster_teilen, 'btn-primary', function () {
                teileUeberHandy(info).then(f.zu).catch(function () {});
            });
        }
        f.knopf(info.texte.fenster_kopieren, navigator.share ? 'btn-light' : 'btn-primary', function () {
            kopiereText(zusammen(info)).then(function () { f.zu(); meldung(info.texte.kopiert); }).catch(function () {
                feld.focus();
                feld.select();
            });
        });
        f.knopf(info.texte.fenster_schliessen, 'btn-light', f.zu);
        f.fertig();
    }

    /* Direkt weitergeben: Handy-Menue bzw. Kopieren, sonst Fenster. */
    function weitergeben(info) {
        if (istHandy()) {
            return teileUeberHandy(info).catch(function (e) {
                // Abgebrochen = der Mensch hat es sich anders ueberlegt; nur andere Fehler -> Fenster.
                if (!e || e.name !== 'AbortError') { fenster(info); }
            });
        }
        return kopiereText(zusammen(info)).then(function () { meldung(info.texte.kopiert); }).catch(function () { fenster(info); });
    }

    /* Nicht oeffentlich: erst erklaeren, dann entscheiden lassen. */
    function hinweis(id, info) {
        var f = neuesFenster(info.texte.intern_titel);
        var text = document.createElement('p');
        text.className = 'nexus-teilen-erklaerung';
        text.textContent = info.texte.intern_text;
        f.kasten.appendChild(text);
        if (info.darf) {
            f.knopf(info.texte.knopf_oeffentlich, 'btn-primary', function () {
                if (!window.confirm(info.texte.frage_an)) { return; }
                f.zu();
                setzeFreigabe(id, false).then(function () {
                    return holeInfo(id);
                }).then(function (neu) {
                    // Nach dem Speichern ist der Fingertipp "verbraucht" -- deshalb das Fenster
                    // mit dem oeffentlichen Link, dort ist "Weitergeben ..." ein frischer Tipp.
                    fenster(neu);
                }).catch(function () {});
            });
        }
        f.knopf(info.texte.knopf_mitglieder, info.darf ? 'btn-light' : 'btn-primary', function () {
            f.zu();
            weitergeben(info);
        });
        f.knopf(info.texte.fenster_schliessen, 'btn-light', f.zu);
        f.fertig();
    }

    function teilen(link) {
        var id = link.getAttribute('data-nexus-teilen');
        holeInfo(id).then(function (info) {
            if (info.oeffentlich) {
                return weitergeben(info);
            }
            hinweis(id, info);
        }).catch(function () {
            meldung('⚠ Fehler – bitte noch einmal versuchen. / Error – please try again.');
        });
    }

    /* Freigabe setzen oder aufheben; aktualisiert Menue-Eintraege und Globus auf der Seite. */
    function setzeFreigabe(id, an) {
        return sende(an ? 'zuruecknehmen' : 'freigeben', id).then(function (antwort) {
            delete vorrat[id];
            document.querySelectorAll('[data-nexus-freigabe="' + id + '"]').forEach(function (l) {
                l.setAttribute('data-an', an ? '0' : '1');
                var symbol = l.querySelector('i');
                l.textContent = ' ' + l.getAttribute(an ? 'data-label-an' : 'data-label-aus');
                if (symbol) { l.insertBefore(symbol, l.firstChild); }
            });
            document.querySelectorAll('[data-nexus-teilen="' + id + '"]').forEach(function (t) {
                var globus = t.parentNode.querySelector('[data-nexus-globus="' + id + '"]');
                if (an && globus) { globus.remove(); }
                if (!an && !globus) {
                    var g = document.createElement('i');
                    g.className = 'fa fa-globe nexus-teilen-globus';
                    g.setAttribute('data-nexus-globus', id);
                    t.insertAdjacentText('afterend', ' ');
                    t.parentNode.insertBefore(g, t.nextSibling.nextSibling);
                }
            });
            meldung(antwort.meldung, 7000);
            return antwort;
        }).catch(function (e) {
            meldung('⚠ Fehler – bitte noch einmal versuchen. / Error – please try again.');
            throw e;
        });
    }

    function freigabe(link) {
        var id = link.getAttribute('data-nexus-freigabe');
        var an = link.getAttribute('data-an') === '1';
        if (!window.confirm(link.getAttribute(an ? 'data-frage-aus' : 'data-frage-an'))) {
            return;
        }
        setzeFreigabe(id, an).catch(function () {});
    }

    /* Schaufenster (nur Admins): Vorschau zeigen, dann mit einem Klick hineinstellen -- oder herausnehmen. */
    function schaufensterZustand(id, drin) {
        document.querySelectorAll('[data-nexus-schaufenster="' + id + '"]').forEach(function (l) {
            l.setAttribute('data-an', drin ? '1' : '0');
            l.classList.toggle('drin', drin);
            var symbol = l.querySelector('i');
            l.textContent = ' ' + l.getAttribute(drin ? 'data-label-drin' : 'data-label-an');
            if (symbol) { l.insertBefore(symbol, l.firstChild); }
        });
    }

    function schaufenster(link) {
        var id = link.getAttribute('data-nexus-schaufenster');
        if (link.getAttribute('data-an') === '1') {
            if (!window.confirm(link.getAttribute('data-frage-raus'))) { return; }
            sende('schaufenster-aus', id).then(function (antwort) {
                schaufensterZustand(id, false);
                meldung(antwort.meldung, 6000);
            }).catch(function () { meldung('⚠ Fehler – bitte noch einmal versuchen. / Error – please try again.'); });
            return;
        }
        fetch(BASIS + 'vorschau?id=' + encodeURIComponent(id), {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.json();
        }).then(function (v) {
            var f = neuesFenster(v.texte.titel);
            f.kasten.classList.add('nexus-teilen-breit');
            var pruef = document.createElement('div');
            pruef.className = 'nexus-teilen-pruefung' + (v.namen.length ? ' warnung' : '');
            pruef.textContent = v.namen.length ? v.texte.namen + ' ' + v.namen.join(', ') : v.texte.keine;
            var bilder = document.createElement('div');
            bilder.className = 'nexus-teilen-hinweis';
            bilder.textContent = v.texte.bilder;
            var vorschau = document.createElement('div');
            vorschau.className = 'nexus-teilen-vorschau';
            if (v.zeigeTitel) {
                var h = document.createElement('h4');
                h.textContent = v.titel;
                vorschau.appendChild(h);
            }
            var inhalt = document.createElement('div');
            // HTML kommt fertig und bereinigt vom Server (HumHubs RichText-Umwandlung, nur Admins).
            inhalt.innerHTML = v.html;
            vorschau.appendChild(inhalt);
            f.kasten.appendChild(pruef);
            f.kasten.appendChild(bilder);
            f.kasten.appendChild(vorschau);
            f.knopf(v.texte.stellen, 'btn-primary', function () {
                f.zu();
                sende('schaufenster-an', id).then(function (antwort) {
                    schaufensterZustand(id, true);
                    meldung(antwort.meldung + '\n' + antwort.seite, 9000);
                }).catch(function () { meldung('⚠ Fehler – bitte noch einmal versuchen. / Error – please try again.'); });
            });
            f.knopf(v.texte.abbrechen, 'btn-light', f.zu);
            f.fertig();
        }).catch(function () {
            meldung('⚠ Vorschau nicht möglich – bitte noch einmal versuchen. / Preview failed.');
        });
    }

    // Vorab holen, sobald der Finger/die Maus den Knopf beruehrt: dann ist beim Loslassen alles da,
    // und das Handy erlaubt das Teilen-Menue noch (es verlangt einen frischen Fingertipp).
    document.addEventListener('pointerdown', function (e) {
        var link = e.target.closest && e.target.closest('[data-nexus-teilen]');
        if (link) { holeInfo(link.getAttribute('data-nexus-teilen')).catch(function () {}); }
    }, true);

    document.addEventListener('click', function (e) {
        var link = e.target.closest && e.target.closest('[data-nexus-teilen], [data-nexus-freigabe], [data-nexus-schaufenster]');
        if (!link) { return; }
        e.preventDefault();
        e.stopPropagation();
        if (link.hasAttribute('data-nexus-teilen')) {
            teilen(link);
        } else if (link.hasAttribute('data-nexus-schaufenster')) {
            schaufenster(link);
        } else {
            freigabe(link);
        }
    }, true);
}());
