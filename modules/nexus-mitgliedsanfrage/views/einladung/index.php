<?php

/**
 * @var $kreis humhub\modules\space\models\Space
 * @var $email string
 * @var $fehler string|null
 * @var $anmelden string
 */

use humhub\helpers\Html;
use humhub\modules\space\widgets\Image;
use humhub\widgets\LanguageChooser;
use humhub\widgets\SiteLogo;
use nexus\modules\mitgliedsanfrage\Texte;

$this->pageTitle = Texte::t('titel');
?>
<div class="container container-registration nx-einladung">
    <?= SiteLogo::widget(['place' => SiteLogo::PLACE_LOGIN]) ?>
    <br>
    <div class="panel panel-default">
        <div class="panel-heading"><strong><?= Html::encode(Texte::t('titel')) ?></strong></div>
        <div class="panel-body">
            <div style="display:flex;gap:.8rem;align-items:center;margin-bottom:1rem">
                <?= Image::widget(['space' => $kreis, 'width' => 48, 'link' => false]) ?>
                <span style="font-size:1.1rem"><?= Html::encode(Texte::t('eingeladen', ['{kreis}' => $kreis->name])) ?></span>
            </div>
            <p><?= Html::encode(Texte::t('erklaerung')) ?></p>
            <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>
            <?= Html::beginForm('', 'post') ?>
                <input type="email" name="email" class="form-control" required autocomplete="email"
                       value="<?= Html::encode($email) ?>" placeholder="<?= Html::encode(Texte::t('email')) ?>"
                       aria-label="<?= Html::encode(Texte::t('email')) ?>" style="font-size:1.05rem;padding:.6rem .75rem">
                <button type="submit" class="btn btn-primary btn-lg" style="margin-top:.75rem;width:100%"><?= Html::encode(Texte::t('weiter')) ?></button>
            <?= Html::endForm() ?>
            <p style="margin-top:1rem;text-align:center"><a href="<?= Html::encode($anmelden) ?>"><?= Html::encode(Texte::t('schon_konto')) ?></a></p>
        </div>
    </div>
    <?= LanguageChooser::widget(['vertical' => true, 'hideLabel' => true]) ?>
</div>
