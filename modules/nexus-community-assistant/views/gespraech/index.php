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
CSS);

// Kein Code im HTML (Sicherheitsregel des Portals): alles per registerJs, HumHub haengt den nonce an
$this->registerJs(<<<JS
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
<div class="container nx-nova">
    <div class="nx-nova-seite">
        <div class="panel panel-default">
            <div class="panel-heading"><strong><i class="fa fa-comments"></i> <?= Html::encode($t('titel')) ?></strong></div>
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
                        <textarea id="nx-nova-text" name="text" class="form-control" required maxlength="<?= Gespraeche::MAX_NACHRICHT ?>" placeholder="<?= Html::encode($t('eingabe')) ?>"></textarea>
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-top:.5rem;flex-wrap:wrap">
                            <span class="nx-nova-hinweis"><?= Html::encode(sprintf($t('heute'), $rest, Gespraeche::tageslimit())) ?></span>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> <?= Html::encode($t('senden')) ?></button>
                        </div>
                        <div class="nx-nova-denkt alert alert-info"><i class="fa fa-spinner fa-spin"></i> <?= Html::encode($t('denkt')) ?></div>
                    <?= Html::endForm() ?>
                <?php endif; ?>
                <p class="nx-nova-hinweis" style="margin-top:.8rem"><i class="fa fa-info-circle"></i> <?= Html::encode($t('datenschutz')) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
