<?php

use humhub\helpers\Html;

/* @var $fensterAn bool */
/* @var $tuerPersonen string */
/* @var $unbekannt string[] */
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

            <hr>
            <label for="nexus-meeting-tuer"><strong>Offene Tür</strong> – wer darf sie öffnen?</label>
            <p class="text-muted" style="font-size: 13px">Diese Menschen bekommen oben in der Leiste „Offene Tür“. Ist ihre Tür offen, sehen alle Mitglieder auf jeder Seite
                „… ist gerade da“ und kommen mit einem Klick ins Gespräch (auf Wunsch über einen Warteraum). Benutzernamen, mit Komma getrennt. Leer: niemand.</p>
            <?php if ($unbekannt !== []) : ?>
                <div class="alert alert-danger">Nicht gefunden (Benutzername prüfen): <?= Html::encode(implode(', ', $unbekannt)) ?> – nichts gespeichert.</div>
            <?php endif; ?>
            <?= Html::textInput('tuer_personen', $tuerPersonen, ['id' => 'nexus-meeting-tuer', 'class' => 'form-control', 'placeholder' => 'z. B. josh, roland']) ?>
            <br>
            <button type="submit" class="btn btn-primary">Speichern</button>
        <?= Html::endForm() ?>
    </div>
</div>
