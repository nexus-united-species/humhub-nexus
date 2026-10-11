<?php

/**
 * @var $eintraege array
 * @var $mehr int
 * @var $imKreis bool
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;
use yii\helpers\Url;
?>
<div class="panel panel-default nx-pj">
    <div class="panel-heading"><i class="fa fa-check-square-o"></i> <strong><?= Html::encode(Texte::t($imKreis ? 'im_kreis' : 'meine_aufgaben')) ?></strong></div>
    <div class="panel-body" style="padding-top:0">
        <?= $this->render('@nexus-projekte/views/meine/_liste', ['eintraege' => $eintraege, 'kurz' => true]) ?>
        <a href="<?= Html::encode(Url::to(['/nexus-projekte/meine/index'])) ?>"><?= Html::encode(Texte::t('alle_aufgaben')) ?><?= $mehr > 0 ? ' (+' . (int)$mehr . ')' : '' ?> →</a>
    </div>
</div>
