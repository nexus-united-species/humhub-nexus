<?php

use humhub\helpers\Html;
use nexus\modules\arbeitszeit\models\Eintrag;
use nexus\modules\arbeitszeit\services\Texte;
use nexus\modules\arbeitszeit\services\ZeitService;
use yii\helpers\Url;

/* @var $kreise array<int,string> */
/* @var $summen array{gesamt: float, monat: float, offen: float} */
/* @var $eintraege Eintrag[] */
/* @var $fehler string[] */
/* @var $alt array */

$this->title = Texte::t('titel');
$zahl = fn(float $h) => rtrim(rtrim(number_format($h, 2, ',', '.'), '0'), ',') ?: '0';
$ok = Yii::$app->session->getFlash('nexus-az-ok');
$status = [
    Eintrag::STATUS_OFFEN => ['st_offen', 'naz-offen'],
    Eintrag::STATUS_FREIGEGEBEN => ['st_freigegeben', 'naz-frei'],
    Eintrag::STATUS_RUECKSPRACHE => ['st_ruecksprache', 'naz-rueck'],
];
?>
<style>
    .naz { max-width: 760px; margin: 0 auto; }
    .naz-kacheln { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 16px 0; }
    .naz-kachel { border: 1px solid rgba(0,0,0,.1); border-radius: 10px; padding: 14px; text-align: center; }
    .naz-kachel b { display: block; font-size: 26px; line-height: 1.2; }
    .naz-kachel span { font-size: 13px; opacity: .75; }
    .naz-tipp { background: rgba(212,175,55,.12); border-left: 4px solid #D4AF37; border-radius: 6px; padding: 10px 14px; margin-bottom: 18px; }
    .naz-feld { margin-bottom: 14px; }
    .naz-feld label { display: block; font-weight: 600; margin-bottom: 4px; }
    .naz-feld small { display: block; opacity: .7; margin-top: 3px; }
    .naz-feld input, .naz-feld select, .naz-feld textarea { width: 100%; font-size: 16px; padding: 8px 10px; border-radius: 6px; border: 1px solid rgba(0,0,0,.2); }
    .naz-zwei { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .naz-eintrag { border: 1px solid rgba(0,0,0,.1); border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; }
    .naz-kopf { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 6px; font-weight: 600; }
    .naz-badge { font-size: 13px; font-weight: 600; padding: 2px 8px; border-radius: 10px; white-space: nowrap; }
    .naz-offen { background: rgba(212,175,55,.2); } .naz-frei { background: rgba(40,167,69,.18); } .naz-rueck { background: rgba(0,123,255,.15); }
    .naz-klein { font-size: 13px; opacity: .75; }
    .naz-meldung { padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; }
    .naz-meldung.gut { background: rgba(40,167,69,.15); } .naz-meldung.schlecht { background: rgba(220,53,69,.15); }
    @media (max-width: 600px) { .naz-kacheln, .naz-zwei { grid-template-columns: 1fr; } .naz-kachel b { font-size: 22px; } }
</style>

<div class="panel panel-default naz">
    <div class="panel-heading"><h1 style="margin:0;font-size:24px">⏱ <?= Html::encode(Texte::t('titel')) ?></h1></div>
    <div class="panel-body">
        <p><?= Html::encode(Texte::t('einleitung')) ?></p>

        <?php if ($ok): ?><div class="naz-meldung gut" role="status"><?= Html::encode($ok) ?></div><?php endif; ?>
        <?php if ($fehler): ?>
            <div class="naz-meldung schlecht" role="alert">
                <b><?= Html::encode(Texte::t('fehler')) ?></b>
                <ul style="margin:6px 0 0 18px"><?php foreach ($fehler as $f): ?><li><?= Html::encode(Texte::t($f)) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="naz-kacheln">
            <div class="naz-kachel"><b><?= $zahl($summen['gesamt']) ?> <?= Html::encode(Texte::t('std')) ?></b><span><?= Html::encode(Texte::t('freigegeben_gesamt')) ?></span></div>
            <div class="naz-kachel"><b><?= $zahl($summen['monat']) ?> <?= Html::encode(Texte::t('std')) ?></b><span><?= Html::encode(Texte::t('diesen_monat')) ?></span></div>
            <div class="naz-kachel"><b><?= $zahl($summen['offen']) ?> <?= Html::encode(Texte::t('std')) ?></b><span><?= Html::encode(Texte::t('wartet')) ?></span></div>
        </div>

        <div class="naz-tipp"><?= Html::encode(Texte::t('tipp')) ?></div>

        <h2 style="font-size:19px"><?= Html::encode(Texte::t('neu')) ?></h2>
        <?php if (!$kreise): ?>
            <p><?= Html::encode(Texte::t('kein_kreis')) ?></p>
        <?php else: ?>
            <?= Html::beginForm(['/nexus-arbeitszeit/meine/speichern'], 'post') ?>
            <div class="naz-zwei">
                <div class="naz-feld">
                    <label for="naz-stunden"><?= Html::encode(Texte::t('stunden')) ?></label>
                    <input id="naz-stunden" name="stunden" type="number" inputmode="decimal" step="0.25" min="0.25" max="<?= (int)Eintrag::MAX_STUNDEN ?>" required value="<?= Html::encode($alt['stunden'] ?? '') ?>">
                    <small><?= Html::encode(Texte::t('stunden_hilfe')) ?></small>
                </div>
                <div class="naz-feld">
                    <label for="naz-datum"><?= Html::encode(Texte::t('datum')) ?></label>
                    <input id="naz-datum" name="datum" type="date" max="<?= date('Y-m-d') ?>" required value="<?= Html::encode($alt['datum'] ?? date('Y-m-d')) ?>">
                </div>
            </div>
            <div class="naz-feld">
                <label for="naz-kreis"><?= Html::encode(Texte::t('kreis')) ?></label>
                <select id="naz-kreis" name="space_id" required>
                    <option value=""><?= Html::encode(Texte::t('kreis_waehlen')) ?></option>
                    <?php foreach ($kreise as $id => $name): ?>
                        <option value="<?= (int)$id ?>" <?= (int)($alt['space_id'] ?? 0) === (int)$id ? 'selected' : '' ?>><?= Html::encode($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="naz-feld">
                <label for="naz-text"><?= Html::encode(Texte::t('taetigkeit')) ?></label>
                <textarea id="naz-text" name="beschreibung" rows="3" maxlength="<?= Eintrag::MAX_BESCHREIBUNG ?>" required><?= Html::encode($alt['beschreibung'] ?? '') ?></textarea>
                <small><?= Html::encode(Texte::t('taetigkeit_hilfe')) ?></small>
            </div>
            <button type="submit" class="btn btn-primary" style="min-height:44px;font-size:16px;padding:0 24px">✓ <?= Html::encode(Texte::t('speichern')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>

        <h2 style="font-size:19px;margin-top:28px"><?= Html::encode(Texte::t('meine_eintraege')) ?></h2>
        <?php if (!$eintraege): ?>
            <p class="naz-klein"><?= Html::encode(Texte::t('keine')) ?></p>
        <?php endif; ?>
        <?php foreach ($eintraege as $e): [$stText, $stKlasse] = $status[$e->status] ?? ['st_offen', 'naz-offen']; ?>
            <div class="naz-eintrag">
                <div class="naz-kopf">
                    <span><?= $e->stundenText() ?> <?= Html::encode(Texte::t('std')) ?> · <?= ZeitService::datumText($e->datum) ?></span>
                    <span class="naz-badge <?= $stKlasse ?>"><?= Html::encode(Texte::t($stText)) ?></span>
                </div>
                <div class="naz-klein"><?= Html::encode($e->space->name ?? '–') ?></div>
                <div style="margin-top:4px"><?= nl2br(Html::encode($e->beschreibung)) ?></div>
                <?php if ($e->status === Eintrag::STATUS_RUECKSPRACHE && $e->rueckfrage): ?>
                    <div class="naz-klein" style="margin-top:6px">💬 <?= Html::encode($e->pruefer->displayName ?? '') ?>: <?= Html::encode($e->rueckfrage) ?></div>
                <?php endif; ?>
                <?php if ($e->status === Eintrag::STATUS_OFFEN): ?>
                    <?= Html::beginForm(['/nexus-arbeitszeit/meine/loeschen', 'id' => $e->id], 'post', ['style' => 'margin-top:6px', 'onsubmit' => 'return confirm(' . json_encode(Texte::t('loeschen_frage')) . ')']) ?>
                    <button type="submit" class="btn btn-sm btn-light">🗑 <?= Html::encode(Texte::t('loeschen')) ?></button>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (Yii::$app->user->getIdentity()->isSystemAdmin()): ?>
            <p style="margin-top:20px"><a class="btn btn-default" href="<?= Url::to(['/nexus-arbeitszeit/freigabe/index']) ?>">✅ <?= Html::encode(Texte::t('freigabe_link')) ?></a></p>
        <?php endif; ?>
    </div>
</div>
