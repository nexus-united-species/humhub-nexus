<?php

/**
 * Wissensbereich anlegen (EinrichtenController) und Einstellungen eines im Portal angelegten
 * Wissensbereichs (RedaktionController::actionEinstellungen) -- dasselbe Formular.
 *
 * @var $eintrag nexus\modules\wissen\models\SammlungEintrag
 * @var $aktion string        Ziel des Formulars
 * @var $kreisName string
 * @var $zurueck string
 * @var $loeschenUrl string|null  nur bei einem leeren, schon angelegten Wissensbereich
 */

use humhub\helpers\Html;
use nexus\modules\wissen\services\Texte;
use nexus\modules\wissen\services\Themen;

$istNeu = $eintrag->isNewRecord;
$this->title = $istNeu ? Texte::t('einrichten') : Texte::t('einstellungen');
?>
<div class="panel panel-default nx-ws">
    <div class="panel-body">
        <div class="nx-ws-pfad"><a href="<?= Html::encode($zurueck) ?>"><?= Html::encode($istNeu ? $kreisName : $eintrag->name) ?></a></div>
        <h1 style="font-size:1.4rem"><i class="fa fa-book"></i> <?= Html::encode($this->title) ?></h1>
        <?php if ($istNeu) : ?><p class="nx-ws-leise"><?= Html::encode(Texte::t('einrichten_text')) ?></p><?php endif; ?>
        <?php if ($eintrag->hasErrors()) : ?>
            <div class="alert alert-danger"><?= Html::encode(implode(' ', $eintrag->getErrorSummary(true))) ?></div>
        <?php endif; ?>

        <?= Html::beginForm($aktion, 'post') ?>
            <div class="nx-ws-felder">
                <div><label for="nx-ws-name"><?= Html::encode(Texte::t('feld_name')) ?></label>
                    <input id="nx-ws-name" class="form-control" name="name" value="<?= Html::encode((string)$eintrag->name) ?>" required minlength="2" maxlength="120" placeholder="z. B. Gartenwissen"></div>
                <div><label for="nx-ws-symbol"><?= Html::encode(Texte::t('feld_symbol')) ?></label>
                    <select id="nx-ws-symbol" class="form-control" name="symbol">
                        <?php foreach (Themen::SYMBOLE as $s) : ?>
                            <option value="<?= Html::encode($s) ?>" <?= $s === $eintrag->symbol ? 'selected' : '' ?>><?= Html::encode($s) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div style="grid-column: 1 / -1"><label for="nx-ws-beschreibung"><?= Html::encode(Texte::t('feld_beschreibung')) ?></label>
                    <input id="nx-ws-beschreibung" class="form-control" name="beschreibung" value="<?= Html::encode((string)$eintrag->beschreibung) ?>" maxlength="255"></div>
                <div style="grid-column: 1 / -1"><label for="nx-ws-hinweis"><?= Html::encode(Texte::t('feld_hinweis')) ?></label>
                    <textarea id="nx-ws-hinweis" class="form-control" name="hinweis" maxlength="2000" rows="2" placeholder="<?= Html::encode(Texte::t('feld_hinweis_hilfe')) ?>"><?= Html::encode((string)$eintrag->hinweis) ?></textarea></div>
            </div>
            <div class="nx-ws-knoepfe">
                <button type="submit" class="btn btn-primary"><?= Html::encode($istNeu ? Texte::t('anlegen') : Texte::t('speichern')) ?></button>
                <a class="btn btn-light" href="<?= Html::encode($zurueck) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
            </div>
        <?= Html::endForm() ?>

        <?php if ($loeschenUrl !== null) : ?>
            <?= Html::beginForm($loeschenUrl, 'post', ['style' => 'margin-top:1.5rem']) ?>
                <button type="submit" class="btn btn-sm btn-danger" data-nx-ws-bestaetigen="<?= Html::encode(Texte::t('sammlung_loeschen_frage')) ?>">
                    <i class="fa fa-trash"></i> <?= Html::encode(Texte::t('sammlung_loeschen')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>
