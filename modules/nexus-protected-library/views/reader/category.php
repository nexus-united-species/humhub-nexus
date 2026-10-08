<?php

/** @var yii\web\View $this */
/** @var string $sprache */
/** @var string $mediaType */
/** @var nexus\modules\protectedLibrary\models\Book[] $buecher */

use nexus\modules\protectedLibrary\models\Book;
use yii\helpers\Html;
use yii\helpers\Url;

$spracheInfo = Book::SPRACHEN[$sprache];
$typInfo = Book::TYPEN[$mediaType];
$this->title = 'Bibliothek -- ' . $typInfo['name'];
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4>
            <a href="<?= Url::to(['reader/index']) ?>" style="color: inherit;">📚 Bibliothek</a>
            &nbsp;/&nbsp;
            <a href="<?= Url::to(['reader/language', 'language' => $sprache]) ?>" style="color: inherit;">
                <?= $spracheInfo['flagge'] ?> <?= Html::encode($spracheInfo['name']) ?>
            </a>
            &nbsp;/&nbsp; <?= Html::encode($typInfo['name']) ?>
        </h4>
    </div>
    <div class="panel-body">
        <?php if (empty($buecher)): ?>
            <p class="text-muted">Hier gibt es noch nichts -- schau bald wieder vorbei.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($buecher as $buch): ?>
                    <a href="<?= Url::to(['reader/book', 'id' => $buch->id]) ?>" class="list-group-item">
                        <strong><?= Html::encode($buch->title) ?></strong>
                        <?php if ($buch->author): ?>
                            <br><span class="text-muted"><?= Html::encode($buch->author) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
