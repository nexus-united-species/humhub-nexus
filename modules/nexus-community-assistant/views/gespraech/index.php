<?php

/**
 * Seite "Nova – Gespraeche": links die eigenen Gespraeche, ein neues beginnen und was Nova sich merkt,
 * rechts das gewaehlte Gespraech.
 *
 * @var yii\web\View $this
 * @var nexus\modules\communityAssistant\models\Gespraech[] $gespraeche
 * @var nexus\modules\communityAssistant\models\Gespraech|null $aktiv
 * @var nexus\modules\communityAssistant\models\Merkpunkt[] $merk
 * @var int $rest
 * @var string|null $sperre
 * @var string|null $meldung
 * @var string $vorlage
 */

use nexus\modules\communityAssistant\services\Gespraeche;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\helpers\Markdown;
use yii\helpers\Url;

$t = [Gespraeche::class, 't'];
$this->title = $t('titel');
$markdown = fn(string $text) => HtmlPurifier::process(Markdown::process($text, 'gfm'));

$this->registerCss(<<<CSS
.nx-nova { display: grid; grid-template-columns: minmax(220px, 300px) 1fr; gap: 1rem; align-items: start; }
@media (max-width: 800px) { .nx-nova { grid-template-columns: 1fr; } .nx-nova-seite { order: 2; } }
.nx-nova .nx-nova-liste a { display: block; padding: .45rem .6rem; border-radius: 6px; color: inherit; text-decoration: none; }
.nx-nova .nx-nova-liste a:hover, .nx-nova .nx-nova-liste a:focus { background: var(--bs-tertiary-bg, #f2f2f2); }
.nx-nova .nx-nova-liste a.aktiv { background: var(--bs-secondary-bg, #e9ecef); font-weight: 600; }
.nx-nova .nx-nova-liste small { display: block; opacity: .7; font-weight: normal; }
.nx-nova .nx-nova-arten button { display: block; width: 100%; text-align: left; margin-bottom: .35rem; white-space: normal; }
.nx-nova .nx-nova-arten small { display: block; opacity: .75; }
.nx-nova .nx-nova-nachricht { padding: .7rem .9rem; border-radius: 10px; margin-bottom: .8rem; max-width: 92%; }
.nx-nova .nx-nova-du { background: var(--bs-primary-bg-subtle, #e7f0ff); margin-left: auto; white-space: pre-wrap; }
.nx-nova .nx-nova-nova { background: var(--bs-tertiary-bg, #f6f6f6); border-left: 3px solid #c9a227; }
.nx-nova .nx-nova-nova p:last-child { margin-bottom: 0; }
.nx-nova .nx-nova-wer { font-size: .8rem; opacity: .7; margin-bottom: .2rem; }
.nx-nova .nx-nova-quellen { font-size: .82rem; margin-top: .5rem; opacity: .85; }
.nx-nova textarea { min-height: 7rem; }
.nx-nova .nx-nova-denkt { display: none; margin-top: .5rem; }
.nx-nova form.laeuft .nx-nova-denkt { display: block; }
.nx-nova .nx-nova-hinweis { font-size: .82rem; opacity: .8; }
.nx-nova .nx-nova-sprechen.nimmt-auf { background: #c0392b; border-color: #c0392b; color: #fff; }
.nx-nova .nx-nova-sprechen.nimmt-auf .fa { animation: nx-nova-puls 1s infinite; }
@keyframes nx-nova-puls { 50% { opacity: .35; } }
.nx-nova .nx-nova-vorlesen { float: right; font-size: .8rem; padding: 0 .5rem; margin-left: .5rem; }
.nx-nova .nx-nova-sprech-status { font-size: .9rem; margin-top: .4rem; min-height: 1.2rem; }
@media (prefers-reduced-motion: reduce) { .nx-nova .nx-nova-sprechen.nimmt-auf .fa { animation: none; } }
CSS);

// Texte fuer die Sprachfunktion: als Daten an der Seite, damit im Skript kein PHP steckt
$sprachTexte = [];
foreach (['sprechen', 'sprechen_stopp', 'sprechen_laeuft', 'sprechen_schreibt', 'sprechen_nichts', 'sprechen_fehler',
          'sprechen_keinmikro', 'vorlesen', 'vorlesen_stopp'] as $schluessel) {
    $sprachTexte[$schluessel] = $t($schluessel);
}

// Kein Code im HTML (Sicherheitsregel des Portals): alles per registerJs, HumHub haengt den nonce an
$this->registerJs(<<<'JS'
(function () {
    var form = document.getElementById('nx-nova-form');
    if (form) {
        form.addEventListener('submit', function () {
            form.classList.add('laeuft');
            var knopf = form.querySelector('button[type=submit]');
            if (knopf) { knopf.disabled = true; }
        });
        var feld = form.querySelector('textarea');
        if (feld) {
            feld.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); form.requestSubmit(); }
            });
        }
    }
    var ende = document.getElementById('nx-nova-ende');
    if (ende && window.location.hash === '#nx-nova-ende') { ende.scrollIntoView(); }
    // ── Sprache (Josh, 28.10.2026): Diktieren ueber den eigenen Whisper-Dienst, Vorlesen ueber die Stimme des Geraets ──
    var wurzel = document.querySelector('.nx-nova');
    var T = {};
    try { T = JSON.parse(wurzel.getAttribute('data-nx-sprache-texte') || '{}'); } catch (err) { T = {}; }
    var MAX_AUFNAHME = 3 * 60;
    var status = document.getElementById('nx-nova-sprech-status');

    function melde(text) { if (status) { status.textContent = text || ''; } }
    function zeit(s) { s = Math.floor(s); return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); }

    var sprechen = document.getElementById('nx-nova-sprechen');
    var kannAufnehmen = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
    if (sprechen && !kannAufnehmen) { sprechen.style.display = 'none'; }
    if (sprechen && kannAufnehmen && form) {
        var aufnahme = null;
        var formatWahl = function () {
            var kandidaten = ['audio/mp4', 'audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus'];
            for (var i = 0; i < kandidaten.length; i++) {
                if (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(kandidaten[i])) { return kandidaten[i]; }
            }
            return '';
        };
        var ruhe = function () {
            sprechen.classList.remove('nimmt-auf');
            sprechen.disabled = false;
            sprechen.querySelector('span').textContent = T.sprechen;
            sprechen.setAttribute('aria-pressed', 'false');
        };
        var textEinsetzen = function (text) {
            var feld = document.getElementById('nx-nova-text');
            var vorher = feld.value.replace(/\s+$/, '');
            feld.value = (vorher ? vorher + ' ' : '') + text;
            feld.focus();
            feld.setSelectionRange(feld.value.length, feld.value.length);
        };
        var umwandeln = function (blob, endung) {
            melde(T.sprechen_schreibt);
            sprechen.disabled = true;
            var daten = new FormData();
            daten.append('audio', blob, 'nova-' + Date.now() + '.' + endung);
            var meta = document.querySelector('meta[name="csrf-token"]');
            fetch('/nexus-voice/mitschrift', {
                method: 'POST',
                headers: { 'X-CSRF-Token': meta ? meta.getAttribute('content') : '', 'X-Requested-With': 'XMLHttpRequest' },
                body: daten
            })
                .then(function (a) { return a.ok ? a.json() : Promise.reject(new Error('HTTP ' + a.status)); })
                .then(function (e) {
                    var text = ((e && e.text) || '').trim();
                    if (!text) { melde(T.sprechen_nichts); return; }
                    melde('');
                    textEinsetzen(text);
                })
                .catch(function () { melde(T.sprechen_fehler); })
                .then(ruhe);
        };
        sprechen.addEventListener('click', function () {
            if (aufnahme) { if (aufnahme.recorder.state !== 'inactive') { aufnahme.recorder.stop(); } return; }
            melde('');
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
                var mime = formatWahl();
                var recorder = new MediaRecorder(stream, mime ? { mimeType: mime } : {});
                var stuecke = [];
                var beginn = Date.now();
                var uhr = setInterval(function () {
                    var s = (Date.now() - beginn) / 1000;
                    melde(T.sprechen_laeuft + ' ' + zeit(s) + ' / ' + zeit(MAX_AUFNAHME));
                    if (s >= MAX_AUFNAHME && recorder.state !== 'inactive') { recorder.stop(); }
                }, 250);
                aufnahme = { recorder: recorder };
                recorder.ondataavailable = function (e) { if (e.data && e.data.size) { stuecke.push(e.data); } };
                recorder.onstop = function () {
                    clearInterval(uhr);
                    stream.getTracks().forEach(function (t) { t.stop(); });
                    aufnahme = null;
                    var typ = mime ? mime.split(';')[0] : (stuecke[0] && stuecke[0].type) || 'audio/webm';
                    var blob = new Blob(stuecke, { type: typ });
                    sprechen.classList.remove('nimmt-auf');
                    if (blob.size < 2000) { melde(T.sprechen_nichts); ruhe(); return; }
                    umwandeln(blob, typ.indexOf('mp4') >= 0 ? 'm4a' : (typ.indexOf('ogg') >= 0 ? 'ogg' : 'webm'));
                };
                sprechen.classList.add('nimmt-auf');
                sprechen.setAttribute('aria-pressed', 'true');
                sprechen.querySelector('span').textContent = T.sprechen_stopp;
                recorder.start(1000);
            }).catch(function () { melde(T.sprechen_keinmikro); });
        });
        form.addEventListener('submit', function () {
            if (aufnahme && aufnahme.recorder.state !== 'inactive') { aufnahme.recorder.stop(); }
        });
    }

    // Vorlesen: Stimme des Geraets (Browser) -- nichts verlaesst das Geraet. Lange Texte in Stuecken, weil
    // manche Browser sonst nach etwa 15 Sekunden abbrechen.
    var kannVorlesen = !!(window.speechSynthesis && window.SpeechSynthesisUtterance);
    var liest = null;
    var stuecke = function (text) {
        var saetze = text.replace(/\s+/g, ' ').match(/[^.!?…]+[.!?…]*\s*/g) || [text];
        var teile = [], akt = '';
        saetze.forEach(function (s) {
            if ((akt + s).length > 220 && akt) { teile.push(akt); akt = ''; }
            akt += s;
        });
        if (akt.trim()) { teile.push(akt); }
        return teile;
    };
    var stoppeLesen = function () {
        if (!kannVorlesen) { return; }
        window.speechSynthesis.cancel();
        if (liest) { liest.knopf.querySelector('span').textContent = T.vorlesen; liest.knopf.setAttribute('aria-pressed', 'false'); }
        liest = null;
    };
    if (kannVorlesen) {
        var sprachcode = (document.documentElement.lang || 'de').toLowerCase().split('-')[0];
        document.querySelectorAll('.nx-nova-nova').forEach(function (box) {
            var kopf = box.querySelector('.nx-nova-wer');
            if (!kopf) { return; }
            var k = document.createElement('button');
            k.type = 'button';
            k.className = 'btn btn-light nx-nova-vorlesen';
            k.setAttribute('aria-pressed', 'false');
            k.innerHTML = '<i class="fa fa-volume-up" aria-hidden="true"></i> <span></span>';
            k.querySelector('span').textContent = T.vorlesen;
            k.addEventListener('click', function () {
                var warSelbst = liest && liest.knopf === k;
                stoppeLesen();
                if (warSelbst) { return; }
                var text = Array.prototype.map.call(box.children, function (el) {
                    return (el.classList.contains('nx-nova-wer') || el.classList.contains('nx-nova-quellen')) ? '' : el.innerText;
                }).join(' ').trim();
                if (!text) { return; }
                var teile = stuecke(text);
                var stimmen = window.speechSynthesis.getVoices().filter(function (v) { return v.lang.toLowerCase().indexOf(sprachcode) === 0; });
                var aktuell = { knopf: k };
                liest = aktuell;
                k.querySelector('span').textContent = T.vorlesen_stopp;
                k.setAttribute('aria-pressed', 'true');
                var naechstes = function (i) {
                    if (liest !== aktuell) { return; }
                    if (i >= teile.length) { stoppeLesen(); return; }
                    var u = new SpeechSynthesisUtterance(teile[i]);
                    u.lang = stimmen[0] ? stimmen[0].lang : sprachcode;
                    if (stimmen[0]) { u.voice = stimmen[0]; }
                    u.onend = function () { naechstes(i + 1); };
                    u.onerror = function () { if (liest === aktuell) { stoppeLesen(); } };
                    window.speechSynthesis.speak(u);
                };
                naechstes(0);
            });
            kopf.appendChild(k);
        });
        window.addEventListener('pagehide', stoppeLesen);
        if (form) { form.addEventListener('submit', stoppeLesen); }
    }

    document.querySelectorAll('form[data-nx-nova-frage]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (f.dataset.bestaetigt) { return; }
            e.preventDefault();
            var weiter = function () { f.dataset.bestaetigt = '1'; f.requestSubmit(); };
            try {
                humhub.require('ui.modal').confirm({body: f.dataset.nxNovaFrage}).then(function (ja) { if (ja) { weiter(); } });
            } catch (err) {
                if (window.confirm(f.dataset.nxNovaFrage)) { weiter(); }
            }
        });
    });
})();
JS);
?>
<div class="container nx-nova" data-nx-sprache-texte="<?= Html::encode(json_encode($sprachTexte, JSON_UNESCAPED_UNICODE)) ?>">
    <div class="nx-nova-seite">
        <div class="panel panel-default">
            <div class="panel-heading"><strong><i class="fa fa-comments"></i> <?= Html::encode($t('titel')) ?></strong>
                <div class="nx-nova-hinweis"><?= Html::encode($t('untertitel')) ?></div></div>
            <div class="panel-body">
                <div class="nx-nova-liste">
                    <?php foreach ($gespraeche as $g) : ?>
                        <a href="<?= Url::to(['index', 'g' => $g->id]) ?>" class="<?= $aktiv && $aktiv->id === $g->id ? 'aktiv' : '' ?>">
                            <i class="fa fa-<?= Html::encode(Gespraeche::ARTEN[$g->art][0] ?? 'comment') ?>"></i>
                            <?= Html::encode($g->titel !== '' ? $g->titel : $t('art_' . $g->art)) ?>
                            <small><?= Html::encode(Yii::$app->formatter->asRelativeTime($g->updated_at)) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
                <h4 style="margin-top:1rem"><?= Html::encode($t('neues')) ?></h4>
                <div class="nx-nova-arten">
                    <?php foreach (Gespraeche::ARTEN as $art => [$symbol]) : ?>
                        <?= Html::beginForm(['neu'], 'post') . Html::hiddenInput('art', $art) ?>
                            <button type="submit" class="btn btn-light">
                                <i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode($t('art_' . $art)) ?>
                                <small><?= Html::encode($t('art_' . $art . '_hilfe')) ?></small>
                            </button>
                        <?= Html::endForm() ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
            <div class="panel-heading"><strong><i class="fa fa-bookmark-o"></i> <?= Html::encode($t('gedaechtnis')) ?></strong></div>
            <div class="panel-body">
                <p class="nx-nova-hinweis"><?= Html::encode($t('gedaechtnis_hilfe')) ?></p>
                <?php if ($merk === []) : ?>
                    <p class="nx-nova-hinweis"><?= Html::encode($t('gedaechtnis_leer')) ?></p>
                <?php endif; ?>
                <?php foreach ($merk as $m) : ?>
                    <div style="display:flex;gap:.4rem;align-items:flex-start;margin-bottom:.3rem">
                        <span style="flex:1"><?= Html::encode($m->text) ?></span>
                        <?= Html::beginForm(['vergessen', 'm' => $m->id, 'g' => $aktiv->id ?? 0], 'post') ?>
                            <button type="submit" class="btn btn-sm btn-light" title="<?= Html::encode($t('vergessen')) ?>" aria-label="<?= Html::encode($t('vergessen')) ?>"><i class="fa fa-times"></i></button>
                        <?= Html::endForm() ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-body">
            <?php if ($meldung) : ?>
                <div class="alert alert-warning"><?= Html::encode($meldung) ?></div>
            <?php endif; ?>
            <?php if ($aktiv === null) : ?>
                <p><?= Html::encode($t('keine')) ?></p>
                <p class="nx-nova-hinweis"><i class="fa fa-info-circle"></i> <?= Html::encode($t('datenschutz')) ?></p>
            <?php else : ?>
                <div style="display:flex;justify-content:space-between;gap:.5rem;flex-wrap:wrap;margin-bottom:.8rem">
                    <h3 style="margin:0"><i class="fa fa-<?= Html::encode(Gespraeche::ARTEN[$aktiv->art][0] ?? 'comment') ?>"></i> <?= Html::encode($t('art_' . $aktiv->art)) ?></h3>
                    <?= Html::beginForm(['loeschen', 'g' => $aktiv->id], 'post', ['data-nx-nova-frage' => $t('loeschen_frage')]) ?>
                        <button type="submit" class="btn btn-sm btn-light"><i class="fa fa-trash-o"></i> <?= Html::encode($t('loeschen')) ?></button>
                    <?= Html::endForm() ?>
                </div>
                <?php foreach ($aktiv->nachrichten as $n) : ?>
                    <?php if ($n->rolle === 'user') : ?>
                        <div class="nx-nova-nachricht nx-nova-du"><div class="nx-nova-wer"><?= Html::encode($t('du')) ?></div><?= Html::encode($n->inhalt) ?></div>
                    <?php else : ?>
                        <div class="nx-nova-nachricht nx-nova-nova">
                            <div class="nx-nova-wer"><?= Html::encode($t('nova')) ?></div>
                            <?= $markdown((string)$n->inhalt) ?>
                            <?php if ($n->quellenListe() !== []) : ?>
                                <div class="nx-nova-quellen"><strong><?= Html::encode($t('quellen')) ?>:</strong>
                                    <?= implode(' · ', array_map(fn($q) => !empty($q['url'])
                                        ? Html::a(Html::encode($q['titel']), $q['url'])
                                        : Html::encode($q['titel']), $n->quellenListe())) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div id="nx-nova-ende"></div>
                <?php if ($sperre !== null) : ?>
                    <div class="alert alert-info"><?= Html::encode($sperre) ?></div>
                <?php else : ?>
                    <?= Html::beginForm(['senden', 'g' => $aktiv->id], 'post', ['id' => 'nx-nova-form']) ?>
                        <label for="nx-nova-text" class="sr-only visually-hidden"><?= Html::encode($t('eingabe')) ?></label>
                        <textarea id="nx-nova-text" name="text" class="form-control" required maxlength="<?= Gespraeche::MAX_NACHRICHT ?>" placeholder="<?= Html::encode($t('eingabe')) ?>"><?= Html::encode($vorlage ?? '') ?></textarea>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-top:.5rem;flex-wrap:wrap">
                            <span class="nx-nova-hinweis"><?= Html::encode(sprintf($t('heute'), $rest, Gespraeche::tageslimit())) ?></span>
                            <span style="display:flex;gap:.5rem;flex-wrap:wrap">
                                <button type="button" id="nx-nova-sprechen" class="btn btn-light nx-nova-sprechen" aria-pressed="false" title="<?= Html::encode($t('sprechen_hilfe')) ?>"><i class="fa fa-microphone" aria-hidden="true"></i> <span><?= Html::encode($t('sprechen')) ?></span></button>
                                <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> <?= Html::encode($t('senden')) ?></button>
                            </span>
                        </div>
                        <div id="nx-nova-sprech-status" class="nx-nova-sprech-status" role="status" aria-live="polite"></div>
                        <div class="nx-nova-denkt alert alert-info"><i class="fa fa-spinner fa-spin"></i> <?= Html::encode($t('denkt')) ?></div>
                    <?= Html::endForm() ?>
                <?php endif; ?>
                <p class="nx-nova-hinweis" style="margin-top:.8rem"><i class="fa fa-info-circle"></i> <?= Html::encode($t('datenschutz')) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
