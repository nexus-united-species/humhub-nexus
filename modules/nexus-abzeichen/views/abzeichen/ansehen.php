<?php

/**
 * @var $abz nexus\modules\abzeichen\services\Abzeichen
 * @var $zeige bool
 * @var $meldung string|null
 */

use humhub\helpers\Html;
use humhub\modules\user\widgets\Image;
use nexus\modules\abzeichen\services\Texte;
use yii\helpers\Url;

$seite = $abz->seite(); // Markdown, mit HtmlPurifier gereinigt
$symbol = Html::encode($abz->symbol());
$bild = Image::widget(['user' => Yii::$app->user->identity, 'width' => 72, 'link' => false, 'hideOnlineStatus' => true]);
if (!$zeige) {
    // Vorschau: derselbe Rahmen, den das Abzeichen sonst automatisch um das Profilbild legt
    $bild = '<span class="nx-abz-rahmen">' . $bild . '<span class="nx-abz-siegel"><i class="fa fa-' . $symbol . '"></i></span></span>';
}
?>
<div class="container">
<div class="panel panel-default nx-abz nx-abz-seite" style="--nx-abz-farbe:<?= Html::encode($abz->farbe()) ?>">
    <div class="panel-body">
        <div class="nx-abz-leise"><a href="<?= Html::encode(Url::to(['index'])) ?>">← <?= Html::encode(Texte::t('zurueck')) ?></a></div>
        <?php if ($meldung) : ?><div class="nx-abz-meldung"><?= Html::encode($meldung) ?></div><?php endif; ?>

        <header class="nx-abz-kopf">
            <div class="nx-abz-gross-siegel" aria-hidden="true"><i class="fa fa-<?= $symbol ?>"></i></div>
            <div class="nx-abz-marke"><?= Html::encode(Texte::t('abzeichen_eins')) ?></div>
            <h1><?= Html::encode($seite['titel']) ?></h1>
            <?php $unter = $seite['untertitel'] !== '' ? $seite['untertitel'] : Html::encode($abz->kurz()); /* Untertitel ist bereits gereinigtes HTML */ ?>
            <?php if ($unter !== '') : ?><p class="nx-abz-kurz"><?= $unter ?></p><?php endif; ?>
        </header>

        <div class="nx-abz-text"><?= $seite['html'] ?></div>

        <div class="nx-abz-bekennen<?= $zeige ? ' nx-abz-aktiv' : '' ?>">
            <div class="nx-abz-bild<?= $zeige ? '' : ' nx-abz-vorschau' ?>"><?= $bild ?></div>
            <div class="nx-abz-bekennen-text">
                <?php if ($zeige) : ?>
                    <div class="nx-abz-gold"><strong><?= Html::encode(Texte::t('du_zeigst')) ?></strong></div>
                <?php else : ?>
                    <div class="nx-abz-leise"><?= Html::encode(Texte::t('vorschau')) ?></div>
                <?php endif; ?>
                <?= Html::beginForm(Url::to(['bekennen', 'a' => $abz->kennung]), 'post') ?>
                    <?= Html::hiddenInput('an', $zeige ? '0' : '1') ?>
                    <?php if ($zeige) : ?>
                        <button type="submit" class="btn btn-default btn-sm"><?= Html::encode(Texte::t('loesen')) ?></button>
                    <?php else : ?>
                        <button type="submit" class="btn btn-primary btn-lg nx-abz-knopf"><i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode(Texte::t('bekennen')) ?></button>
                    <?php endif; ?>
                <?= Html::endForm() ?>
                <div class="nx-abz-leise nx-abz-klein"><?= Html::encode(Texte::t('hinweis_bekenntnis')) ?></div>
            </div>
        </div>
    </div>
</div>
</div>
