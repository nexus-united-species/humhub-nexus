<?php

/**
 * Versionsgeschichte (nur Admins): jede Fassung ansehen und wiederherstellen.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $artikel nexus\modules\gesundheit\models\Artikel
 * @var $versionen nexus\modules\gesundheit\models\Version[]
 * @var $gezeigt nexus\modules\gesundheit\models\Version|null
 * @var $gezeigtHtml string|null
 */

use humhub\helpers\Html;
use nexus\modules\gesundheit\services\Texte;

$url = fn(string $route, array $p = []) => $kreis->createUrl('/nexus-gesundheit/wissen/' . $route, $p);
?>
<div class="panel panel-default nx-gw">
    <div class="panel-body">
        <div class="nx-gw-pfad">
            <a href="<?= Html::encode($url('index')) ?>"><?= Html::encode(Texte::t('zurueck')) ?></a> ›
            <a href="<?= Html::encode($artikel->url($kreis)) ?>"><?= Html::encode($artikel->titel) ?></a>
        </div>
        <h1 style="font-size:1.4rem"><?= Html::encode(Texte::t('versionen')) ?></h1>
        <ul class="nx-gw-liste">
            <?php foreach ($versionen as $i => $v) : ?>
                <li>
                    <a href="<?= Html::encode($url('versionen', ['a' => $artikel->slug, 'zeige' => $v->id])) ?>"><?= Html::encode(Yii::$app->formatter->asDatetime($v->created_at, 'medium')) ?></a>
                    · <?= Html::encode($v->autor ? $v->autor->displayName : 'Redaktion (Import)') ?>
                    · <?= Html::encode($v->grund) ?>
                    <?php if ($i === 0) : ?><span class="nx-gw-leise">(aktuell)</span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($gezeigt !== null) : ?>
            <hr>
            <h2 style="font-size:1.15rem"><?= Html::encode($gezeigt->titel) ?> <span class="nx-gw-leise">– <?= Html::encode(Yii::$app->formatter->asDatetime($gezeigt->created_at, 'medium')) ?></span></h2>
            <?= Html::beginForm($url('wiederherstellen', ['a' => $artikel->slug, 'version' => $gezeigt->id]), 'post', ['onsubmit' => 'return confirm("Diese Fassung wiederherstellen? Die aktuelle bleibt in der Versionsgeschichte erhalten.");']) ?>
                <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-undo"></i> Diese Fassung wiederherstellen</button>
            <?= Html::endForm() ?>
            <div class="nx-gw-text" style="margin-top:1rem"><?= $gezeigtHtml /* mit HtmlPurifier gereinigt */ ?></div>
        <?php endif; ?>
    </div>
</div>
