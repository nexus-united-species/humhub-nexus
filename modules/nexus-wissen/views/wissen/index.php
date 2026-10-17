<?php

/**
 * Startseite einer Sammlung: Suche, Themen, Schlagworte -- oder eine Trefferliste.
 *
 * @var $sammlung nexus\modules\wissen\services\Sammlung
 * @var $q string
 * @var $liste array|null
 * @var $ueberschrift string|null
 * @var $anzahl int
 * @var $themen array<string, array{name: string, symbol: string}>
 * @var $jeThema array<string, int>
 * @var $schlagworte array<string, int>
 * @var $darfBearbeiten bool
 * @var $offeneVorschlaege int
 */

use humhub\helpers\Html;
use nexus\modules\wissen\services\Texte;

$freigegeben = $sammlung->freigegeben();
$untertitel = sprintf(Texte::t('anzahl'), $anzahl) . ($sammlung->beschreibung() !== '' ? ' · ' . $sammlung->beschreibung() : '');
$this->title = $sammlung->name();
?>
<div class="panel panel-default nx-ws">
    <div class="panel-body">
        <div class="nx-ws-kopf">
            <h1><i class="fa fa-<?= Html::encode($sammlung->symbol()) ?>"></i> <?= Html::encode($sammlung->name()) ?></h1>
            <div class="nx-ws-leise"><?= Html::encode($untertitel) ?></div>
        </div>

        <?php if ($darfBearbeiten) : ?>
            <div class="nx-ws-admin">
                <?php if (!$freigegeben) : ?>
                    <span><i class="fa fa-lock"></i> <?= Html::encode(Texte::t('nicht_freigegeben')) ?></span>
                <?php endif; ?>
                <?= Html::beginForm($sammlung->url('freigabe'), 'post', ['style' => 'display:inline']) ?>
                    <?= Html::hiddenInput('an', $freigegeben ? '0' : '1') ?>
                    <button type="submit" class="btn btn-sm <?= $freigegeben ? 'btn-light' : 'btn-primary' ?>"><?= Html::encode(Texte::t($freigegeben ? 'zurueckziehen' : 'freigeben')) ?></button>
                <?= Html::endForm() ?>
                <a href="<?= Html::encode($sammlung->url('vorschlaege')) ?>"><i class="fa fa-inbox"></i> <?= Html::encode(Texte::t('vorschlaege')) ?> (<?= (int)$offeneVorschlaege ?>)</a>
            </div>
        <?php endif; ?>

        <?php if ($anzahl === 0) : ?>
            <p><?= Html::encode(Texte::t('leer')) ?></p>
        <?php else : ?>
            <form class="nx-ws-suche" method="get" action="<?= Html::encode($sammlung->url()) ?>">
                <input type="hidden" name="s" value="<?= Html::encode($sammlung->kennung) ?>">
                <input type="search" name="q" class="form-control" value="<?= Html::encode($q) ?>"
                       placeholder="<?= Html::encode($sammlung->suchbeispiel()) ?>" aria-label="<?= Html::encode(Texte::t('suchen')) ?>">
                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?= Html::encode(Texte::t('suchen')) ?></button>
            </form>
        <?php endif; ?>

        <?php if ($liste !== null) : ?>
            <?php if (trim($q) !== '') : ?>
                <h2 style="font-size:1.15rem"><?= Html::encode(sprintf(Texte::t('treffer'), count($liste), $q)) ?></h2>
            <?php elseif ($ueberschrift !== null) : ?>
                <h2 style="font-size:1.15rem"><?= Html::encode($ueberschrift) ?> <span class="nx-ws-leise">(<?= count($liste) ?>)</span></h2>
            <?php endif; ?>
            <?php if ($liste === [] && trim($q) !== '') : ?>
                <p><?= Html::encode(sprintf(Texte::t('keine_treffer'), $q)) ?></p>
            <?php else : ?>
                <ul class="nx-ws-liste">
                    <?php foreach ($liste as $a) : ?>
                        <li>
                            <a class="nx-ws-titel" href="<?= Html::encode($sammlung->url('artikel', ['a' => $a['slug']])) ?>"><?= Html::encode($a['titel']) ?></a>
                            <?php if ($a['thema'] !== '') : ?><span class="nx-ws-leise">· <?= Html::encode($sammlung->themaName($a['thema'])) ?></span><?php endif; ?>
                            <?php if ($a['anriss'] !== '') : ?><p><?= Html::encode($a['anriss']) ?></p><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($themen !== []) : ?>
                <p style="margin-top:1rem"><a href="<?= Html::encode($sammlung->url()) ?>">← <?= Html::encode($sammlung->name()) ?></a></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($liste === null || ($themen === [] && trim($q) === '')) : ?>
            <?php if ($themen !== []) : ?>
                <h2 style="font-size:1.15rem"><?= Html::encode(Texte::t('themen')) ?></h2>
                <div class="nx-ws-themen">
                    <?php foreach ($themen as $code => $thema) : ?>
                        <?php if (($jeThema[$code] ?? 0) === 0) : ?>
                            <div class="nx-ws-thema nx-ws-thema-leer">
                                <i class="fa fa-<?= Html::encode($thema['symbol']) ?>"></i><?= Html::encode($thema['name']) ?>
                                <div class="nx-ws-leise"><?= Html::encode(Texte::t('thema_leer')) ?></div>
                            </div>
                        <?php else : ?>
                            <a class="nx-ws-thema" href="<?= Html::encode($sammlung->url('index', ['thema' => $code])) ?>">
                                <i class="fa fa-<?= Html::encode($thema['symbol']) ?>"></i><?= Html::encode($thema['name']) ?>
                                <div class="nx-ws-leise"><?= Html::encode(sprintf(Texte::t('artikel_im_thema'), $jeThema[$code])) ?></div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($schlagworte !== []) : ?>
                <h2 style="font-size:1.15rem"><?= Html::encode(Texte::t('schlagworte')) ?></h2>
                <div class="nx-ws-worte">
                    <?php foreach ($schlagworte as $wort => $n) : ?>
                        <a class="nx-ws-wort" href="<?= Html::encode($sammlung->url('index', ['wort' => $wort])) ?>"><?= Html::encode($wort) ?> <span class="nx-ws-leise"><?= (int)$n ?></span></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
