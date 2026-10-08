<?php

/** @var yii\web\View $this */
/** @var string $sprache */
/** @var array $anzahlJeTyp */

use nexus\modules\protectedLibrary\models\Book;
use yii\helpers\Html;
use yii\helpers\Url;

$spracheInfo = Book::SPRACHEN[$sprache];
$this->title = 'Bibliothek -- ' . $spracheInfo['name'];
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4>
            <a href="<?= Url::to(['reader/index']) ?>" style="color: inherit;">📚 Bibliothek</a>
            &nbsp;/&nbsp; <?= $spracheInfo['flagge'] ?> <?= Html::encode($spracheInfo['name']) ?>
        </h4>
    </div>
    <div class="panel-body">
        <p class="text-muted">Was moechtest du dir ansehen?</p>

        <div class="row">
            <?php foreach (Book::TYPEN as $typCode => $info): ?>
                <?php $anzahl = $anzahlJeTyp[$typCode]; ?>
                <div class="col-sm-4 col-md-3" style="margin-bottom: 16px;">
                    <?php if ($anzahl > 0): ?>
                        <a href="<?= Url::to(['reader/category', 'language' => $sprache, 'mediaType' => $typCode]) ?>"
                           class="list-group-item text-center" style="padding: 24px 12px;">
                            <i class="fa fa-<?= Html::encode($info['icon']) ?>" style="font-size: 28px;"></i>
                            <div style="margin-top: 8px;"><strong><?= Html::encode($info['name']) ?></strong></div>
                            <div class="text-muted" style="font-size: 13px;"><?= $anzahl ?> Titel</div>
                        </a>
                    <?php else: ?>
                        <div class="list-group-item text-center text-muted" style="padding: 24px 12px; opacity: 0.5;">
                            <i class="fa fa-<?= Html::encode($info['icon']) ?>" style="font-size: 28px;"></i>
                            <div style="margin-top: 8px;"><strong><?= Html::encode($info['name']) ?></strong></div>
                            <div style="font-size: 13px;">bald verfuegbar</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
