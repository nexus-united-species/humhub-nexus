<?php

/**
 * Markdown- oder Textdatei einer Kachel im Portal lesen.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $info array
 * @var $html string|null  fertig und bereinigt; null = zu gross fuer die Ansicht
 * @var $datei nexus\modules\projekte\models\Datei
 * @var $uebersetzbar bool
 * @var $zuLang bool  zu lang zum Uebersetzen
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;
use nexus\modules\projekte\services\Uebersetzen;

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
            <?php if ($uebersetzbar && $zuLang) : ?>
                <div class="nx-pj-leise" style="margin-bottom:.6rem"><?= Html::encode(Texte::t('tr_zu_lang')) ?></div>
            <?php elseif ($uebersetzbar) : ?>
                <?= $this->render('_uebersetzen', ['kreis' => $kreis, 'lang' => true]) ?>
            <?php endif; ?>
            <div class="nx-pj-lesen"<?= Uebersetzen::attr('d', (int)$datei->id) ?>><?= $html ?></div>
        <?php endif; ?>
    </div>
</div>
