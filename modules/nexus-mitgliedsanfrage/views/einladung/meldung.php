<?php

/**
 * @var $titel string
 * @var $text string
 * @var $knopf string|null
 * @var $ziel string|null
 */

use humhub\helpers\Html;
use humhub\widgets\SiteLogo;

$this->pageTitle = $titel;
?>
<div class="container container-registration nx-einladung">
    <?= SiteLogo::widget(['place' => SiteLogo::PLACE_LOGIN]) ?>
    <br>
    <div class="panel panel-default">
        <div class="panel-heading"><strong><?= Html::encode($titel) ?></strong></div>
        <div class="panel-body">
            <p style="font-size:1.05rem"><?= Html::encode($text) ?></p>
            <?php if (!empty($knopf) && !empty($ziel)) : ?>
                <a class="btn btn-primary btn-lg" style="width:100%" href="<?= Html::encode($ziel) ?>"><?= Html::encode($knopf) ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
