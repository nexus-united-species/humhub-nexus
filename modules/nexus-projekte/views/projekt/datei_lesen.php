<?php

/**
 * Markdown- oder Textdatei einer Kachel im Portal lesen.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $info array
 * @var $html string|null  fertig und bereinigt; null = zu gross fuer die Ansicht
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$this->title = $info['anzeige'];
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <div class="nx-pj-leise"><a href="<?= Html::encode($karte->projekt->url()) ?>"><?= Html::encode($karte->projekt->titel) ?></a>
            · <a href="<?= Html::encode($karte->url()) ?>"><?= Html::encode($karte->titel) ?></a></div>
        <h1 style="font-size:1.4rem"><?= Html::encode($info['anzeige']) ?></h1>
        <div class="nx-pj-knoepfe" style="margin-bottom:.8rem">
            <a class="btn btn-light btn-sm" href="<?= Html::encode($karte->url()) ?>"><i class="fa fa-arrow-left"></i> <?= Html::encode(Texte::t('datei_lesen_zurueck')) ?></a>
            <a class="btn btn-light btn-sm" href="<?= Html::encode($info['url']) ?>" target="_blank" rel="noopener"><i class="fa fa-download"></i> <?= Html::encode(Texte::t('datei_herunterladen')) ?></a>
        </div>
        <?php if ($html === null) : ?>
            <div class="alert alert-info"><?= Html::encode(Texte::t('datei_lesen_zu_gross')) ?></div>
        <?php else : ?>
            <div class="nx-pj-lesen"><?= $html ?></div>
        <?php endif; ?>
    </div>
</div>
