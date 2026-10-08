<?php

use humhub\helpers\Html;
use humhub\modules\space\models\Space;
use nexus\modules\arbeitszeit\models\Eintrag;
use yii\helpers\Url;

/* @var $eintrag Eintrag */
/* @var $fehler string[] */
/* @var $kreise Space[] */

$this->title = 'Eintrag bearbeiten';
$stundenWert = rtrim(rtrim(number_format((float)$eintrag->stunden, 2, '.', ''), '0'), '.');
?>
<style>
    .naz { max-width: 700px; margin: 0 auto; }
    .naz-feld { margin-bottom: 14px; }
    .naz-feld label { display: block; font-weight: 600; margin-bottom: 4px; }
    .naz-feld input, .naz-feld select, .naz-feld textarea { width: 100%; font-size: 16px; padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(0,0,0,.2); }
    .naz-zwei { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .naz-meldung { padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; background: rgba(220,53,69,.15); }
    .naz-verlauf { font-size: 13px; opacity: .8; white-space: pre-line; background: rgba(0,0,0,.04); border-radius: 6px; padding: 8px 12px; }
    @media (max-width: 600px) { .naz-zwei { grid-template-columns: 1fr; } }
</style>

<div class="panel panel-default naz">
    <div class="panel-heading"><h1 style="margin:0;font-size:24px">✏️ Eintrag bearbeiten</h1></div>
    <div class="panel-body">
        <p><b><?= Html::encode($eintrag->name()) ?></b> · eingetragen am <?= date('d.m.Y H:i', strtotime($eintrag->created_at)) ?>
            über <?= Html::encode(['assistent' => 'den Assistenten', 'formular' => 'das Formular', 'telegram' => 'Telegram (übernommen)'][$eintrag->quelle] ?? $eintrag->quelle) ?></p>

        <?php if ($fehler): ?>
            <div class="naz-meldung" role="alert"><b>Bitte prüfen:</b><ul style="margin:6px 0 0 18px"><?php foreach ($fehler as $f): ?><li><?= Html::encode($f) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <?= Html::beginForm(['/nexus-arbeitszeit/freigabe/bearbeiten', 'id' => $eintrag->id], 'post') ?>
        <div class="naz-zwei">
            <div class="naz-feld">
                <label for="naz-stunden">Stunden</label>
                <input id="naz-stunden" name="stunden" type="number" inputmode="decimal" step="0.25" min="0.25" max="<?= (int)Eintrag::MAX_STUNDEN ?>" required value="<?= Html::encode($stundenWert) ?>">
            </div>
            <div class="naz-feld">
                <label for="naz-datum">Tag</label>
                <input id="naz-datum" name="datum" type="date" max="<?= date('Y-m-d') ?>" required value="<?= Html::encode($eintrag->datum) ?>">
            </div>
        </div>
        <div class="naz-feld">
            <label for="naz-kreis">Kreis</label>
            <select id="naz-kreis" name="space_id">
                <option value="">(ohne Kreis)</option>
                <?php foreach ($kreise as $k): ?>
                    <option value="<?= (int)$k->id ?>" <?= (int)$eintrag->space_id === (int)$k->id ? 'selected' : '' ?>><?= Html::encode($k->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="naz-feld">
            <label for="naz-text">Tätigkeit</label>
            <textarea id="naz-text" name="beschreibung" rows="3" maxlength="<?= Eintrag::MAX_BESCHREIBUNG ?>" required><?= Html::encode($eintrag->beschreibung) ?></textarea>
        </div>
        <div class="naz-feld">
            <label for="naz-status">Status</label>
            <select id="naz-status" name="status">
                <?php foreach ([Eintrag::STATUS_OFFEN => '⏳ wartet auf Freigabe', Eintrag::STATUS_FREIGEGEBEN => '✅ freigegeben', Eintrag::STATUS_RUECKSPRACHE => '💬 Rücksprache'] as $wert => $text): ?>
                    <option value="<?= $wert ?>" <?= $eintrag->status === $wert ? 'selected' : '' ?>><?= $text ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="min-height:44px;padding:0 24px">💾 Speichern</button>
        <a class="btn btn-default" href="<?= Url::to(['/nexus-arbeitszeit/freigabe/index']) ?>">Abbrechen</a>
        <?= Html::endForm() ?>

        <?php if ($eintrag->verlauf): ?>
            <h2 style="font-size:16px;margin-top:22px">Bisherige Änderungen</h2>
            <div class="naz-verlauf"><?= Html::encode($eintrag->verlauf) ?></div>
        <?php endif; ?>

        <hr style="margin:24px 0 14px">
        <?= Html::beginForm(['/nexus-arbeitszeit/freigabe/entfernen', 'id' => $eintrag->id], 'post', ['onsubmit' => 'return confirm("Diesen Eintrag wirklich endgültig löschen?")']) ?>
            <button type="submit" class="btn btn-danger btn-sm">🗑 Eintrag löschen</button>
            <span style="font-size:13px;opacity:.75;margin-left:8px">Nur wenn er ganz falsch ist – für Korrekturen lieber oben ändern.</span>
        <?= Html::endForm() ?>
    </div>
</div>
