<?php

use humhub\helpers\Html;

/* @var $fensterAn bool */
?>
<div class="panel panel-default">
    <div class="panel-heading"><strong>Meetings live</strong></div>
    <div class="panel-body">
        <p>Läuft ein Online-Treffen aus dem Kalender, sehen die Mitglieder des Kreises auf jeder Seite einen kleinen Hinweis „Jetzt live“.</p>
        <?= Html::beginForm(['index'], 'post') ?>
            <div class="form-check" style="margin: 12px 0">
                <?= Html::checkbox('fenster', $fensterAn, ['value' => '1', 'id' => 'nexus-meeting-fenster', 'class' => 'form-check-input']) ?>
                <label for="nexus-meeting-fenster" class="form-check-label">
                    <strong>Kleines Meeting-Fenster im Portal</strong> – „Beitreten“ öffnet das Treffen schwebend über dem Portal.
                </label>
            </div>
            <p class="text-muted" style="font-size: 13px">Ohne Haken führt „Beitreten“ wie bisher auf die Meeting-Seite. Den Hinweis „Jetzt live“ gibt es in beiden Fällen.
                Soll gar nichts mehr erscheinen: das Modul unter „Module“ ausschalten.</p>
            <button type="submit" class="btn btn-primary">Speichern</button>
        <?= Html::endForm() ?>
    </div>
</div>
