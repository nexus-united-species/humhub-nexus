<?php

use humhub\helpers\Html;
use nexus\modules\karte\assets\KarteAsset;
use nexus\modules\karte\services\Kacheln;
use nexus\modules\karte\services\Texte;
use yii\helpers\Json;
use yii\helpers\Url;

/* @var $punkte array<int, array{id: int, name: string, ort: string, lat: float, lng: float, text: string, url: string}> */
/* @var $suchende array<int, array{id: int, name: string, ort: string, lat: float, lng: float, text: string, url: string}> */
/* @var $ichSuche bool */
/* @var $gruendenUrl string|null */
/* @var $darfVerwalten bool */

KarteAsset::register($this);
$this->title = Texte::t('titel');
?>
<div class="panel panel-default nka">
    <div class="panel-heading">
        <h1 class="nka-titel">🗺️ <?= Html::encode(Texte::t('titel')) ?></h1>
    </div>
    <div class="panel-body">
        <p class="nka-einleitung"><?= Html::encode(Texte::t('einleitung')) ?></p>

        <?php // Die Karte steht immer da, auch ohne Punkte -- eine Seite ohne Karte wirkt kaputt. ?>
        <?= Html::tag('div', '', [
                'id' => 'nexus-karte',
                'class' => 'nka-karte',
                'role' => 'application',
                'aria-label' => Texte::t('titel'),
                'data-punkte' => Json::encode($punkte),
                'data-suchende' => Json::encode($suchende),
                'data-sucht-titel' => Texte::t('sucht_titel'),
                'data-kachel' => Url::to(['/nexus-karte/karte/kachel']),
                'data-min-zoom' => Kacheln::MIN_ZOOM,
                'data-max-zoom' => Kacheln::MAX_ZOOM,
                'data-zum-kreis' => Texte::t('zum_kreis'),
                'data-quelle' => Texte::t('karte_quelle'),
        ]) ?>
        <?php if ($suchende !== []): ?>
            <p class="nka-legende">
                <span class="nka-legende-punkt nka-legende-gemeinschaft"></span> <?= Html::encode(Texte::t('legende_gemeinschaft')) ?>
                <label class="nka-legende-schalter">
                    <input type="checkbox" id="nka-suchende-zeigen" checked>
                    <span class="nka-legende-punkt nka-legende-sucht"></span> <?= Html::encode(Texte::t('legende_sucht')) ?> (<?= count($suchende) ?>)
                </label>
            </p>
        <?php endif; ?>
        <?php if ($punkte === []): ?>
            <p class="nka-leer"><?= Html::encode(Texte::t('keine')) ?></p>
        <?php else: ?>
            <p class="nka-klein"><?= Html::encode(Texte::t('ort_hinweis')) ?></p>

            <h2 class="nka-zwischen"><?= Html::encode(Texte::t('liste')) ?></h2>
            <ul class="nka-liste">
                <?php foreach ($punkte as $p): ?>
                    <li class="nka-eintrag">
                        <div>
                            <strong><?= Html::encode($p['name']) ?></strong>
                            <span class="nka-ort">📍 <?= Html::encode($p['ort']) ?></span>
                            <?php if ($p['text'] !== ''): ?><p><?= Html::encode($p['text']) ?></p><?php endif; ?>
                        </div>
                        <div class="nka-knoepfe">
                            <button type="button" class="btn btn-default btn-sm nka-zeigen" data-nka-zeigen="<?= (int)$p['id'] ?>" aria-label="<?= Html::encode($p['ort']) ?>">🗺️</button>
                            <?= Html::a(Html::encode(Texte::t('zum_kreis')), $p['url'], ['class' => 'btn btn-primary btn-sm']) ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($suchende !== []): ?>
            <h2 class="nka-zwischen">🤝 <?= Html::encode(Texte::t('sucht_liste')) ?></h2>
            <ul class="nka-liste">
                <?php foreach ($suchende as $m): ?>
                    <li class="nka-eintrag">
                        <div>
                            <strong><?= Html::encode($m['name']) ?></strong>
                            <span class="nka-ort">📍 <?= Html::encode($m['ort']) ?></span>
                            <?php if ($m['text'] !== ''): ?><p><?= Html::encode($m['text']) ?></p><?php endif; ?>
                        </div>
                        <div class="nka-knoepfe">
                            <?= Html::a(Html::encode(Texte::t('zum_profil')), $m['url'], ['class' => 'btn btn-default btn-sm']) ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="nka-gruenden nka-mitstreiter">
            <strong>🤝 <?= Html::encode(Texte::t('sucht_box_titel')) ?></strong>
            <p><?= Html::encode(Texte::t('sucht_box_text')) ?></p>
            <?= Html::a(Html::encode(Texte::t($ichSuche ? 'sucht_box_aendern' : 'sucht_box_knopf')), ['/nexus-karte/suche/index'], ['class' => 'btn btn-primary', 'data-pjax-prevent' => '1']) ?>
        </div>

        <?php if ($gruendenUrl !== null): ?>
            <div class="nka-gruenden">
                <strong><?= Html::encode(Texte::t('gruenden_titel')) ?></strong>
                <p><?= Html::encode(Texte::t('gruenden_text')) ?></p>
                <?= Html::a(Html::encode(Texte::t('gruenden_knopf')), $gruendenUrl, ['class' => 'btn btn-primary']) ?>
            </div>
        <?php endif; ?>

        <?php if ($darfVerwalten): ?>
            <p class="nka-verwalten"><?= Html::a(Html::encode(Texte::t('verwalten')), ['/nexus-karte/verwaltung/index'], ['data-pjax-prevent' => '1']) ?></p>
        <?php endif; ?>
    </div>
</div>
