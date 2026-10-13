/* Abzeichen: kurze goldene Feier mit leichtem Funkenregen (entfaellt bei "weniger Bewegung"). */
(function () {
    if (window.nexusAbzeichenFeier) { return; }
    var FUNKEN = 40, DAUER_MS = 2600, FARBEN = ['#D4AF37', '#f3d36b', '#b8860b', '#fff1b8'];

    function funkenregen() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
        for (var i = 0; i < FUNKEN; i++) {
            var f = document.createElement('span');
            f.className = 'nx-abz-funke';
            f.style.left = (Math.random() * 100) + 'vw';
            f.style.background = FARBEN[i % FARBEN.length];
            document.body.appendChild(f);
            var weg = window.innerHeight + 40, drift = (Math.random() - 0.5) * 160, dreh = Math.random() * 720;
            f.animate([
                {transform: 'translate(0,0) rotate(0deg)', opacity: 1},
                {transform: 'translate(' + drift + 'px,' + weg + 'px) rotate(' + dreh + 'deg)', opacity: 0.2}
            ], {duration: DAUER_MS + Math.random() * 1200, delay: Math.random() * 500, easing: 'ease-in'}).onfinish = (function (el) {
                return function () { el.remove(); };
            })(f);
        }
    }

    /* Teilen ist freiwillig: erst ein Klick oeffnet das Feld fuer eigene Worte, erst der zweite teilt. */
    function teilenKnopf(box, knoepfe, t) {
        var auf = document.createElement('button');
        auf.type = 'button';
        auf.className = 'btn btn-sm btn-primary';
        auf.textContent = t.knopf;
        auf.addEventListener('click', function () {
            var form = document.createElement('div');
            form.className = 'nx-abz-feier-teilen';
            var wo = document.createElement('div');
            wo.className = 'nx-abz-feier-zahl';
            wo.textContent = t.wo;
            var feld = document.createElement('textarea');
            feld.className = 'form-control';
            feld.rows = 2;
            feld.maxLength = t.max;
            feld.placeholder = t.notiz;
            var senden = document.createElement('button');
            senden.type = 'button';
            senden.className = 'btn btn-sm btn-primary';
            senden.textContent = t.senden;
            senden.addEventListener('click', function () {
                senden.disabled = true;
                var daten = new FormData();
                var param = document.querySelector('meta[name="csrf-param"]');
                var token = document.querySelector('meta[name="csrf-token"]');
                if (param && token) { daten.append(param.content, token.content); }
                daten.append('notiz', feld.value);
                fetch(t.url, {method: 'POST', body: daten, credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
                    .then(function (r) { return r.json(); })
                    .then(function (a) { form.textContent = a && a.ok ? t.ok : t.fehler; })
                    .catch(function () { form.textContent = t.fehler; });
            });
            form.appendChild(wo); form.appendChild(feld); form.appendChild(senden);
            box.insertBefore(form, knoepfe);
            auf.remove();
            feld.focus();
        });
        return auf;
    }

    window.nexusAbzeichenFeier = function (d) {
        var box = document.createElement('div');
        box.className = 'nx-abz-feier';
        box.setAttribute('role', 'status');
        var symbol = document.createElement('i');
        symbol.className = 'fa fa-' + d.symbol;
        var text = document.createElement('span');
        text.textContent = ' ' + d.text;
        var zahl = document.createElement('div');
        zahl.className = 'nx-abz-feier-zahl';
        zahl.textContent = d.zaehler;
        var knopf = document.createElement('button');
        knopf.type = 'button';
        knopf.className = 'btn btn-sm btn-default';
        knopf.textContent = d.knopf;
        knopf.addEventListener('click', function () { box.remove(); });
        box.appendChild(symbol); box.appendChild(text); box.appendChild(zahl);
        var knoepfe = document.createElement('div');
        knoepfe.className = 'nx-abz-feier-knoepfe';
        knoepfe.appendChild(knopf);
        if (d.teilen) { knoepfe.appendChild(teilenKnopf(box, knoepfe, d.teilen)); }
        box.appendChild(knoepfe);
        // Mehrere Feiern auf einmal (z. B. Woche + Stunden-Stufe): uebereinander statt aufeinander
        var schon = document.querySelectorAll('.nx-abz-feier').length;
        if (schon) { box.style.bottom = (24 + schon * 12) + 'px'; box.style.marginBottom = (schon * 8) + 'px'; }
        document.body.appendChild(box);
        requestAnimationFrame(function () { box.classList.add('nx-abz-sichtbar'); });
        funkenregen();
    };
})();
