(function () {
    'use strict';

    // Das Portal wechselt Seiten ohne Neuladen (PJAX) und fuehrt dieses Skript
    // dabei jedes Mal erneut aus. Ohne diese Sperre haengt sich mit jedem
    // Seitenwechsel ein weiterer Klick-Beobachter an: EIN Klick loeste dann
    // mehrere Uebersetzungen gleichzeitig aus, die sich gegenseitig den
    // Anzeigekasten wieder wegnahmen und beim Speichern kollidierten
    // (Serverfehler 500) -- erst ein Neuladen der Seite half.
    // Meldung Josh, 21.09.2026.
    if (window.nexusTranslateGeladen) { return; }
    window.nexusTranslateGeladen = true;

    function holCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    function zeigeUebersetzung(ziel, url) {
        // Laeuft schon eine Anfrage fuer dieses Ziel (Doppelklick), nichts Neues starten.
        if (ziel.getAttribute('data-nexus-laeuft')) { return; }

        var vorhanden = ziel.querySelector('.nexus-uebersetzung');
        if (vorhanden) { vorhanden.remove(); return; }

        var kasten = document.createElement('div');
        kasten.className = 'nexus-uebersetzung';
        kasten.style.cssText = 'margin-top:10px;padding:10px 12px;background:rgba(212,175,55,0.10);border-left:3px solid #D4AF37;border-radius:4px;font-size:14px;white-space:pre-wrap';
        kasten.textContent = 'Translating …';
        ziel.appendChild(kasten);
        ziel.setAttribute('data-nexus-laeuft', '1');

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-Token': holCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (antwort) { return antwort.json().then(function (daten) { return { ok: antwort.ok, daten: daten }; }); })
            .then(function (ergebnis) {
                if (!ergebnis.ok) { throw new Error(ergebnis.daten.message || 'Translation failed.'); }
                kasten.textContent = ergebnis.daten.text;
            })
            .catch(function (fehler) {
                kasten.style.color = '#b91c1c';
                kasten.textContent = 'Translation failed: ' + fehler.message;
            })
            .then(function () { ziel.removeAttribute('data-nexus-laeuft'); });
    }

    // Absicherung gegen doppelte "Uebersetzen"-Bloecke bei Nachrichten:
    // HumHubs Mail-Modul prueft Konversationen im Hintergrund periodisch auf
    // neue Nachrichten und rendert dabei bestehende Nachrichten teils erneut
    // -- jedes Mal haengt unser serverseitiger Haken (Events::onViewAfterRender
    // im Uebersetzen-Modul) einen weiteren Block an. Serverseitig ist das
    // nicht zuverlaessig zu verhindern (jeder Render-Aufruf ist ein eigener,
    // unabhaengiger Request), deshalb hier: sobald ein neuer Block mit einer
    // bereits vorhandenen Nachrichten-ID im DOM auftaucht, wird er sofort
    // wieder entfernt. Meldung dazu aus der Gemeinschaft, 09.09.2026 -- mehrfache
    // "Uebersetzen"-Links unter derselben Nachricht.
    function entferneDoppelteNachrichtenBloecke() {
        var gesehen = Object.create(null);
        var bloecke = document.querySelectorAll('.nexus-translate-message-block[data-nexus-message-block-id]');
        for (var i = 0; i < bloecke.length; i++) {
            var id = bloecke[i].getAttribute('data-nexus-message-block-id');
            if (gesehen[id]) {
                bloecke[i].remove();
            } else {
                gesehen[id] = true;
            }
        }
    }

    // ── Automatische Uebersetzung (Josh, 25.09.2026) ─────────────────────
    // Der Server markiert Beitraege/Kommentare, die in einer anderen Sprache als
    // der des Lesers geschrieben sind (data-nexus-auto am "Translate"-Link). Hier
    // wird die Uebersetzung geholt (serverseitig je Text und Sprache nur EINMAL
    // erzeugt) und anstelle des Originals gezeigt -- mit einem Hinweis und einem
    // Umschalter "Original anzeigen". Hoechstens zwei Anfragen gleichzeitig, damit
    // ein Stream mit vielen fremdsprachigen Beitraegen den Server nicht flutet.
    var warteschlange = [];
    var laufend = 0;

    function autoZiel(link) {
        if (link.hasAttribute('data-nexus-wikiseite-id')) {
            // Ganze Wiki-Seite beim Oeffnen (seit 28.09.2026): Inhalt + Titel. Das Wiki baut die
            // Seite erst im Browser auf (anfangs unsichtbar) -- getauscht wird erst danach (warten).
            var sid = link.getAttribute('data-nexus-wikiseite-id');
            var koerper = link.closest('.wiki-page-body') || document;
            var sel = koerper.querySelector('.markdown-render');
            return sel ? {
                el: sel,
                titelEl: koerper.querySelector('h1.wiki-page-title'),
                warten: true,
                url: '/nexus-translate/translate/auto?id=' + encodeURIComponent(sid) + '&type=wikis'
            } : null;
        }
        if (link.hasAttribute('data-nexus-cal-id')) {
            // Kalendertermin: nur die Beschreibung (der Titel kommt schon uebersetzt vom Server).
            var cid2 = link.getAttribute('data-nexus-cal-id');
            var cpanel = link.closest('.panel');
            var cel2 = cpanel ? cpanel.querySelector('.calendar-wall-entry .event-info-section-content[data-ui-markdown]') : null;
            return cel2 ? { el: cel2, url: '/nexus-translate/translate/auto?id=' + encodeURIComponent(cid2) + '&type=cal' } : null;
        }
        if (link.hasAttribute('data-nexus-wiki-id')) {
            // Wiki-Seite im Stream: Vorschau-Ausschnitt + Titel im Kopf desselben Eintrags.
            var wid = link.getAttribute('data-nexus-wiki-id');
            var panel = link.closest('.panel');
            var wel = panel ? panel.querySelector('.wiki-preview-content') : null;
            return wel ? {
                el: wel,
                titelEl: panel.querySelector('.wall-entry-header h4 > a'),
                url: '/nexus-translate/translate/auto?id=' + encodeURIComponent(wid) + '&type=wiki'
            } : null;
        }
        if (link.hasAttribute('data-nexus-post-id')) {
            var id = link.getAttribute('data-nexus-post-id');
            var el = document.querySelector('#post-content-' + id + ' [data-ui-markdown]');
            return el ? { el: el, url: '/nexus-translate/translate/auto?id=' + encodeURIComponent(id) + '&type=post' } : null;
        }
        var cid = link.getAttribute('data-nexus-comment-id');
        var cel = cid ? document.getElementById('comment-message-' + cid) : null;
        return cel ? { el: cel, url: '/nexus-translate/translate/auto?id=' + encodeURIComponent(cid) + '&type=comment' } : null;
    }

    // Wartet, bis ein Element sichtbar ist (Wiki baut seine Seite erst auf), hoechstens 15 s.
    function wennSichtbar(el, fertig) {
        var versuche = 0;
        (function pruefen() {
            if (el.offsetParent !== null || getComputedStyle(el).display !== 'none') { fertig(); return; }
            if (++versuche < 75) { setTimeout(pruefen, 200); } else { fertig(); }
        })();
    }

    // Bilder in der Uebersetzung duerfen nicht breiter als der Beitrag werden.
    function passeBilderAn(el) {
        var bilder = el.querySelectorAll('img');
        for (var i = 0; i < bilder.length; i++) {
            bilder[i].style.maxWidth = '100%';
            bilder[i].style.height = 'auto';
        }
    }

    function zeigeAuto(link, ziel, html, titel) {
        var original = ziel.el.innerHTML;
        var titelOriginal = ziel.titelEl ? ziel.titelEl.textContent : null;
        function setzeTitel(uebersetzt) {
            if (ziel.titelEl && titel) { ziel.titelEl.textContent = uebersetzt ? titel : titelOriginal; }
        }
        ziel.el.innerHTML = html;
        passeBilderAn(ziel.el);
        setzeTitel(true);
        var leiste = document.createElement('div');
        leiste.className = 'nexus-auto-hinweis';
        leiste.style.cssText = 'font-size:12px;color:#8a8f98;margin:0 0 6px 0;';
        var umschalter = document.createElement('a');
        umschalter.href = '#';
        umschalter.style.marginLeft = '6px';
        umschalter.textContent = link.getAttribute('data-nexus-original');
        leiste.appendChild(document.createTextNode('🌐 ' + link.getAttribute('data-nexus-hinweis') + ' ·'));
        leiste.appendChild(umschalter);
        var uebersetzt = true;
        umschalter.addEventListener('click', function (ev) {
            ev.preventDefault();
            uebersetzt = !uebersetzt;
            ziel.el.innerHTML = uebersetzt ? html : original;
            if (uebersetzt) { passeBilderAn(ziel.el); }
            setzeTitel(uebersetzt);
            umschalter.textContent = link.getAttribute(uebersetzt ? 'data-nexus-original' : 'data-nexus-uebersetzung');
        });
        ziel.el.parentNode.insertBefore(leiste, ziel.el);
        // Der "Translate"-Knopf wuerde jetzt dasselbe noch einmal zeigen.
        link.style.display = 'none';
    }

    function naechsteAuto() {
        while (laufend < 2 && warteschlange.length) {
            var link = warteschlange.shift();
            var ziel = autoZiel(link);
            if (!ziel) { continue; }
            laufend++;
            fetch(ziel.url, {
                method: 'POST',
                headers: { 'X-CSRF-Token': holCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (antwort) { return antwort.ok ? antwort.json() : null; })
                .then((function (l, z) {
                    return function (daten) {
                        if (daten && daten.html) {
                            if (z.warten) { wennSichtbar(z.el, function () { zeigeAuto(l, z, daten.html, daten.titel); }); }
                            else { zeigeAuto(l, z, daten.html, daten.titel); }
                            return;
                        }
                        throw new Error('keine Uebersetzung');
                    };
                })(link, ziel))
                .catch((function (l) {
                    // Ein zweiter Versuch nach 8 s (Dienst kurz ueberlastet, sehr langer Text);
                    // danach bleibt das Original stehen und "Translate" funktioniert weiter.
                    return function () {
                        if (l.getAttribute('data-nexus-nochmal')) { return; }
                        l.setAttribute('data-nexus-nochmal', '1');
                        setTimeout(function () { warteschlange.push(l); naechsteAuto(); }, 8000);
                    };
                })(link))
                .then(function () { laufend--; naechsteAuto(); });
        }
    }

    // "Letzte Aktivitaeten" (Josh, 25.09.2026): der Rahmen ("hat einen Beitrag erstellt")
    // ist schon in der Sprache des Lesers, der Auszug in Anfuehrungszeichen nicht. Alle neu
    // sichtbaren Eintraege gehen in EINER Anfrage an den Server; der liefert nur die, die
    // wirklich in einer anderen Sprache sind. Ausgetauscht wird reiner Text, nie HTML.
    function tauscheText(el, alt, neu) {
        var gang = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
        while (gang.nextNode()) {
            var knoten = gang.currentNode;
            if (knoten.nodeValue.indexOf(alt) !== -1) {
                knoten.nodeValue = knoten.nodeValue.replace(alt, function () { return neu; });
                return true;
            }
        }
        return false;
    }

    function sammleAktivitaeten() {
        var links = document.querySelectorAll('a[href*="activity/link"]:not([data-nexus-akt])');
        var nachId = {};
        var ids = [];
        for (var i = 0; i < links.length; i++) {
            links[i].setAttribute('data-nexus-akt', '1');
            var treffer = /[?&]id=(\d+)/.exec(links[i].getAttribute('href'));
            if (!treffer) { continue; }
            (nachId[treffer[1]] = nachId[treffer[1]] || []).push(links[i]);
            ids.push(treffer[1]);
        }
        if (!ids.length) { return; }
        var daten = new URLSearchParams();
        ids.forEach(function (id) { daten.append('ids[]', id); });
        fetch('/nexus-translate/translate/aktivitaeten', {
            method: 'POST',
            headers: { 'X-CSRF-Token': holCsrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
            body: daten
        })
            .then(function (antwort) { return antwort.ok ? antwort.json() : null; })
            .then(function (ergebnis) {
                var eintraege = (ergebnis && ergebnis.eintraege) || {};
                Object.keys(eintraege).forEach(function (id) {
                    (nachId[id] || []).forEach(function (link) {
                        eintraege[id].forEach(function (paar) {
                            if (paar.original && tauscheText(link, paar.original, paar.uebersetzt)) {
                                link.title = '🌐 ' + paar.hinweis;
                            }
                        });
                    });
                });
            })
            .catch(function () { /* Auszug bleibt im Original */ });
    }

    function sammleAuto() {
        var links = document.querySelectorAll('a[data-nexus-auto]:not([data-nexus-auto-gesehen])');
        for (var i = 0; i < links.length; i++) {
            links[i].setAttribute('data-nexus-auto-gesehen', '1');
            warteschlange.push(links[i]);
        }
        naechsteAuto();
        sammleAktivitaeten();
    }

    var autoTimer = null;
    if (typeof MutationObserver !== 'undefined' && document.body) {
        var beobachter = new MutationObserver(function () {
            entferneDoppelteNachrichtenBloecke();
            clearTimeout(autoTimer);
            autoTimer = setTimeout(sammleAuto, 200);
        });
        beobachter.observe(document.body, { childList: true, subtree: true });
    }
    // Einmal sofort ausfuehren, falls beim Laden dieses Skripts bereits
    // Duplikate im initial ausgelieferten HTML stehen.
    entferneDoppelteNachrichtenBloecke();
    sammleAuto();

    document.addEventListener('click', function (ev) {
        // Wiki-Seite: "Translate" erscheint nur, wenn uebersetzt werden soll -- ein Klick
        // (falls die automatische Uebersetzung noch laeuft oder scheiterte) stoesst sie erneut an.
        var wikiLink = ev.target.closest('a[data-nexus-wiki-id], a[data-nexus-cal-id], a[data-nexus-wikiseite-id]');
        if (wikiLink) {
            ev.preventDefault();
            warteschlange.push(wikiLink);
            naechsteAuto();
            return;
        }

        var postLink = ev.target.closest('a[data-nexus-post-id]');
        if (postLink) {
            ev.preventDefault();
            var postId = postLink.getAttribute('data-nexus-post-id');
            var panel = postLink.closest('[class*="wall_humhubmodulespostmodelsPost_"]');
            var postZiel = panel ? panel.querySelector('.wall-entry-content.content') : null;
            if (!postId || !postZiel) { return; }
            zeigeUebersetzung(postZiel, '/nexus-translate/translate/translate?id=' + encodeURIComponent(postId) + '&type=post');
            return;
        }

        var commentLink = ev.target.closest('a[data-nexus-comment-id]');
        if (commentLink) {
            ev.preventDefault();
            var commentId = commentLink.getAttribute('data-nexus-comment-id');
            var commentZiel = document.getElementById('comment-message-' + commentId);
            if (!commentId || !commentZiel) { return; }
            zeigeUebersetzung(commentZiel, '/nexus-translate/translate/translate?id=' + encodeURIComponent(commentId) + '&type=comment');
            return;
        }

        var messageLink = ev.target.closest('a[data-nexus-message-id]');
        if (messageLink) {
            ev.preventDefault();
            var messageId = messageLink.getAttribute('data-nexus-message-id');
            var messageZiel = document.getElementById('nexus-msg-translate-target-' + messageId);
            if (!messageId || !messageZiel) { return; }
            zeigeUebersetzung(messageZiel, '/nexus-translate/translate/translate?id=' + encodeURIComponent(messageId) + '&type=message');
        }
    });
})();
