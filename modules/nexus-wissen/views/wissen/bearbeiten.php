<?php

/**
 * Bearbeiten und neu Schreiben (nur Redaktion). Der Text ist Markdown -- wie in den Dateien des Imports.
 * Neue Artikel: oben das Hochladen einer Datei, gespeichert wird als Entwurf.
 *
 * @var $sammlung nexus\modules\wissen\services\Sammlung
 * @var $artikel nexus\modules\wissen\models\Artikel
 * @var $neu bool
 * @var $bilder int            Bilder in einer hochgeladenen Datei (nicht uebernommen)
 * @var $herkunft string|null  Name der hochgeladenen Datei
 * @var $themen array<string, array{name: string, symbol: string}>
 * @var $schlagworte string
 * @var $suchwoerter string
 * @var $fehler string|null
 */

use humhub\helpers\Html;
use nexus\modules\wissen\services\Hochladen;
use nexus\modules\wissen\services\Texte;

$herkunft = $herkunft ?? '';
$ziel = $neu ? $sammlung->url('redaktion/anlegen') : $sammlung->url('speichern', ['a' => $artikel->slug]);
$this->title = $neu ? Texte::t('neuer_artikel') : Texte::t('bearbeiten');
?>
<div class="panel panel-default nx-ws">
    <div class="panel-body">
        <div class="nx-ws-pfad">
            <a href="<?= Html::encode($sammlung->url()) ?>"><?= Html::encode($sammlung->name()) ?></a>
            <?php if (!$neu) : ?>› <a href="<?= Html::encode($artikel->url($sammlung)) ?>"><?= Html::encode($artikel->titel) ?></a><?php endif; ?>
        </div>
        <h1 style="font-size:1.4rem"><?= Html::encode($this->title) ?></h1>
        <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>

        <?php if ($neu) : ?>
            <div class="nx-ws-formular">
                <?= Html::beginForm($sammlung->url('redaktion/hochladen'), 'post', ['enctype' => 'multipart/form-data']) ?>
                    <?= Html::hiddenInput('thema', $artikel->thema) ?>
                    <label for="nx-ws-datei"><i class="fa fa-upload"></i> <?= Html::encode(Texte::t('hochladen')) ?></label>
                    <p class="nx-ws-leise" style="margin:.2rem 0 .5rem"><?= Html::encode(Texte::t('hochladen_text')) ?></p>
                    <div class="nx-ws-knoepfe" style="margin:0">
                        <input id="nx-ws-datei" type="file" name="datei" required
                               accept="<?= Html::encode(implode(',', array_map(fn($e) => '.' . $e, Hochladen::ENDUNGEN))) ?>">
                        <button type="submit" class="btn btn-default"><?= Html::encode(Texte::t('hochladen')) ?></button>
                    </div>
                <?= Html::endForm() ?>
            </div>
            <?php if ($herkunft !== '') : ?>
                <div class="nx-ws-meldung"><?= Html::encode(sprintf(Texte::t('hochladen_aus'), $herkunft)) ?></div>
            <?php endif; ?>
            <?php if ($bilder > 0) : ?>
                <div class="nx-ws-hinweis"><i class="fa fa-picture-o"></i> <?= Html::encode(sprintf(Texte::t('bilder_hinweis'), $bilder)) ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <?= Html::beginForm($ziel, 'post') ?>
            <?php if ($neu) : ?><?= Html::hiddenInput('herkunft', $herkunft) ?><?php endif; ?>
            <div class="nx-ws-felder">
                <div style="grid-column: 1 / -1"><label>Titel</label><input class="form-control" name="titel" value="<?= Html::encode($artikel->titel) ?>" required maxlength="255"></div>
                <div><label><?= Html::encode(Texte::t('thema')) ?></label>
                    <select class="form-control" name="thema">
                        <option value="" <?= $artikel->thema === '' ? 'selected' : '' ?>>–</option>
                        <?php foreach ($themen as $code => $thema) : ?>
                            <option value="<?= Html::encode($code) ?>" <?= (string)$code === $artikel->thema ? 'selected' : '' ?>><?= Html::encode($thema['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label><?= Html::encode(Texte::t('geprueft')) ?></label><input class="form-control" type="date" name="pruefdatum" value="<?= Html::encode((string)$artikel->pruefdatum) ?>"></div>
                <div><label><?= Html::encode(Texte::t('naechste_pruefung')) ?></label><input class="form-control" type="date" name="wiedervorlage" value="<?= Html::encode((string)$artikel->wiedervorlage) ?>"></div>
                <div style="grid-column: 1 / -1"><label>Schlagworte (sichtbar, mit Komma getrennt<?= $neu ? ' – leer lassen: die KI schlägt welche vor' : '' ?>)</label><input class="form-control" name="schlagworte" value="<?= Html::encode($schlagworte) ?>"></div>
                <div style="grid-column: 1 / -1"><label>Suchwörter (Alltagswörter und andere Bezeichnungen, nur für die Suche)</label><input class="form-control" name="suchwoerter" value="<?= Html::encode($suchwoerter) ?>"></div>
            </div>
            <label>Text (Markdown: ## Überschrift, - Aufzählung, **fett**, [Linktext](https://…))</label>
            <textarea class="form-control nx-ws-editor" name="inhalt" required><?= Html::encode($artikel->inhalt) ?></textarea>
            <?php if (!$neu) : ?>
                <div class="nx-ws-felder" style="margin-top:.75rem">
                    <div style="grid-column: 1 / -1"><label>Was wurde geändert? (für die Versionsgeschichte)</label><input class="form-control" name="grund" maxlength="255" placeholder="z. B. Angabe nach Vorschlag von … korrigiert"></div>
                </div>
            <?php endif; ?>
            <div class="nx-ws-knoepfe">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= Html::encode($neu ? Texte::t('als_entwurf_speichern') : Texte::t('speichern')) ?></button>
                <a class="btn btn-light" href="<?= Html::encode($neu ? $sammlung->url() : $artikel->url($sammlung)) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>
