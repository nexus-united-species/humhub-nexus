<?php

/**
 * Projekt anlegen oder bearbeiten.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $projekt nexus\modules\projekte\models\Projekt
 * @var $fehler string|null
 */

use humhub\helpers\Html;
use humhub\modules\user\widgets\UserPickerField;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\services\Entscheidung;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;

$neu = $projekt->isNewRecord;
$zurueck = $neu ? $kreis->createUrl('/nexus-projekte/projekt/index') : $projekt->url();
$this->title = Texte::t($neu ? 'projekt_anlegen' : 'projekt_bearbeiten');
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <h1 style="font-size:1.4rem"><?= Html::encode($this->title) ?></h1>
        <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>

        <?= Html::beginForm($kreis->createUrl('/nexus-projekte/projekt/speichern', $neu ? [] : ['p' => $projekt->id]), 'post') ?>
            <div class="nx-pj-felder">
                <div class="nx-pj-ganz"><label for="nx-pj-titel"><?= Html::encode(Texte::t('titel')) ?></label>
                    <input id="nx-pj-titel" class="form-control" name="titel" value="<?= Html::encode($projekt->titel) ?>" required maxlength="190"></div>
                <div class="nx-pj-ganz"><label for="nx-pj-ziel"><?= Html::encode(Texte::t('ziel')) ?></label>
                    <textarea id="nx-pj-ziel" class="form-control" name="ziel" rows="3" maxlength="5000"><?= Html::encode((string)$projekt->ziel) ?></textarea></div>
                <div><label><?= Html::encode(Texte::t('leitung')) ?></label>
                    <?= UserPickerField::widget([
                        'name' => 'leitung',
                        'selection' => $projekt->isNewRecord ? [Yii::$app->user->getIdentity()] : $projekt->leitungen(),
                        'url' => $kreis->createUrl('/space/membership/search'),
                        'maxSelection' => nexus\modules\projekte\models\Projekt::MAX_LEITUNG,
                        'placeholder' => Texte::t('personen'),
                    ]) ?></div>
                <div><label for="nx-pj-frist"><?= Html::encode(Texte::t('frist')) ?></label>
                    <input id="nx-pj-frist" class="form-control" type="date" name="frist" value="<?= Html::encode((string)$projekt->frist) ?>"></div>
                <div class="nx-pj-ganz"><label for="nx-pj-regel"><?= Html::encode(Texte::t('regel')) ?></label>
                    <select id="nx-pj-regel" class="form-control" name="regel">
                        <?php foreach (Entscheidung::REGELN as $regel) : ?>
                            <option value="<?= $regel ?>" <?= Entscheidung::regel($projekt->regel) === $regel ? 'selected' : '' ?>><?= Html::encode(Texte::t('regel_' . $regel)) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div><label for="nx-pj-frist-tage"><?= Html::encode(Texte::t('frist_tage')) ?></label>
                    <input id="nx-pj-frist-tage" class="form-control" type="number" name="frist_tage" min="1" max="<?= Projekt::MAX_FRIST_TAGE ?>" value="<?= (int)($projekt->frist_tage ?: 14) ?>"></div>
                <div><label for="nx-pj-weitergabe"><?= Html::encode(Texte::t('weitergabe')) ?></label>
                    <input id="nx-pj-weitergabe" class="form-control" name="weitergabe" maxlength="100" value="<?= Html::encode((string)$projekt->weitergabe) ?>">
                    <span class="nx-pj-leise"><?= Html::encode(Texte::t('weitergabe_hilfe')) ?></span></div>
                <?php if (!$neu) : ?>
                    <div><label for="nx-pj-status"><?= Html::encode(Texte::t('status')) ?></label>
                        <select id="nx-pj-status" class="form-control" name="status">
                            <option value="aktiv" <?= $projekt->status === 'aktiv' ? 'selected' : '' ?>><?= Html::encode(Texte::t('aktiv')) ?></option>
                            <option value="abgeschlossen" <?= $projekt->status === 'abgeschlossen' ? 'selected' : '' ?>><?= Html::encode(Texte::t('abgeschlossen')) ?></option>
                        </select></div>
                <?php endif; ?>
            </div>
            <div class="nx-pj-knoepfe">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= Html::encode(Texte::t($neu ? 'projekt_anlegen' : 'speichern')) ?></button>
                <a class="btn btn-light" href="<?= Html::encode($zurueck) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
            </div>
        <?= Html::endForm() ?>

        <?php if (!$neu && Rechte::istKreisAdmin($kreis, Yii::$app->user->getIdentity())) : ?>
            <hr>
            <?= Html::beginForm($kreis->createUrl('/nexus-projekte/projekt/loeschen', ['p' => $projekt->id]), 'post', ['data-nx-pj-frage' => Texte::t('loeschen_frage')]) ?>
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> <?= Html::encode(Texte::t('loeschen')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>
