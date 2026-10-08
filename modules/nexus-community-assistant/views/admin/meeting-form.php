<?php

/** @var yii\web\View $this */
/** @var nexus\modules\communityAssistant\models\Meeting $meeting */
/** @var humhub\modules\space\models\Space[] $spaces */
/** @var int[] $ausgewaehlteSpaceIds */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $meeting->isNewRecord ? 'Neues Meeting' : 'Meeting bearbeiten';

$wochentage = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
$typ = $meeting->istEinmalig() ? 'einmalig' : 'wiederkehrend';
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4><?= Html::encode($this->title) ?></h4>
    </div>
    <div class="panel-body">
        <?= Html::beginForm('', 'post') ?>

        <div class="form-group">
            <label>Titel (nur intern, erscheint nicht im Beitrag)</label>
            <?= Html::textInput('title', $meeting->title, ['class' => 'form-control', 'required' => true]) ?>
        </div>

        <div class="form-group">
            <label>Beitragstext (wird 1:1 gepostet)</label>
            <?= Html::textarea('message', $meeting->message, ['class' => 'form-control', 'rows' => 8, 'required' => true]) ?>
        </div>

        <div class="form-group">
            <label>Wiederholung</label><br>
            <label class="radio-inline">
                <input type="radio" name="typ" value="wiederkehrend" <?= $typ === 'wiederkehrend' ? 'checked' : '' ?>
                       onclick="document.getElementById('wiederkehrend-felder').style.display='block'; document.getElementById('einmalig-felder').style.display='none';">
                Wiederkehrend (woechentlich)
            </label>
            <label class="radio-inline">
                <input type="radio" name="typ" value="einmalig" <?= $typ === 'einmalig' ? 'checked' : '' ?>
                       onclick="document.getElementById('wiederkehrend-felder').style.display='none'; document.getElementById('einmalig-felder').style.display='block';">
                Einmalig
            </label>
        </div>

        <div id="wiederkehrend-felder" style="display: <?= $typ === 'wiederkehrend' ? 'block' : 'none' ?>;">
            <div class="form-group">
                <label>Wochentag</label>
                <?= Html::dropDownList('recurrence_weekday', $meeting->recurrence_weekday ?: 1, $wochentage, ['class' => 'form-control', 'style' => 'width: 200px;']) ?>
            </div>
            <div class="form-group">
                <label>Uhrzeit (Europe/Berlin, HH:MM)</label>
                <?= Html::textInput('recurrence_time', $meeting->recurrence_time ?: '18:00', ['class' => 'form-control', 'style' => 'width: 120px;', 'placeholder' => '18:00']) ?>
            </div>
        </div>

        <div id="einmalig-felder" style="display: <?= $typ === 'einmalig' ? 'block' : 'none' ?>;">
            <div class="form-group">
                <label>Zeitpunkt</label>
                <?= Html::textInput('event_date', $meeting->event_date ? date('Y-m-d\TH:i', strtotime($meeting->event_date)) : '', ['type' => 'datetime-local', 'class' => 'form-control', 'style' => 'width: 260px;']) ?>
            </div>
        </div>

        <div class="form-group">
            <label>Ziel-Spaces (mehrere moeglich, z.B. Kreis 1 + Kreis 2)</label><br>
            <?php foreach ($spaces as $space): ?>
                <label class="checkbox-inline" style="display: block;">
                    <input type="checkbox" name="space_ids[]" value="<?= (int)$space->id ?>"
                        <?= in_array((int)$space->id, $ausgewaehlteSpaceIds, true) ? 'checked' : '' ?>>
                    <?= Html::encode($space->name) ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <label class="checkbox-inline">
                <input type="checkbox" name="active" value="1" <?= $meeting->active ? 'checked' : '' ?>>
                Aktiv (wird automatisch gepostet)
            </label>
        </div>

        <button type="submit" class="btn btn-primary">Speichern</button>
        <a href="<?= Url::to(['index']) ?>" class="btn btn-default">Abbrechen</a>

        <?= Html::endForm() ?>
    </div>
</div>
