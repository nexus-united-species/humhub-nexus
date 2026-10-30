<?php

/**
 * Kachel anlegen oder bearbeiten.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $projekt nexus\modules\projekte\models\Projekt
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $darfZuweisen bool
 * @var $fehler string|null
 */

use humhub\helpers\Html;
use humhub\modules\user\widgets\UserPickerField;
use nexus\modules\projekte\services\Texte;

$neu = $karte->isNewRecord;
$links = implode("\n", array_map(fn($l) => $l['titel'] === $l['url'] ? $l['url'] : $l['titel'] . ' | ' . $l['url'], $karte->linkListe()));
$schritte = $neu ? '' : implode("\n", array_map(fn($s) => $s->text, $karte->schritte));
$suche = $kreis->createUrl('/space/membership/search');
$this->title = Texte::t($neu ? 'neue_karte' : 'karte_bearbeiten');
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <div class="nx-pj-leise"><a href="<?= Html::encode($projekt->url()) ?>"><?= Html::encode($projekt->titel) ?></a></div>
        <h1 style="font-size:1.4rem"><?= Html::encode($this->title) ?></h1>
        <?php if ($neu) : ?>
            <p class="nx-pj-leise"><?= Html::encode(sprintf(Texte::t('art_gewaehlt'), Texte::t($karte->istAuswahl() ? 'art_auswahl_titel' : 'art_aufgabe_titel'))) ?>
                · <a href="<?= Html::encode($kreis->createUrl('/nexus-projekte/projekt/karte', ['p' => $projekt->id])) ?>"><?= Html::encode(Texte::t('art_aendern')) ?></a></p>
        <?php endif; ?>
        <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>

        <?= Html::beginForm($kreis->createUrl('/nexus-projekte/projekt/karte-speichern', ['p' => $projekt->id] + ($neu ? [] : ['k' => $karte->id])), 'post', ['enctype' => 'multipart/form-data']) ?>
            <div class="nx-pj-felder">
                <div class="nx-pj-ganz"><label for="nx-pj-titel"><?= Html::encode(Texte::t('titel')) ?></label>
                    <input id="nx-pj-titel" class="form-control" name="titel" value="<?= Html::encode($karte->titel) ?>" required maxlength="190"></div>
                <div class="nx-pj-ganz"><label for="nx-pj-beschreibung"><?= Html::encode(Texte::t('beschreibung')) ?></label>
                    <textarea id="nx-pj-beschreibung" class="form-control" name="beschreibung" rows="3" maxlength="10000"><?= Html::encode((string)$karte->beschreibung) ?></textarea></div>
                <?php if ($darfZuweisen) : ?>
                    <?php if ($neu) : ?>
                        <?php /* Die Art steht schon fest (Auswahl vorher in karte_art.php) */ ?>
                        <input type="hidden" name="art" value="<?= $karte->istAuswahl() ? 'auswahl' : 'aufgabe' ?>">
                    <?php else : ?>
                    <div class="nx-pj-ganz"><label for="nx-pj-art"><?= Html::encode(Texte::t('auswahl_art')) ?></label>
                        <select id="nx-pj-art" name="art" class="form-control">
                            <option value="aufgabe"<?= $karte->istAuswahl() ? '' : ' selected' ?>><?= Html::encode(Texte::t('auswahl_art_aufgabe')) ?></option>
                            <option value="auswahl"<?= $karte->istAuswahl() ? ' selected' : '' ?>><?= Html::encode(Texte::t('auswahl_art_auswahl')) ?></option>
                        </select>
                        <span class="nx-pj-leise"><?= Html::encode(Texte::t('auswahl_art_hilfe')) ?></span></div>
                    <?php endif; ?>
                    <div><label><?= Html::encode(Texte::t('verantwortlich')) ?></label>
                        <?= UserPickerField::widget(['name' => 'verantwortlich', 'selection' => $karte->verantwortlich ? [$karte->verantwortlich] : [],
                            'url' => $suche, 'maxSelection' => 1, 'placeholder' => Texte::t('personen')]) ?></div>
                    <div><label><?= Html::encode(Texte::t('hilft_mit')) ?></label>
                        <?= UserPickerField::widget(['name' => 'helfer', 'selection' => $neu ? [] : $karte->helfer(),
                            'url' => $suche, 'maxSelection' => 20, 'placeholder' => Texte::t('personen')]) ?></div>
                <?php endif; ?>
                <div><label for="nx-pj-frist"><?= Html::encode(Texte::t('frist')) ?></label>
                    <input id="nx-pj-frist" class="form-control" type="date" name="frist" value="<?= Html::encode((string)$karte->frist) ?>"></div>
                <div class="nx-pj-ganz"><label for="nx-pj-schritte"><?= Html::encode(Texte::t('teilschritte')) ?></label>
                    <textarea id="nx-pj-schritte" class="form-control" name="schritte" rows="4" placeholder="<?= Html::encode(Texte::t('schritte_hilfe')) ?>"><?= Html::encode($schritte) ?></textarea>
                    <span class="nx-pj-leise"><?= Html::encode(Texte::t('schritte_hilfe')) ?></span></div>
                <div class="nx-pj-ganz"><label for="nx-pj-links"><?= Html::encode(Texte::t('unterlagen')) ?></label>
                    <textarea id="nx-pj-links" class="form-control" name="links" rows="3" placeholder="Text | https://…"><?= Html::encode($links) ?></textarea>
                    <span class="nx-pj-leise"><?= Html::encode(Texte::t('links_hilfe')) ?></span></div>
                <?php if (!$karte->istAuswahl()) : ?>
                    <div class="nx-pj-ganz"><label for="nx-pj-dateien"><?= Html::encode(Texte::t('dateien_anhaengen')) ?></label>
                        <?php if (\nexus\modules\projekte\services\Dateien::hochladbar($kreis)) : ?>
                            <input id="nx-pj-dateien" class="form-control" type="file" name="dateien[]" multiple>
                            <span class="nx-pj-leise"><?= Html::encode(Texte::t('dateien_anhaengen_hilfe')) ?></span>
                        <?php else : ?>
                            <span class="nx-pj-leise"><?= Html::encode(Texte::t('dateien_aus')) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="nx-pj-knoepfe">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= Html::encode(Texte::t('speichern')) ?></button>
                <a class="btn btn-light" href="<?= Html::encode($neu ? $projekt->url() : $karte->url()) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
            </div>
        <?= Html::endForm() ?>

        <?php if (!$neu && $darfZuweisen) : ?>
            <hr>
            <?= Html::beginForm($kreis->createUrl('/nexus-projekte/projekt/karte-loeschen', ['k' => $karte->id]), 'post', ['data-nx-pj-frage' => Texte::t('loeschen_frage')]) ?>
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> <?= Html::encode(Texte::t('loeschen')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>
