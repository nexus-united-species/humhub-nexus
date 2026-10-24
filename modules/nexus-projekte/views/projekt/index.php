<?php

/**
 * Alle Projekte eines Kreises als Kacheln.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $aktive nexus\modules\projekte\models\Projekt[]
 * @var $abgeschlossene nexus\modules\projekte\models\Projekt[]
 * @var $zahlen array<int, array{gesamt: int, fertig: int, warten: int, ueberfaellig: int}>
 * @var $darfAnlegen bool
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$this->title = Texte::t('projekte');
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <?= $this->render('/team/_reiter', ['kreis' => $kreis, 'aktiv' => 'projekte']) ?>
        <div class="nx-pj-kopf">
            <h1><i class="fa fa-columns"></i> <?= Html::encode(Texte::t('projekte')) ?></h1>
        </div>

        <?php if ($aktive === [] && !$darfAnlegen) : ?>
            <p><?= Html::encode(Texte::t('keine_projekte')) ?></p>
        <?php endif; ?>

        <div class="nx-pj-raster">
            <?php foreach ($aktive as $p) : ?>
                <?php $z = $zahlen[$p->id]; $prozent = $z['gesamt'] > 0 ? round(100 * $z['fertig'] / $z['gesamt']) : 0; ?>
                <a class="nx-pj-kachel" href="<?= Html::encode($p->url()) ?>">
                    <strong><?= Html::encode($p->titel) ?></strong>
                    <span class="nx-pj-leise">
                        <?= Html::encode(Texte::t('leitung')) ?>: <?= Html::encode($p->leitungNamen()) ?>
                        · <?= Html::encode($p->frist ? sprintf(Texte::t('bis'), Texte::datum($p->frist)) : Texte::t('laufend')) ?>
                    </span>
                    <div class="nx-pj-balken"><span style="width:<?= (int)$prozent ?>%"></span></div>
                    <span class="nx-pj-leise"><?= Html::encode(sprintf(Texte::t('fertig_von'), $z['fertig'], $z['gesamt'])) ?></span>
                    <?php if ($z['warten'] > 0) : ?>
                        <div class="nx-pj-gelb" style="font-size:.85rem;margin-top:.25rem"><i class="fa fa-thumbs-o-up"></i> <?= Html::encode(sprintf(Texte::t('warten_auf_dich'), $z['warten'])) ?></div>
                    <?php endif; ?>
                    <?php if ($z['ueberfaellig'] > 0) : ?>
                        <div class="nx-pj-rot" style="font-size:.85rem;margin-top:.25rem"><i class="fa fa-exclamation-circle"></i> <?= Html::encode(sprintf(Texte::t('ueberfaellig_n'), $z['ueberfaellig'])) ?></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            <?php if ($darfAnlegen) : ?>
                <a class="nx-pj-kachel nx-pj-neu" href="<?= Html::encode($kreis->createUrl('/nexus-projekte/projekt/formular')) ?>"><i class="fa fa-plus"></i> <?= Html::encode(Texte::t('neues_projekt')) ?></a>
            <?php endif; ?>
        </div>

        <?php if ($abgeschlossene !== []) : ?>
            <h2 style="font-size:1.05rem"><?= Html::encode(Texte::t('abgeschlossene')) ?></h2>
            <ul class="nx-pj-liste">
                <?php foreach ($abgeschlossene as $p) : ?>
                    <li><a href="<?= Html::encode($p->url()) ?>"><?= Html::encode($p->titel) ?></a>
                        <span class="nx-pj-leise"><?= Html::encode(sprintf(Texte::t('fertig_von'), $zahlen[$p->id]['fertig'], $zahlen[$p->id]['gesamt'])) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
