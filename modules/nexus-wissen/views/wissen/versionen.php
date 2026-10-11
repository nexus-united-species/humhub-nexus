<?php

/**
 * Versionsgeschichte (nur Admins): jede Fassung ansehen und wiederherstellen.
 *
 * @var $sammlung nexus\modules\wissen\services\Sammlung
 * @var $artikel nexus\modules\wissen\models\Artikel
 * @var $versionen nexus\modules\wissen\models\Version[]
 * @var $gezeigt nexus\modules\wissen\models\Version|null
 * @var $gezeigtHtml string|null
 */

use humhub\helpers\Html;
use nexus\modules\wissen\services\Texte;

?>
<div class="panel panel-default nx-ws">
    <div class="panel-body">
        <div class="nx-ws-pfad">
            <a href="<?= Html::encode($sammlung->url('index')) ?>"><?= Html::encode($sammlung->name()) ?></a> ›
            <a href="<?= Html::encode($artikel->url($sammlung)) ?>"><?= Html::encode($artikel->titel) ?></a>
        </div>
        <h1 style="font-size:1.4rem"><?= Html::encode(Texte::t('versionen')) ?></h1>
        <ul class="nx-ws-liste">
            <?php foreach ($versionen as $i => $v) : ?>
                <li>
                    <a href="<?= Html::encode($sammlung->url('versionen', ['a' => $artikel->slug, 'zeige' => $v->id])) ?>"><?= Html::encode(Yii::$app->formatter->asDatetime($v->created_at, 'medium')) ?></a>
                    · <?= Html::encode($v->autor ? $v->autor->displayName : 'Redaktion (Import)') ?>
                    · <?= Html::encode($v->grund) ?>
                    <?php if ($i === 0) : ?><span class="nx-ws-leise">(aktuell)</span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($gezeigt !== null) : ?>
            <hr>
            <h2 style="font-size:1.15rem"><?= Html::encode($gezeigt->titel) ?> <span class="nx-ws-leise">– <?= Html::encode(Yii::$app->formatter->asDatetime($gezeigt->created_at, 'medium')) ?></span></h2>
            <?= Html::beginForm($sammlung->url('wiederherstellen', ['a' => $artikel->slug, 'version' => $gezeigt->id]), 'post', ['onsubmit' => 'return confirm("Diese Fassung wiederherstellen? Die aktuelle bleibt in der Versionsgeschichte erhalten.");']) ?>
                <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-undo"></i> Diese Fassung wiederherstellen</button>
            <?= Html::endForm() ?>
            <div class="nx-ws-text" style="margin-top:1rem"><?= $gezeigtHtml /* mit HtmlPurifier gereinigt */ ?></div>
        <?php endif; ?>
    </div>
</div>
