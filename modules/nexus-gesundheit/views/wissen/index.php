<?php

/**
 * Startseite: Suche, Themen, Schlagworte -- oder eine Trefferliste.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $q string
 * @var $liste array|null
 * @var $ueberschrift string|null
 * @var $anzahl int
 * @var $jeThema array<string, int>
 * @var $schlagworte array<string, int>
 * @var $istAdmin bool
 * @var $freigegeben bool
 * @var $offeneVorschlaege int
 */

use humhub\helpers\Html;
use nexus\modules\gesundheit\services\Texte;

$url = fn(string $route, array $p = []) => $kreis->createUrl('/nexus-gesundheit/wissen/' . $route, $p);
?>
<div class="panel panel-default nx-gw">
    <div class="panel-body">
        <div class="nx-gw-kopf">
            <h1><i class="fa fa-heartbeat"></i> <?= Html::encode(Texte::t('titel')) ?></h1>
            <div class="nx-gw-leise"><?= Html::encode(sprintf(Texte::t('untertitel'), $anzahl)) ?></div>
        </div>

        <?php if ($istAdmin) : ?>
            <div class="nx-gw-admin">
                <?php if (!$freigegeben) : ?>
                    <span><i class="fa fa-lock"></i> <?= Html::encode(Texte::t('nicht_freigegeben')) ?></span>
                <?php endif; ?>
                <?= Html::beginForm($url('freigabe'), 'post', ['style' => 'display:inline']) ?>
                    <?= Html::hiddenInput('an', $freigegeben ? '0' : '1') ?>
                    <button type="submit" class="btn btn-sm <?= $freigegeben ? 'btn-light' : 'btn-primary' ?>"><?= Html::encode(Texte::t($freigegeben ? 'zurueckziehen' : 'freigeben')) ?></button>
                <?= Html::endForm() ?>
                <a href="<?= Html::encode($url('vorschlaege')) ?>"><i class="fa fa-inbox"></i> <?= Html::encode(Texte::t('vorschlaege')) ?> (<?= (int)$offeneVorschlaege ?>)</a>
            </div>
        <?php endif; ?>

        <form class="nx-gw-suche" method="get" action="<?= Html::encode($url('index')) ?>">
            <input type="search" name="q" class="form-control" value="<?= Html::encode($q) ?>"
                   placeholder="<?= Html::encode(Texte::t('suche_platzhalter')) ?>" aria-label="<?= Html::encode(Texte::t('suchen')) ?>">
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?= Html::encode(Texte::t('suchen')) ?></button>
        </form>

        <?php if ($liste !== null) : ?>
            <h2 style="font-size:1.15rem">
                <?php if (trim($q) !== '') : ?>
                    <?= Html::encode(sprintf(Texte::t('treffer'), count($liste), $q)) ?>
                <?php else : ?>
                    <?= Html::encode($ueberschrift) ?> <span class="nx-gw-leise">(<?= count($liste) ?>)</span>
                <?php endif; ?>
            </h2>
            <?php if ($liste === []) : ?>
                <p><?= Html::encode(sprintf(Texte::t('keine_treffer'), $q)) ?></p>
            <?php else : ?>
                <ul class="nx-gw-liste">
                    <?php foreach ($liste as $a) : ?>
                        <li>
                            <a class="nx-gw-titel" href="<?= Html::encode($url('artikel', ['a' => $a['slug']])) ?>"><?= Html::encode($a['titel']) ?></a>
                            <span class="nx-gw-leise">· <?= Html::encode(Texte::thema($a['thema'])) ?></span>
                            <?php if ($a['anriss'] !== '') : ?><p><?= Html::encode($a['anriss']) ?></p><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p style="margin-top:1rem"><a href="<?= Html::encode($url('index')) ?>">← <?= Html::encode(Texte::t('zurueck')) ?></a></p>
        <?php else : ?>
            <h2 style="font-size:1.15rem"><?= Html::encode(Texte::t('themen')) ?></h2>
            <div class="nx-gw-themen">
                <?php foreach (Texte::themen() as $code => $name) : ?>
                    <a class="nx-gw-thema" href="<?= Html::encode($url('index', ['thema' => $code])) ?>">
                        <i class="fa fa-<?= Html::encode(Texte::SYMBOLE[$code] ?? 'book') ?>"></i><?= Html::encode($name) ?>
                        <div class="nx-gw-leise"><?= Html::encode(sprintf(Texte::t('artikel_im_thema'), $jeThema[$code] ?? 0)) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($schlagworte !== []) : ?>
                <h2 style="font-size:1.15rem"><?= Html::encode(Texte::t('schlagworte')) ?></h2>
                <div class="nx-gw-worte">
                    <?php foreach ($schlagworte as $wort => $n) : ?>
                        <a class="nx-gw-wort" href="<?= Html::encode($url('index', ['wort' => $wort])) ?>"><?= Html::encode($wort) ?> <span class="nx-gw-leise"><?= (int)$n ?></span></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
