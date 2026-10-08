<?php

/** @var yii\web\View $this */
/** @var array $anzahlJeSprache */

use nexus\modules\protectedLibrary\models\Book;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Unterstuetzer-Bibliothek';
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4>📚 Unterstuetzer-Bibliothek</h4>
    </div>
    <div class="panel-body">
        <p class="text-muted">In welcher Sprache moechtest du stoebern?</p>

        <div class="row">
            <?php foreach (Book::SPRACHEN as $code => $info): ?>
                <div class="col-sm-4" style="margin-bottom: 16px;">
                    <a href="<?= Url::to(['reader/language', 'language' => $code]) ?>"
                       class="list-group-item text-center" style="padding: 32px 16px; font-size: 20px;">
                        <div style="font-size: 40px;"><?= $info['flagge'] ?></div>
                        <strong><?= Html::encode($info['name']) ?></strong>
                        <div class="text-muted" style="font-size: 14px;">
                            <?= $anzahlJeSprache[$code] ?> Titel
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
