<?php

/**
 * @var $abzeichen nexus\modules\abzeichen\services\Abzeichen[]
 * @var $meine string[]
 * @var $momente nexus\modules\abzeichen\services\Moment[]
 * @var $zaehler array<string, int>
 */

use humhub\helpers\Html;
use nexus\modules\abzeichen\services\Texte;
use yii\helpers\Url;
?>
<div class="container">
<div class="panel panel-default nx-abz">
    <div class="panel-body">
        <h1 style="font-size:1.5rem;margin:0 0 .4rem"><i class="fa fa-star-o"></i> <?= Html::encode(Texte::t('abzeichen')) ?></h1>
        <p class="nx-abz-leise"><?= Html::encode(Texte::t('einleitung')) ?></p>

        <div class="nx-abz-liste">
            <?php foreach ($abzeichen as $abz) : ?>
                <a class="nx-abz-karte" href="<?= Html::encode(Url::to(['ansehen', 'a' => $abz->kennung])) ?>" style="--nx-abz-farbe:<?= Html::encode($abz->farbe()) ?>">
                    <span class="nx-abz-muster"><i class="fa fa-<?= Html::encode($abz->symbol()) ?>"></i></span>
                    <span>
                        <strong><?= Html::encode($abz->name()) ?></strong>
                        <?php if ($abz->kurz() !== '') : ?><br><span class="nx-abz-leise"><?= Html::encode($abz->kurz()) ?></span><?php endif; ?>
                        <?php if (in_array($abz->kennung, $meine, true)) : ?><br><span class="nx-abz-gold"><i class="fa fa-check"></i> <?= Html::encode(Texte::t('du_zeigst')) ?></span><?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($momente !== []) : ?>
            <h2 style="font-size:1.15rem;margin-top:1.5rem"><?= Html::encode(Texte::t('momente')) ?></h2>
            <p class="nx-abz-leise"><?= Html::encode(Texte::t('momente_hinweis')) ?></p>
            <ul class="nx-abz-momente">
                <?php foreach ($momente as $m) : ?>
                    <?php $n = (int)($zaehler[$m->kennung] ?? 0); ?>
                    <li>
                        <i class="fa fa-<?= Html::encode($m->symbol()) ?> nx-abz-gold"></i>
                        <strong><?= Html::encode($m->name()) ?>:</strong>
                        <?= $n > 0 ? (int)$n . ' ' . str_repeat('🏅', min($n, 12)) : Html::encode(Texte::t('noch_keine')) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
</div>
