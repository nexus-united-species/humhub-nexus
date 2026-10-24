<?php

/**
 * Eine eigene Unterhaltung an das Team uebergeben.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $team nexus\modules\projekte\models\Team
 * @var $unterhaltungen array<int, array{id: int, title: string, updated_at: string}>
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$this->title = Texte::t('team_uebergeben');
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <h1 style="font-size:1.4rem"><i class="fa fa-share"></i> <?= Html::encode(sprintf(Texte::t('team_uebergeben_an'), $team->titel)) ?></h1>
        <div class="nx-pj-hinweis"><?= Html::encode(Texte::t('team_uebergeben_hilfe')) ?></div>

        <?php if ($unterhaltungen === []) : ?>
            <p><?= Html::encode(Texte::t('team_keine_unterhaltungen')) ?></p>
        <?php else : ?>
            <?= Html::beginForm($team->url('uebergeben'), 'post') ?>
                <p class="nx-pj-label"><?= Html::encode(Texte::t('team_welche_unterhaltung')) ?></p>
                <?php foreach ($unterhaltungen as $i => $u) : ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="mid" value="<?= (int)$u['id'] ?>" id="nx-tm-u<?= (int)$u['id'] ?>" <?= $i === 0 ? 'required' : '' ?>>
                        <label class="form-check-label" for="nx-tm-u<?= (int)$u['id'] ?>"><?= Html::encode((string)$u['title'] ?: '–') ?>
                            <span class="nx-pj-leise">· <?= Html::encode(Texte::datum($u['updated_at'])) ?></span></label>
                    </div>
                <?php endforeach; ?>
                <div class="nx-pj-felder" style="margin-top:.8rem">
                    <div class="nx-pj-ganz"><label for="nx-tm-notiz"><?= Html::encode(Texte::t('team_notiz')) ?></label>
                        <textarea id="nx-tm-notiz" class="form-control" name="notiz" rows="2" maxlength="3000" placeholder="<?= Html::encode(Texte::t('team_notiz_beispiel')) ?>"></textarea></div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="verlassen" value="1" id="nx-tm-verlassen">
                    <label class="form-check-label" for="nx-tm-verlassen"><?= Html::encode(Texte::t('team_verlassen')) ?></label>
                </div>
                <div class="nx-pj-knoepfe">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-share"></i> <?= Html::encode(Texte::t('team_uebergeben')) ?></button>
                    <a class="btn btn-light" href="<?= Html::encode($team->url()) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
                </div>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>
