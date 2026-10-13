<?php

/**
 * @var $abzeichen nexus\modules\abzeichen\services\Abzeichen[]
 * @var $meine string[]
 * @var $momente nexus\modules\abzeichen\services\Moment[]
 * @var $zaehler array<string, int>
 * @var $geteilt array<string, int>
 * @var $kreis humhub\modules\space\models\Space|null
 * @var $meldung string|null
 */

use humhub\helpers\Html;
use nexus\modules\abzeichen\services\Teilen;
use nexus\modules\abzeichen\services\Texte;
use yii\helpers\Url;

$darfTeilen = $kreis !== null && $kreis->isMember(Yii::$app->user->id);
?>
<div class="container">
<div class="panel panel-default nx-abz">
    <div class="panel-body">
        <h1 style="font-size:1.5rem;margin:0 0 .4rem"><i class="fa fa-star-o"></i> <?= Html::encode(Texte::t('abzeichen')) ?></h1>
        <p class="nx-abz-leise"><?= Html::encode(Texte::t('einleitung')) ?></p>
        <?php if ($meldung) : ?><div class="nx-abz-meldung"><?= Html::encode($meldung) ?></div><?php endif; ?>

        <div class="nx-abz-liste">
            <?php foreach ($abzeichen as $abz) : ?>
                <a class="nx-abz-karte" href="<?= Html::encode(Url::to(['ansehen', 'a' => $abz->kennung])) ?>" style="--nx-abz-farbe:<?= Html::encode($abz->farbe()) ?>">
                    <span class="nx-abz-muster"><i class="fa fa-<?= Html::encode($abz->symbol()) ?>"></i></span>
                    <span>
                        <strong><?= Html::encode($abz->name()) ?></strong>
                        <?php if ($abz->kurz() !== '') : ?><br><span class="nx-abz-leise"><?= Html::encode($abz->kurz()) ?></span><?php endif; ?>
                        <?php if (in_array($abz->kennung, $meine, true)) : ?><br><span class="nx-abz-gold"><i class="fa fa-check"></i> <?= Html::encode(Texte::t('du_zeigst')) ?></span><?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($momente !== []) : ?>
            <h2 style="font-size:1.15rem;margin-top:1.75rem"><?= Html::encode(Texte::t('momente')) ?></h2>
            <p class="nx-abz-leise"><?= Html::encode(Texte::t('momente_hinweis')) ?></p>
            <div class="nx-abz-momente">
                <?php foreach ($momente as $m) : ?>
                    <?php
                    $wert = (int)($zaehler[$m->kennung] ?? 0);
                    $stufe = $m->stufeBei($wert);
                    $naechste = $m->naechsteStufe($wert);
                    ?>
                    <section class="nx-abz-moment">
                        <div class="nx-abz-moment-kopf">
                            <span class="nx-abz-muster"><i class="fa fa-<?= Html::encode($m->symbol()) ?>"></i></span>
                            <div>
                                <strong><?= Html::encode($m->name()) ?></strong>
                                <div class="nx-abz-wert"><?= $wert > 0 ? Html::encode($m->wertText($wert)) : Html::encode(Texte::t('noch_keine')) ?></div>
                                <?php if ($stufe !== null) : ?><div class="nx-abz-gold"><i class="fa fa-certificate"></i> <?= Html::encode($stufe[1]) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <?php if ($m->stufen() !== []) : ?>
                            <ol class="nx-abz-stufen" aria-label="<?= Html::encode(Texte::t('stufen')) ?>">
                                <?php foreach ($m->stufen() as $schwelle => $name) : ?>
                                    <li class="<?= $wert >= $schwelle ? 'nx-abz-erreicht' : '' ?>">
                                        <span class="nx-abz-stufe-zahl"><?= Html::encode($m->wertText($schwelle)) ?></span>
                                        <span><?= Html::encode($name) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                            <div class="nx-abz-leise nx-abz-klein">
                                <?= Html::encode($naechste !== null
                                    ? strtr(Texte::t('naechste'), ['{stufe}' => $naechste[1], '{wert}' => $m->wertText($naechste[0])])
                                    : Texte::t('alle_stufen')) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($darfTeilen && $wert > 0) : ?>
                            <?php if ((int)($geteilt[$m->kennung] ?? 0) >= $wert) : ?>
                                <div class="nx-abz-leise nx-abz-klein"><i class="fa fa-check"></i> <?= Html::encode(Texte::t('geteilt')) ?></div>
                            <?php else : ?>
                                <details class="nx-abz-teilen">
                                    <summary><i class="fa fa-bullhorn"></i> <?= Html::encode(Texte::t('teilen')) ?></summary>
                                    <?= Html::beginForm(Url::to(['teilen', 'm' => $m->kennung]), 'post') ?>
                                        <div class="nx-abz-leise nx-abz-klein"><?= Html::encode(strtr(Texte::t('teilen_wo'), ['{kreis}' => $kreis->name])) ?></div>
                                        <blockquote class="nx-abz-vorschau-text"><?= Html::encode($m->teilenText($wert)) ?></blockquote>
                                        <textarea name="notiz" class="form-control" rows="2" maxlength="<?= Teilen::MAX_NOTIZ ?>" placeholder="<?= Html::encode(Texte::t('notiz')) ?>"></textarea>
                                        <button type="submit" class="btn btn-primary btn-sm" style="margin-top:.5rem"><?= Html::encode(Texte::t('senden')) ?></button>
                                    <?= Html::endForm() ?>
                                </details>
                            <?php endif; ?>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
