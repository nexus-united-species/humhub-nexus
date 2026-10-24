<?php

/**
 * @var $eintraege array
 * @var $geleitet nexus\modules\projekte\models\Projekt[]
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;
?>
<div class="container">
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;margin:0 0 1rem">
            <h1 style="font-size:1.5rem;margin:0"><i class="fa fa-check-square-o"></i> <?= Html::encode(Texte::t('meine_aufgaben')) ?></h1>
            <a class="btn btn-light btn-sm" href="<?= Html::encode(\yii\helpers\Url::to(['/nexus-projekte/teams/index'])) ?>"><i class="fa fa-users"></i> <?= Html::encode(Texte::t('teams_mach_mit')) ?></a>
        </div>
        <?php if ($eintraege === []) : ?>
            <p><?= Html::encode(Texte::t('meine_leer')) ?></p>
        <?php else : ?>
            <?= $this->render('_liste', ['eintraege' => $eintraege, 'kurz' => false]) ?>
        <?php endif; ?>

        <?php if ($geleitet !== []) : ?>
            <h2 style="font-size:1.1rem;margin-top:1.5rem"><?= Html::encode(Texte::t('ich_leite')) ?></h2>
            <ul class="nx-pj-liste">
                <?php foreach ($geleitet as $p) : ?>
                    <?php $f = $p->fortschritt(); ?>
                    <li>
                        <span><a href="<?= Html::encode($p->url()) ?>"><strong><?= Html::encode($p->titel) ?></strong></a>
                            <span class="nx-pj-leise">· <?= Html::encode($p->kreis->name) ?></span></span>
                        <span class="nx-pj-leise"><?= Html::encode(sprintf(Texte::t('fertig_von'), $f['fertig'], $f['gesamt'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
</div>
