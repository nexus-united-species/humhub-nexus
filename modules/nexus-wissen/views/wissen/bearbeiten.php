<?php

/**
 * Bearbeiten (nur Bearbeitende). Der Text ist Markdown -- wie in den Dateien des Imports.
 *
 * @var $sammlung nexus\modules\wissen\services\Sammlung
 * @var $artikel nexus\modules\wissen\models\Artikel
 * @var $themen array<string, array{name: string, symbol: string}>
 * @var $schlagworte string
 * @var $suchwoerter string
 * @var $fehler string|null
 */

use humhub\helpers\Html;
use nexus\modules\wissen\services\Texte;
?>
<div class="panel panel-default nx-ws">
    <div class="panel-body">
        <div class="nx-ws-pfad">
            <a href="<?= Html::encode($sammlung->url()) ?>"><?= Html::encode($sammlung->name()) ?></a> ›
            <a href="<?= Html::encode($artikel->url($sammlung)) ?>"><?= Html::encode($artikel->titel) ?></a>
        </div>
        <h1 style="font-size:1.4rem"><?= Html::encode(Texte::t('bearbeiten')) ?></h1>
        <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>

        <?= Html::beginForm($sammlung->url('speichern', ['a' => $artikel->slug]), 'post') ?>
            <div class="nx-ws-felder">
                <div style="grid-column: 1 / -1"><label>Titel</label><input class="form-control" name="titel" value="<?= Html::encode($artikel->titel) ?>" required maxlength="255"></div>
                <div><label>Thema</label>
                    <select class="form-control" name="thema">
                        <option value="" <?= $artikel->thema === '' ? 'selected' : '' ?>>–</option>
                        <?php foreach ($themen as $code => $thema) : ?>
                            <option value="<?= Html::encode($code) ?>" <?= $code === $artikel->thema ? 'selected' : '' ?>><?= Html::encode($thema['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label><?= Html::encode(Texte::t('geprueft')) ?></label><input class="form-control" type="date" name="pruefdatum" value="<?= Html::encode((string)$artikel->pruefdatum) ?>"></div>
                <div><label><?= Html::encode(Texte::t('naechste_pruefung')) ?></label><input class="form-control" type="date" name="wiedervorlage" value="<?= Html::encode((string)$artikel->wiedervorlage) ?>"></div>
                <div style="grid-column: 1 / -1"><label>Schlagworte (sichtbar, mit Komma getrennt)</label><input class="form-control" name="schlagworte" value="<?= Html::encode($schlagworte) ?>"></div>
                <div style="grid-column: 1 / -1"><label>Suchwörter (Alltagswörter und andere Bezeichnungen, nur für die Suche)</label><input class="form-control" name="suchwoerter" value="<?= Html::encode($suchwoerter) ?>"></div>
            </div>
            <label>Text (Markdown: ## Überschrift, - Aufzählung, [Linktext](https://…))</label>
            <textarea class="form-control nx-ws-editor" name="inhalt" required><?= Html::encode($artikel->inhalt) ?></textarea>
            <div class="nx-ws-felder" style="margin-top:.75rem">
                <div style="grid-column: 1 / -1"><label>Was wurde geändert? (für die Versionsgeschichte)</label><input class="form-control" name="grund" maxlength="255" placeholder="z. B. Angabe nach Vorschlag von … korrigiert"></div>
            </div>
            <div class="nx-ws-knoepfe">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Speichern</button>
                <a class="btn btn-light" href="<?= Html::encode($artikel->url($sammlung)) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>
