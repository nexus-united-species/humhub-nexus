<?php

/**
 * Alle Teams eines Kreises.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $teams nexus\modules\projekte\models\Team[]
 * @var $darfAusschreiben bool
 * @var $meldung string|null
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$this->title = Texte::t('teams');
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <?= $this->render('_reiter', ['kreis' => $kreis, 'aktiv' => 'teams']) ?>
        <div class="nx-pj-kopf">
            <div>
                <h1><i class="fa fa-users"></i> <?= Html::encode(Texte::t('teams')) ?></h1>
                <span class="nx-pj-leise"><?= Html::encode(Texte::t('teams_erklaerung')) ?></span>
            </div>
        </div>
        <?php if ($meldung) : ?><div class="nx-pj-meldung"><?= Html::encode($meldung) ?></div><?php endif; ?>

        <?php if ($teams === [] && !$darfAusschreiben) : ?>
            <p><?= Html::encode(Texte::t('teams_keine')) ?></p>
        <?php endif; ?>

        <div class="nx-pj-raster">
            <?php foreach ($teams as $t) : ?>
                <?= $this->render('_kachel', ['team' => $t, 'kreisZeigen' => false]) ?>
            <?php endforeach; ?>
            <?php if ($darfAusschreiben) : ?>
                <a class="nx-pj-kachel nx-pj-neu" href="<?= Html::encode($kreis->createUrl('/nexus-projekte/team/formular')) ?>"><i class="fa fa-plus"></i> <?= Html::encode(Texte::t('team_neu')) ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
