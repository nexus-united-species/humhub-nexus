<?php

use humhub\helpers\Html;
use nexus\modules\karte\assets\KarteAsset;
use nexus\modules\karte\models\Suchender;
use nexus\modules\karte\services\Texte;

/* @var $eintrag Suchender|null */
/* @var $suchText string */
/* @var $treffer array<int, array{name: string, lat: float, lng: float}> */
/* @var $sucheOk bool */

KarteAsset::register($this);
$this->title = Texte::t('s_titel');
$ok = Yii::$app->session->getFlash('nexus-karte-ok');
$fehler = Yii::$app->session->getFlash('nexus-karte-fehler');
$nachricht = (string)($eintrag->nachricht ?? '');
?>
<div class="panel panel-default nka">
    <div class="panel-heading">
        <h1 class="nka-titel">🤝 <?= Html::encode(Texte::t('s_titel')) ?></h1>
    </div>
    <div class="panel-body">
        <p><?= Html::a(Html::encode(Texte::t('v_zur_karte')), ['/nexus-karte/karte/index'], ['data-pjax-prevent' => '1']) ?></p>
        <p class="nka-einleitung"><?= Html::encode(Texte::t('s_einleitung')) ?></p>
        <div class="nka-gruenden nka-sichtbar">
            <strong><?= Html::encode(Texte::t('s_sichtbar_titel')) ?></strong>
            <p><?= Html::encode(Texte::t('s_sichtbar')) ?></p>
        </div>

        <?php if ($ok): ?><div class="nka-meldung gut" role="status"><?= Html::encode($ok) ?></div><?php endif; ?>
        <?php if ($fehler): ?><div class="nka-meldung schlecht" role="alert"><?= Html::encode($fehler) ?></div><?php endif; ?>

        <?php if ($eintrag !== null): ?>
            <section class="nka-kasten">
                <h2 class="nka-zwischen"><?= Html::encode(Texte::t('s_mein')) ?></h2>
                <p><strong>📍 <?= Html::encode($eintrag->ort) ?></strong></p>
                <?= Html::beginForm(['nachricht'], 'post', ['class' => 'nka-suche']) ?>
                    <label for="nka-nachricht"><?= Html::encode(Texte::t('s_nachricht')) ?></label>
                    <?= Html::textarea('nachricht', $nachricht, ['id' => 'nka-nachricht', 'maxlength' => Suchender::MAX_NACHRICHT, 'rows' => 3]) ?>
                    <span class="nka-klein"><?= Html::encode(Texte::t('s_nachricht_hilfe')) ?></span>
                    <div class="nka-knoepfe">
                        <button type="submit" class="btn btn-primary btn-sm"><?= Html::encode(Texte::t('s_text_speichern')) ?></button>
                    </div>
                <?= Html::endForm() ?>
                <div class="nka-knoepfe">
                    <?= Html::beginForm(['loeschen'], 'post', ['onsubmit' => 'return confirm(' . json_encode(Texte::t('s_loeschen_frage')) . ')']) ?>
                        <button type="submit" class="btn btn-default btn-sm"><?= Html::encode(Texte::t('s_loeschen')) ?></button>
                    <?= Html::endForm() ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="nka-kasten">
            <h2 class="nka-zwischen"><?= Html::encode(Texte::t($eintrag === null ? 's_neu' : 's_ort_aendern')) ?></h2>
            <?= Html::beginForm(['index'], 'get', ['class' => 'nka-suche', 'data-pjax-prevent' => '1']) ?>
                <label for="nka-q"><?= Html::encode(Texte::t('v_suchfeld')) ?></label>
                <div class="nka-zeile">
                    <?= Html::textInput('q', $suchText, ['id' => 'nka-q', 'maxlength' => 120, 'required' => true]) ?>
                    <button type="submit" class="btn btn-primary"><?= Html::encode(Texte::t('v_suchen')) ?></button>
                </div>
                <span class="nka-klein"><?= Html::encode(Texte::t('s_ort_hilfe')) ?></span>
            <?= Html::endForm() ?>

            <?php if (trim($suchText) !== ''): ?>
                <?php if (!$sucheOk): ?>
                    <div class="nka-meldung schlecht" role="alert"><?= Html::encode(Texte::t('v_suche_fehler')) ?></div>
                <?php elseif ($treffer === []): ?>
                    <div class="nka-meldung schlecht" role="alert"><?= Html::encode(Texte::t('v_nichts')) ?></div>
                <?php else: ?>
                    <p><strong><?= Html::encode(Texte::t('v_treffer')) ?></strong></p>
                    <?php foreach ($treffer as $t): ?>
                        <?= Html::beginForm(['speichern'], 'post', ['class' => 'nka-treffer']) ?>
                            <?= Html::hiddenInput('lat', $t['lat']) ?>
                            <?= Html::hiddenInput('lng', $t['lng']) ?>
                            <span class="nka-klein"><?= Html::encode($t['name']) ?></span>
                            <label>
                                <span class="nka-klein"><?= Html::encode(Texte::t('v_anzeigename')) ?></span>
                                <?= Html::textInput('ort', trim(explode(',', $t['name'])[0]), ['maxlength' => 150, 'required' => true]) ?>
                            </label>
                            <label>
                                <span class="nka-klein"><?= Html::encode(Texte::t('s_nachricht')) ?></span>
                                <?= Html::textarea('nachricht', $nachricht, ['maxlength' => Suchender::MAX_NACHRICHT, 'rows' => 2]) ?>
                            </label>
                            <button type="submit" class="btn btn-primary btn-sm"><?= Html::encode(Texte::t('s_eintragen')) ?></button>
                        <?= Html::endForm() ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</div>
