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
        box.appendChild(symbol); box.appendChild(text); box.appendChild(zahl); box.appendChild(knopf);
        document.body.appendChild(box);
        requestAnimationFrame(function () { box.classList.add('nx-abz-sichtbar'); });
        funkenregen();
    };
})();
