<?php

/**
 * @var $abz nexus\modules\abzeichen\services\Abzeichen
 * @var $zeige bool
 * @var $meldung string|null
 */

use humhub\helpers\Html;
use nexus\modules\abzeichen\services\Texte;
use yii\helpers\Url;
?>
<div class="container">
<div class="panel panel-default nx-abz" style="--nx-abz-farbe:<?= Html::encode($abz->farbe()) ?>">
    <div class="panel-body">
        <div class="nx-abz-leise"><a href="<?= Html::encode(Url::to(['index'])) ?>">← <?= Html::encode(Texte::t('zurueck')) ?></a></div>
        <?php if ($meldung) : ?><div class="nx-abz-meldung"><?= Html::encode($meldung) ?></div><?php endif; ?>

        <div class="nx-abz-text"><?= $abz->textHtml() /* Markdown, mit HtmlPurifier gereinigt */ ?></div>

        <div class="nx-abz-bekennen">
            <?= Html::beginForm(Url::to(['bekennen', 'a' => $abz->kennung]), 'post') ?>
                <?= Html::hiddenInput('an', $zeige ? '0' : '1') ?>
                <?php if ($zeige) : ?>
                    <span class="nx-abz-gold"><i class="fa fa-<?= Html::encode($abz->symbol()) ?>"></i> <?= Html::encode(Texte::t('du_zeigst')) ?></span>
                    <button type="submit" class="btn btn-default btn-sm"><?= Html::encode(Texte::t('loesen')) ?></button>
                <?php else : ?>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-<?= Html::encode($abz->symbol()) ?>"></i> <?= Html::encode(Texte::t('bekennen')) ?></button>
                <?php endif; ?>
            <?= Html::endForm() ?>
            <div class="nx-abz-leise" style="margin-top:.5rem"><?= Html::encode(Texte::t('hinweis_bekenntnis')) ?></div>
        </div>
    </div>
</div>
</div>
