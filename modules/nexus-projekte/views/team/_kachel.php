<?php

/**
 * Ein Team als Kachel (Kreis-Uebersicht und "Teams – mach mit").
 *
 * @var $team nexus\modules\projekte\models\Team
 * @var $kreisZeigen bool
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$aufgaben = $team->aufgaben;
$frei = count(array_filter($aufgaben, static fn($a) => $a->zustaendig_id === null && !$a->erledigt));
?>
<a class="nx-pj-kachel" href="<?= Html::encode($team->url()) ?>">
    <span class="nx-pj-team-status nx-pj-team-<?= Html::encode($team->status) ?>"><?= Html::encode(Texte::t('team_status_' . $team->status)) ?></span>
    <strong><?= Html::encode($team->titel) ?></strong>
    <?php if ($kreisZeigen && $team->kreis !== null) : ?>
        <span class="nx-pj-leise"><?= Html::encode($team->kreis->name) ?></span>
    <?php endif; ?>
    <?php if ($team->auftrag) : ?>
        <span class="nx-pj-leise nx-pj-team-auftrag"><?= Html::encode(mb_strimwidth($team->auftrag, 0, 180, '…')) ?></span>
    <?php endif; ?>
    <span class="nx-pj-leise" style="display:block;margin-top:.35rem">
        <?= Html::encode(sprintf(Texte::t('team_zahlen'), count($team->mitgliedIds()), count($aufgaben))) ?>
        <?php if ($frei > 0) : ?> · <span class="nx-pj-gelb"><?= Html::encode(sprintf(Texte::t('team_frei_n'), $frei)) ?></span><?php endif; ?>
    </span>
</a>
