<?php

/**
 * Bearbeiten (nur Admins). Der Text ist Markdown -- wie in den Dateien der Redaktion.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $artikel nexus\modules\gesundheit\models\Artikel
 * @var $schlagworte string
 * @var $suchwoerter string
 * @var $fehler string|null
 */

use humhub\helpers\Html;
use nexus\modules\gesundheit\models\Artikel;
use nexus\modules\gesundheit\services\Texte;

$url = fn(string $route, array $p = []) => $kreis->createUrl('/nexus-gesundheit/wissen/' . $route, $p);
?>
<div class="panel panel-default nx-gw">
    <div class="panel-body">
        <div class="nx-gw-pfad">
            <a href="<?= Html::encode($url('index')) ?>"><?= Html::encode(Texte::t('zurueck')) ?></a> ›
            <a href="<?= Html::encode($artikel->url($kreis)) ?>"><?= Html::encode($artikel->titel) ?></a>
        </div>
        <h1 style="font-size:1.4rem"><?= Html::encode(Texte::t('bearbeiten')) ?></h1>
        <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>

        <?= Html::beginForm($url('speichern', ['a' => $artikel->slug]), 'post') ?>
            <div class="nx-gw-felder">
                <div style="grid-column: 1 / -1"><label>Titel</label><input class="form-control" name="titel" value="<?= Html::encode($artikel->titel) ?>" required maxlength="255"></div>
                <div><label>Thema</label>
                    <select class="form-control" name="thema">
                        <?php foreach (array_keys(Artikel::THEMEN) as $code) : ?>
                            <option value="<?= Html::encode($code) ?>" <?= $code === $artikel->thema ? 'selected' : '' ?>><?= Html::encode(Texte::thema($code)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label>Geprüft am</label><input class="form-control" type="date" name="pruefdatum" value="<?= Html::encode((string)$artikel->pruefdatum) ?>"></div>
                <div><label>Nächste Prüfung</label><input class="form-control" type="date" name="wiedervorlage" value="<?= Html::encode((string)$artikel->wiedervorlage) ?>"></div>
                <div style="grid-column: 1 / -1"><label>Schlagworte (sichtbar, mit Komma getrennt)</label><input class="form-control" name="schlagworte" value="<?= Html::encode($schlagworte) ?>"></div>
                <div style="grid-column: 1 / -1"><label>Suchwörter (Alltagswörter und andere Bezeichnungen, nur für die Suche)</label><input class="form-control" name="suchwoerter" value="<?= Html::encode($suchwoerter) ?>"></div>
            </div>
            <label>Text (Markdown: ## Überschrift, - Aufzählung, [Linktext](https://…))</label>
            <textarea class="form-control nx-gw-editor" name="inhalt" required><?= Html::encode($artikel->inhalt) ?></textarea>
            <div class="nx-gw-felder" style="margin-top:.75rem">
                <div style="grid-column: 1 / -1"><label>Was wurde geändert? (für die Versionsgeschichte)</label><input class="form-control" name="grund" maxlength="255" placeholder="z. B. Dosierung nach Vorschlag von … korrigiert"></div>
            </div>
            <div class="nx-gw-knoepfe">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Speichern</button>
                <a class="btn btn-light" href="<?= Html::encode($artikel->url($kreis)) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
            </div>
        <?= Html::endForm() ?>
    </div>
</div>
