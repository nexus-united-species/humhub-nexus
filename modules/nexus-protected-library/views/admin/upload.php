<?php

/** @var yii\web\View $this */

use nexus\modules\protectedLibrary\models\Book;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Buch hochladen';
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4>Neues Buch hochladen</h4>
    </div>
    <div class="panel-body">
        <p class="text-muted">
            Nur .epub-Dateien. Titel und Autor werden aus der Datei uebernommen,
            koennen hier aber ueberschrieben werden. Die hochgeladene Datei selbst
            wird nach dem Einlesen nicht aufbewahrt.
        </p>

        <?= Html::beginForm('', 'post', ['enctype' => 'multipart/form-data']) ?>

        <div class="form-group">
            <label>EPUB-Datei</label>
            <input type="file" name="epub" accept=".epub" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Titel (optional, ueberschreibt den Titel aus der Datei)</label>
            <input type="text" name="title" class="form-control">
        </div>

        <div class="form-group">
            <label>Autor (optional, ueberschreibt den Autor aus der Datei)</label>
            <input type="text" name="author" class="form-control">
        </div>

        <div class="form-group">
            <label>Sprache</label>
            <select name="language" class="form-control" style="width: auto;">
                <?php foreach (Book::SPRACHEN as $code => $info): ?>
                    <option value="<?= Html::encode($code) ?>"><?= Html::encode($info['flagge'] . ' ' . $info['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Kategorie</label>
            <select name="media_type" class="form-control" style="width: auto;">
                <?php foreach (Book::TYPEN as $typCode => $info): ?>
                    <option value="<?= Html::encode($typCode) ?>"><?= Html::encode($info['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Importieren</button>
        <a href="<?= Url::to(['index']) ?>" class="btn btn-default">Abbrechen</a>

        <?= Html::endForm() ?>
    </div>
</div>
