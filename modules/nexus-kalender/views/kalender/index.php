<?php

use humhub\helpers\Html;
use nexus\modules\kalender\services\Texte;
use yii\helpers\Url;

/**
 * @var $modul \nexus\modules\kalender\Module
 * @var $vorhanden bool
 * @var $teilenKreise \humhub\modules\space\models\Space[]
 * @var $meldung string|null
 */

$this->pageTitle = Texte::titel();
?>
<div class="container">
    <div class="panel panel-default">
        <div class="panel-body">
            <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;justify-content:space-between;margin-bottom:.6rem">
                <h1 style="margin:0;font-size:1.4rem"><i class="fa fa-calendar"></i> <?= Html::encode(Texte::titel()) ?></h1>
                <?php if ($vorhanden) : ?>
                    <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                        <a class="btn btn-default btn-sm" href="<?= Html::encode(Url::to(['inhalt'])) ?>" target="_blank" rel="noopener"><i class="fa fa-external-link"></i> <?= Html::encode(Texte::t('neuer_tab')) ?></a>
                        <?php if ($modul->bestellLink !== '') : ?>
                            <a class="btn btn-primary btn-sm" href="<?= Html::encode($modul->bestellLink) ?>" target="_blank" rel="noopener"><i class="fa fa-shopping-cart"></i> <?= Html::encode(Texte::t('bestellen')) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($meldung) : ?>
                <div class="alert alert-warning"><?= Html::encode($meldung) ?></div>
            <?php endif; ?>
            <?php if ($modul->entwurf) : ?>
                <div class="alert alert-info"><?= Html::encode(Texte::t('entwurf')) ?></div>
            <?php endif; ?>

            <?php if ($vorhanden) : ?>
                <iframe src="<?= Html::encode(Url::to(['inhalt'])) ?>" title="<?= Html::encode(Texte::titel()) ?>" style="display:block;width:100%;height:82vh;min-height:520px;border:0"></iframe>
            <?php else : ?>
                <p><?= Html::encode(Texte::t('fehlt')) ?></p>
            <?php endif; ?>

            <?php if ($vorhanden && $teilenKreise !== []) : ?>
                <details style="margin-top:.8rem">
                    <summary style="cursor:pointer"><i class="fa fa-comments"></i> <?= Html::encode(Texte::t('teilen')) ?></summary>
                    <?= Html::beginForm(['teilen'], 'post', ['style' => 'margin-top:.5rem;max-width:36rem']) ?>
                        <label for="nx-kalender-kreis"><?= Html::encode(Texte::t('teilen_kreis')) ?></label>
                        <select id="nx-kalender-kreis" name="kreis" class="form-control" required data-prevent-statechange>
                            <?php foreach ($teilenKreise as $kreis) : ?>
                                <option value="<?= (int)$kreis->id ?>"><?= Html::encode($kreis->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="nx-kalender-satz" style="margin-top:.5rem"><?= Html::encode(Texte::t('teilen_satz')) ?></label>
                        <textarea id="nx-kalender-satz" name="text" class="form-control" maxlength="1000" data-prevent-statechange></textarea>
                        <p style="margin:.4rem 0;opacity:.75"><?= Html::encode(Texte::t('teilen_hinweis')) ?></p>
                        <button type="submit" class="btn btn-primary btn-sm"><?= Html::encode(Texte::t('teilen_senden')) ?></button>
                    <?= Html::endForm() ?>
                </details>
            <?php endif; ?>
        </div>
    </div>
</div>
