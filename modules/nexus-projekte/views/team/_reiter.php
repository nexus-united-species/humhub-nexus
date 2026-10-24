<?php

/**
 * Reiter "Projekte | Teams" oben auf den Kreis-Seiten.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $aktiv string projekte|teams
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;
?>
<nav class="nx-pj-reiter" aria-label="<?= Html::encode(Texte::t('projekte')) ?> / <?= Html::encode(Texte::t('teams')) ?>">
    <a href="<?= Html::encode($kreis->createUrl('/nexus-projekte/projekt/index')) ?>" class="<?= $aktiv === 'projekte' ? 'nx-pj-reiter-an' : '' ?>"
        <?= $aktiv === 'projekte' ? 'aria-current="page"' : '' ?>><i class="fa fa-columns"></i> <?= Html::encode(Texte::t('projekte')) ?></a>
    <a href="<?= Html::encode($kreis->createUrl('/nexus-projekte/team/index')) ?>" class="<?= $aktiv === 'teams' ? 'nx-pj-reiter-an' : '' ?>"
        <?= $aktiv === 'teams' ? 'aria-current="page"' : '' ?>><i class="fa fa-users"></i> <?= Html::encode(Texte::t('teams')) ?></a>
</nav>
