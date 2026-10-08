<?php

use humhub\helpers\Html;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\arbeitszeit\models\Eintrag;
use nexus\modules\arbeitszeit\services\ZeitService;
use yii\helpers\Url;

/* @var $monat string */
/* @var $offen Eintrag[] */
/* @var $ruecksprache Eintrag[] */
/* @var $jePerson array */
/* @var $jeKreis array */
/* @var $monate string[] */
/* @var $imZeitraum Eintrag[] */

$this->title = 'Stunden freigeben';
$zahl = fn($h) => rtrim(rtrim(number_format((float)$h, 2, ',', '.'), '0'), ',') ?: '0';
$monatName = fn(string $m) => $m === 'alle' ? 'alle Monate' : date('m/Y', strtotime($m . '-01'));
$ok = Yii::$app->session->getFlash('nexus-az-ok');
$summeMonat = array_sum(array_column($jePerson, 'summe'));
?>
<style>
    .naz { max-width: 900px; margin: 0 auto; }
    .naz-eintrag { border: 1px solid rgba(0,0,0,.1); border-left: 4px solid #D4AF37; border-radius: 10px; padding: 12px 14px; margin-bottom: 12px; }
    .naz-kopf { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 6px; }
    .naz-klein { font-size: 13px; opacity: .75; }
    .naz-aktionen { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; align-items: center; }
    .naz-aktionen form { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin: 0; }
    .naz-aktionen input[type=text] { min-width: 220px; padding: 6px 8px; border-radius: 6px; border: 1px solid rgba(0,0,0,.2); }
    .naz-tabelle { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .naz-tabelle th, .naz-tabelle td { padding: 6px 8px; border-bottom: 1px solid rgba(0,0,0,.08); text-align: left; }
    .naz-tabelle td.zahl, .naz-tabelle th.zahl { text-align: right; font-variant-numeric: tabular-nums; }
    .naz-meldung { padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; background: rgba(40,167,69,.15); }
</style>

<div class="panel panel-default naz">
    <div class="panel-heading"><h1 style="margin:0;font-size:24px">✅ Stunden freigeben</h1></div>
    <div class="panel-body">
        <?php if ($ok): ?><div class="naz-meldung" role="status"><?= Html::encode($ok) ?></div><?php endif; ?>

        <h2 style="font-size:19px">Wartet auf Freigabe (<?= count($offen) ?>)</h2>
        <?php if (!$offen): ?><p class="naz-klein">Alles erledigt – nichts offen. 🎉</p><?php endif; ?>
        <?php foreach ($offen as $e): ?>
            <div class="naz-eintrag">
                <div class="naz-kopf">
                    <b><?= Html::encode($e->name()) ?> · <?= $e->stundenText() ?> Std. · <?= ZeitService::datumText($e->datum) ?></b>
                    <span class="naz-klein"><?= Html::encode($e->space->name ?? '–') ?><?= $e->quelle === 'assistent' ? ' · über den Assistenten' : '' ?></span>
                </div>
                <div style="margin-top:6px"><?= nl2br(Html::encode($e->beschreibung)) ?></div>
                <div class="naz-aktionen">
                    <?= Html::beginForm(['/nexus-arbeitszeit/freigabe/freigeben', 'id' => $e->id], 'post') ?>
                        <button type="submit" class="btn btn-primary">✅ Freigeben</button>
                    <?= Html::endForm() ?>
                    <?= Html::beginForm(['/nexus-arbeitszeit/freigabe/ruecksprache', 'id' => $e->id], 'post') ?>
                        <input type="text" name="grund" maxlength="500" placeholder="Rückfrage (optional), z. B. „Welche Aufgabe genau?“" aria-label="Rückfrage">
                        <button type="submit" class="btn btn-default">💬 Rücksprache</button>
                    <?= Html::endForm() ?>
                </div>
            </div>
        <?php endforeach; ?>

        <h2 style="font-size:19px;margin-top:28px">Übersicht freigegebener Stunden</h2>
        <form method="get" action="<?= Url::to(['/nexus-arbeitszeit/freigabe/index']) ?>" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px">
            <label for="naz-monat" style="margin:0">Zeitraum:</label>
            <select id="naz-monat" name="monat" onchange="this.form.submit()">
                <?php foreach ($monate as $m): ?>
                    <option value="<?= Html::encode($m) ?>" <?= $m === $monat ? 'selected' : '' ?>><?= Html::encode($monatName($m)) ?></option>
                <?php endforeach; ?>
                <option value="alle" <?= $monat === 'alle' ? 'selected' : '' ?>>alle Monate</option>
            </select>
            <a class="btn btn-default" href="<?= Url::to(['/nexus-arbeitszeit/freigabe/export', 'monat' => $monat]) ?>">⬇ Als Excel-Datei herunterladen</a>
        </form>
        <p class="naz-klein">Zeitraum <?= Html::encode($monatName($monat)) ?>: insgesamt <b><?= $zahl($summeMonat) ?> Std.</b> Die Excel-Datei enthält alle Einträge des Zeitraums, auch offene und Rücksprachen.</p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px">
            <div>
                <h3 style="font-size:16px">Je Person</h3>
                <table class="naz-tabelle">
                    <tr><th>Name</th><th class="zahl">Einträge</th><th class="zahl">Stunden</th></tr>
                    <?php foreach ($jePerson as $z): ?>
                        <tr><td><?= Html::encode($z['user_id'] ? (User::findOne($z['user_id'])->displayName ?? '?') : ($z['name_extern'] ?: '?')) ?></td><td class="zahl"><?= (int)$z['anzahl'] ?></td><td class="zahl"><?= $zahl($z['summe']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$jePerson): ?><tr><td colspan="3" class="naz-klein">Keine freigegebenen Stunden in diesem Zeitraum.</td></tr><?php endif; ?>
                </table>
            </div>
            <div>
                <h3 style="font-size:16px">Je Kreis</h3>
                <table class="naz-tabelle">
                    <tr><th>Kreis</th><th class="zahl">Stunden</th></tr>
                    <?php foreach ($jeKreis as $z): ?>
                        <tr><td><?= Html::encode($z['space_id'] ? (Space::findOne($z['space_id'])->name ?? '?') : '–') ?></td><td class="zahl"><?= $zahl($z['summe']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$jeKreis): ?><tr><td colspan="2" class="naz-klein">–</td></tr><?php endif; ?>
                </table>
            </div>
        </div>

        <h2 style="font-size:19px;margin-top:20px">Alle Einträge im Zeitraum (<?= count($imZeitraum) ?>)</h2>
        <p class="naz-klein">Zum Korrigieren auf ✏️ tippen. Geändert wird hier im Portal – die Excel-Datei in der Nextcloud („Kreis 0 - Admins“) zieht automatisch nach.</p>
        <div style="overflow-x:auto">
        <table class="naz-tabelle">
            <tr><th>Tag</th><th>Name</th><th>Kreis</th><th class="zahl">Std.</th><th>Tätigkeit</th><th>Status</th><th></th></tr>
            <?php foreach ($imZeitraum as $e): ?>
                <tr>
                    <td><?= ZeitService::datumText($e->datum) ?></td>
                    <td><?= Html::encode($e->name()) ?></td>
                    <td><?= Html::encode($e->space->name ?? '–') ?></td>
                    <td class="zahl"><?= $e->stundenText() ?></td>
                    <td><?= Html::encode(mb_strimwidth($e->beschreibung, 0, 70, '…')) ?></td>
                    <td><?= ['offen' => '⏳', 'freigegeben' => '✅', 'ruecksprache' => '💬'][$e->status] ?? '' ?></td>
                    <td><a href="<?= Url::to(['/nexus-arbeitszeit/freigabe/bearbeiten', 'id' => $e->id]) ?>" title="Bearbeiten">✏️</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$imZeitraum): ?><tr><td colspan="7" class="naz-klein">Keine Einträge in diesem Zeitraum.</td></tr><?php endif; ?>
        </table>
        </div>

        <?php if ($ruecksprache): ?>
            <h2 style="font-size:19px;margin-top:20px">Rücksprache offen (<?= count($ruecksprache) ?>)</h2>
            <p class="naz-klein">Diese Einträge zählen nicht. Nach Klärung trägt die Person die Stunden am besten neu ein.</p>
            <?php foreach ($ruecksprache as $e): ?>
                <div class="naz-klein" style="margin-bottom:6px">
                    <b><?= Html::encode($e->name()) ?></b> · <?= $e->stundenText() ?> Std. · <?= ZeitService::datumText($e->datum) ?> · <?= Html::encode($e->space->name ?? '–') ?> – <?= Html::encode($e->beschreibung) ?>
                    <?php if ($e->rueckfrage): ?><br>💬 <?= Html::encode($e->pruefer->displayName ?? '') ?>: <?= Html::encode($e->rueckfrage) ?><?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
