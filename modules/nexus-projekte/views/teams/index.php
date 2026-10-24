<?php

/**
 * "Teams – mach mit": Teams aus allen sichtbaren Kreisen.
 *
 * @var $liste array<int, array{team: nexus\modules\projekte\models\Team, mitglied: bool}>
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$suchend = array_filter($liste, static fn($e) => $e['team']->status === 'suchend');
$aktiv = array_filter($liste, static fn($e) => $e['team']->status !== 'suchend');
?>
<div class="container">
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <h1 style="font-size:1.5rem;margin:0 0 .3rem"><i class="fa fa-users"></i> <?= Html::encode(Texte::t('teams_mach_mit')) ?></h1>
        <p class="nx-pj-leise"><?= Html::encode(Texte::t('teams_mach_mit_erklaerung')) ?></p>

        <?php if ($liste === []) : ?>
            <p><?= Html::encode(Texte::t('teams_ueberall_keine')) ?></p>
        <?php endif; ?>

        <?php foreach ([['team_suchen_mitwirkende', $suchend], ['team_arbeiten', $aktiv]] as [$ueberschrift, $gruppe]) : ?>
            <?php if ($gruppe === []) { continue; } ?>
            <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t($ueberschrift)) ?></h2>
            <div class="nx-pj-raster">
                <?php foreach ($gruppe as $e) : ?>
                    <?= $this->render('/team/_kachel', ['team' => $e['team'], 'kreisZeigen' => true]) ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <p class="nx-pj-leise"><?= Html::encode(Texte::t('teams_nicht_mitglied')) ?></p>
    </div>
</div>
</div>
