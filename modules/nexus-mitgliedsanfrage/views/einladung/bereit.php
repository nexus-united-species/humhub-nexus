<?php

/**
 * @var $email string
 * @var $ziel string
 */

use humhub\helpers\Html;
use humhub\widgets\SiteLogo;
use nexus\modules\mitgliedsanfrage\Texte;

$this->pageTitle = Texte::t('bereit_titel');
?>
<div class="container container-registration nx-einladung">
    <?= SiteLogo::widget(['place' => SiteLogo::PLACE_LOGIN]) ?>
    <br>
    <div class="panel panel-default">
        <div class="panel-heading"><strong><?= Html::encode(Texte::t('bereit_titel')) ?></strong></div>
        <div class="panel-body">
            <p><?= Html::encode(Texte::t('bereit')) ?></p>
            <p style="font-size:1.2rem;font-weight:700;text-align:center;margin:1rem 0;word-break:break-all"><?= Html::encode($email) ?></p>
            <a class="btn btn-primary btn-lg" style="width:100%" href="<?= Html::encode($ziel) ?>"><?= Html::encode(Texte::t('konto_anlegen')) ?></a>
        </div>
    </div>
</div>
