<?php

/** @var yii\web\View $this */
/** @var nexus\modules\protectedLibrary\models\Book[] $buecher */

use nexus\modules\protectedLibrary\models\Book;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'N.E.X.U.S. Unterstuetzer-Bibliothek';
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4>Unterstuetzer-Bibliothek -- Buecher</h4>
    </div>
    <div class="panel-body">
        <a href="<?= Url::to(['upload']) ?>" class="btn btn-primary" style="margin-bottom: 16px;">
            Neues Buch hochladen (EPUB)
        </a>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>Titel</th>
                <th>Autor</th>
                <th>Sprache</th>
                <th>Kategorie</th>
                <th>Kapitel</th>
                <th>Sichtbar</th>
                <th>Aktionen</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($buecher as $buch): ?>
                <tr>
                    <td><?= Html::encode($buch->title) ?></td>
                    <td><?= Html::encode($buch->author ?? '-') ?></td>
                    <td><?= Html::encode(Book::SPRACHEN[$buch->language]['flagge'] ?? $buch->language) ?></td>
                    <td><?= Html::encode(Book::TYPEN[$buch->media_type]['name'] ?? $buch->media_type) ?></td>
                    <td><?= count($buch->chapters) ?></td>
                    <td><?= $buch->active ? '✅' : '⏸️' ?></td>
                    <td style="white-space: nowrap;">
                        <?= Html::beginForm(['toggle-active', 'id' => $buch->id], 'post', ['style' => 'display:inline']) ?>
                        <button type="submit" class="btn btn-xs btn-default">
                            <?= $buch->active ? 'Verbergen' : 'Sichtbar schalten' ?>
                        </button>
                        <?= Html::endForm() ?>

                        <?= Html::beginForm(['delete', 'id' => $buch->id], 'post', ['style' => 'display:inline']) ?>
                        <button type="submit" class="btn btn-xs btn-danger"
                                onclick="return confirm('Buch inkl. aller Kapitel wirklich loeschen?');">Loeschen</button>
                        <?= Html::endForm() ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($buecher)): ?>
                <tr><td colspan="7" class="text-muted">Noch keine Buecher.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
