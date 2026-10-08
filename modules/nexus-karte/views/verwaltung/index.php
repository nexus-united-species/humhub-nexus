<?php

use humhub\helpers\Html;
use humhub\modules\space\models\Space;
use nexus\modules\karte\assets\KarteAsset;
use nexus\modules\karte\models\Standort;
use nexus\modules\karte\services\Texte;
use yii\helpers\Url;

/* @var $verwaltbar array<int, array{0: Space, 1: Standort}> */
/* @var $unmarkiert Space[] */
/* @var $istAdmin bool */
/* @var $nurKreis int 0 = alle; sonst nur dieser Kreis (Aufruf aus dem Zahnrad-Menue) */
/* @var $suchKreis int */
/* @var $suchText string */
/* @var $treffer array<int, array{name: string, lat: float, lng: float}> */
/* @var $sucheOk bool */

KarteAsset::register($this);
$this->title = Texte::t('v_titel');
$ok = Yii::$app->session->getFlash('nexus-karte-ok');
$fehler = Yii::$app->session->getFlash('nexus-karte-fehler');
?>
<div class="panel panel-default nka">
    <div class="panel-heading">
        <h1 class="nka-titel">📍 <?= Html::encode(Texte::t('v_titel')) ?></h1>
    </div>
    <div class="panel-body">
        <p><?= Html::a(Html::encode(Texte::t('v_zur_karte')), ['/nexus-karte/karte/index'], ['data-pjax-prevent' => '1']) ?></p>
        <p class="nka-einleitung"><?= Html::encode(Texte::t('v_einleitung')) ?></p>

        <?php if ($ok): ?><div class="nka-meldung gut" role="status"><?= Html::encode($ok) ?></div><?php endif; ?>
        <?php if ($fehler): ?><div class="nka-meldung schlecht" role="alert"><?= Html::encode($fehler) ?></div><?php endif; ?>

        <?php foreach ($verwaltbar as [$space, $standort]): ?>
            <section class="nka-kasten" id="kreis-<?= (int)$space->id ?>">
                <h2 class="nka-zwischen"><?= Html::encode($space->name) ?></h2>
                <p>
                    <?= Html::encode(Texte::t('v_aktuell')) ?>:
                    <?php if ($standort->hatOrt()): ?>
                        <strong>📍 <?= Html::encode($standort->ort) ?></strong>
                    <?php else: ?>
                        <em><?= Html::encode(Texte::t('v_kein_ort')) ?></em>
                    <?php endif; ?>
                </p>

                <?php if ($standort->isNewRecord): ?>
                    <p class="nka-klein"><?= Html::encode(Texte::t('a_neu')) ?></p>
                <?php endif; ?>

                <?= Html::beginForm(Url::to(['index']) . '#kreis-' . (int)$space->id, 'get', ['class' => 'nka-suche', 'data-pjax-prevent' => '1']) ?>
                    <?= Html::hiddenInput('space', (int)$space->id) ?><?= Html::hiddenInput('kreis', $nurKreis) ?>
                    <label for="nka-q-<?= (int)$space->id ?>"><?= Html::encode(Texte::t('v_suchfeld')) ?></label>
                    <div class="nka-zeile">
                        <?= Html::textInput('q', $suchKreis === (int)$space->id ? $suchText : '', ['id' => 'nka-q-' . (int)$space->id, 'maxlength' => 120, 'required' => true]) ?>
                        <button type="submit" class="btn btn-primary"><?= Html::encode(Texte::t('v_suchen')) ?></button>
                    </div>
                <?= Html::endForm() ?>

                <?php if ($suchKreis === (int)$space->id && trim($suchText) !== ''): ?>
                    <?php if (!$sucheOk): ?>
                        <div class="nka-meldung schlecht" role="alert"><?= Html::encode(Texte::t('v_suche_fehler')) ?></div>
                    <?php elseif ($treffer === []): ?>
                        <div class="nka-meldung schlecht" role="alert"><?= Html::encode(Texte::t('v_nichts')) ?></div>
                    <?php else: ?>
                        <p><strong><?= Html::encode(Texte::t('v_treffer')) ?></strong></p>
                        <?php foreach ($treffer as $t): ?>
                            <?= Html::beginForm(['speichern'], 'post', ['class' => 'nka-treffer']) ?>
                                <?= Html::hiddenInput('space_id', (int)$space->id) ?><?= Html::hiddenInput('kreis', $nurKreis) ?>
                                <?= Html::hiddenInput('lat', $t['lat']) ?>
                                <?= Html::hiddenInput('lng', $t['lng']) ?>
                                <span class="nka-klein"><?= Html::encode($t['name']) ?></span>
                                <label>
                                    <span class="nka-klein"><?= Html::encode(Texte::t('v_anzeigename')) ?></span>
                                    <?= Html::textInput('ort', trim(explode(',', $t['name'])[0]), ['maxlength' => 150, 'required' => true]) ?>
                                </label>
                                <button type="submit" class="btn btn-primary btn-sm"><?= Html::encode(Texte::t('v_uebernehmen')) ?></button>
                            <?= Html::endForm() ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="nka-knoepfe">
                    <?php if ($standort->hatOrt()): ?>
                        <?= Html::beginForm(['ort-entfernen'], 'post') ?>
                            <?= Html::hiddenInput('space_id', (int)$space->id) ?><?= Html::hiddenInput('kreis', $nurKreis) ?>
                            <button type="submit" class="btn btn-default btn-sm"><?= Html::encode(Texte::t('v_ort_entfernen')) ?></button>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                    <?php if ($istAdmin && !$standort->isNewRecord): ?>
                        <?= Html::beginForm(['entmarkieren'], 'post', ['onsubmit' => 'return confirm(' . json_encode(Texte::t('a_entfernen_frage')) . ')']) ?>
                            <?= Html::hiddenInput('space_id', (int)$space->id) ?><?= Html::hiddenInput('kreis', $nurKreis) ?>
                            <button type="submit" class="btn btn-default btn-sm"><?= Html::encode(Texte::t('a_entfernen')) ?></button>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if ($istAdmin && $unmarkiert !== []): ?>
            <section class="nka-kasten nka-admin">
                <h2 class="nka-zwischen"><?= Html::encode(Texte::t('a_titel')) ?></h2>
                <p class="nka-klein"><?= Html::encode(Texte::t('a_text')) ?></p>
                <ul class="nka-liste">
                    <?php foreach ($unmarkiert as $space): ?>
                        <li class="nka-eintrag">
                            <span><?= Html::encode($space->name) ?></span>
                            <?= Html::beginForm(['markieren'], 'post') ?>
                                <?= Html::hiddenInput('space_id', (int)$space->id) ?><?= Html::hiddenInput('kreis', $nurKreis) ?>
                                <button type="submit" class="btn btn-default btn-sm"><?= Html::encode(Texte::t('a_markieren')) ?></button>
                            <?= Html::endForm() ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>
</div>
