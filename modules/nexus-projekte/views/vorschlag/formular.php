<?php

/**
 * Vorschlag einbringen oder ueberarbeiten: Zusammenfassung, Begruendung, Textaenderungen, Dateien.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $projekt nexus\modules\projekte\models\Projekt
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $aenderungen array<int, array{stelle: string, alt: string, neu: string}>
 * @var $fassungen array<int, nexus\modules\projekte\models\Datei[]>
 * @var $dateienMoeglich bool
 * @var $fehler string|null
 */

use humhub\helpers\Html;
use humhub\modules\user\widgets\UserPickerField;
use nexus\modules\projekte\models\Aenderung;
use nexus\modules\projekte\models\Datei;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Hilfe;
use nexus\modules\projekte\services\Texte;

$neu = $karte->isNewRecord;
$links = implode("\n", array_map(fn($l) => $l['titel'] === $l['url'] ? $l['url'] : $l['titel'] . ' | ' . $l['url'], $karte->linkListe()));
$zeilen = $aenderungen === [] ? [['stelle' => '', 'alt' => '', 'neu' => '']] : $aenderungen;
$schritte = $neu ? '' : implode("
", array_map(fn($s) => $s->text, $karte->schritte));
$this->title = Texte::t($neu ? 'neuer_vorschlag' : 'vorschlag_bearbeiten');

$zeile = function (array $a) {
    return '<fieldset class="nx-pj-aenderung-feld">'
        . '<button type="button" class="btn btn-sm btn-light nx-pj-entfernen" data-nx-pj-entfernen title="' . Html::encode(Texte::t('aenderung_entfernen')) . '"><i class="fa fa-times"></i></button>'
        . '<label>' . Html::encode(Texte::t('stelle')) . '</label>'
        . '<input class="form-control" name="aenderung_stelle[]" maxlength="255" value="' . Html::encode($a['stelle']) . '" placeholder="' . Html::encode(Texte::t('stelle_hilfe')) . '">'
        . '<div class="nx-pj-zweispaltig"><div><label>' . Html::encode(Texte::t('bisher')) . '</label>'
        . '<textarea class="form-control" name="aenderung_alt[]" rows="4" maxlength="' . Aenderung::MAX_TEXT . '">' . Html::encode($a['alt']) . '</textarea></div>'
        . '<div><label>' . Html::encode(Texte::t('kuenftig')) . '</label>'
        . '<textarea class="form-control" name="aenderung_neu[]" rows="4" maxlength="' . Aenderung::MAX_TEXT . '">' . Html::encode($a['neu']) . '</textarea></div></div>'
        . '</fieldset>';
};
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <div class="nx-pj-leise"><a href="<?= Html::encode($projekt->url()) ?>"><?= Html::encode($projekt->titel) ?></a></div>
        <h1 style="font-size:1.4rem"><i class="fa fa-lightbulb-o"></i> <?= Html::encode($this->title) ?></h1>
        <div class="nx-pj-hinweis"><?= Html::encode(Texte::t('vorschlag_regeln')) ?></div>
        <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>

        <?= Html::beginForm($kreis->createUrl('/nexus-projekte/vorschlag/speichern', ['p' => $projekt->id] + ($neu ? [] : ['k' => $karte->id])), 'post', ['enctype' => 'multipart/form-data']) ?>
            <div class="nx-pj-felder">
                <div class="nx-pj-ganz"><label for="nx-pj-titel"><?= Html::encode(Texte::t('titel')) ?></label>
                    <input id="nx-pj-titel" class="form-control" name="titel" value="<?= Html::encode($karte->titel) ?>" required maxlength="190" placeholder="<?= Html::encode(Texte::t('vorschlag_titel_hilfe')) ?>"></div>
                <div class="nx-pj-ganz"><label for="nx-pj-zusammenfassung"><?= Html::encode(Texte::t('zusammenfassung')) ?></label><?= Hilfe::info('zusammenfassung') ?>
                    <textarea id="nx-pj-zusammenfassung" class="form-control" name="zusammenfassung" rows="4" required maxlength="10000"><?= Html::encode((string)$karte->zusammenfassung) ?></textarea>
                    <span class="nx-pj-leise"><?= Html::encode(Texte::t('zusammenfassung_hilfe')) ?></span></div>
                <div class="nx-pj-ganz"><label for="nx-pj-begruendung"><?= Html::encode(Texte::t('begruendung')) ?></label><?= Hilfe::info('begruendung') ?>
                    <textarea id="nx-pj-begruendung" class="form-control" name="begruendung" rows="3" maxlength="10000"><?= Html::encode((string)$karte->begruendung) ?></textarea>
                    <span class="nx-pj-leise"><?= Html::encode(Texte::t('begruendung_hilfe')) ?></span></div>
                <?php if (Rechte::darfLeiten($projekt, Yii::$app->user->getIdentity())) : ?>
                    <div><label><?= Html::encode(Texte::t('verantwortlich')) ?></label><?= Hilfe::info('verantwortlich_vorschlag') ?>
                        <?= UserPickerField::widget(['name' => 'verantwortlich', 'selection' => $karte->verantwortlich ? [$karte->verantwortlich] : [],
                            'url' => $kreis->createUrl('/space/membership/search'), 'maxSelection' => 1, 'placeholder' => Texte::t('personen')]) ?></div>
                <?php endif; ?>
                <div><label for="nx-pj-frist"><?= Html::encode(Texte::t('frist')) ?></label><?= Hilfe::info('frist') ?>
                    <input id="nx-pj-frist" class="form-control" type="date" name="frist" value="<?= Html::encode((string)$karte->frist) ?>"></div>
                <div class="nx-pj-ganz"><label for="nx-pj-schritte"><?= Html::encode(Texte::t('teilschritte')) ?></label><?= Hilfe::info('teilschritte') ?>
                    <textarea id="nx-pj-schritte" class="form-control" name="schritte" rows="3"><?= Html::encode($schritte) ?></textarea>
                    <span class="nx-pj-leise"><?= Html::encode(Texte::t('schritte_hilfe')) ?></span></div>
            </div>

            <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t('textaenderungen')) ?><?= Hilfe::info('textaenderungen') ?></h2>
            <div class="nx-pj-leise" style="margin-bottom:.5rem"><?= Html::encode(Texte::t('textaenderungen_hilfe')) ?></div>
            <div id="nx-pj-aenderungen" data-max="<?= Aenderung::MAX ?>">
                <?php foreach ($zeilen as $a) : ?><?= $zeile($a) ?><?php endforeach; ?>
            </div>
            <template id="nx-pj-aenderung-vorlage"><?= $zeile(['stelle' => '', 'alt' => '', 'neu' => '']) ?></template>
            <button type="button" class="btn btn-sm btn-default" data-nx-pj-weitere="nx-pj-aenderungen"><i class="fa fa-plus"></i> <?= Html::encode(Texte::t('weitere_aenderung')) ?></button>

            <?php if ($dateienMoeglich) : ?>
                <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t('dateien')) ?><?= Hilfe::info('dateien') ?></h2>
                <div class="nx-pj-leise" style="margin-bottom:.5rem"><?= Html::encode(Texte::t('dateien_hilfe')) ?></div>
                <?php if ($fassungen !== []) : ?>
                    <?= $this->render('/projekt/_dateien', ['fassungen' => $fassungen, 'kompakt' => true, 'karte' => $karte, 'ich' => Yii::$app->user->getIdentity(),
                        'url' => fn(string $aktion, array $p = []) => $kreis->createUrl('/nexus-projekte/projekt/' . $aktion, ['k' => $karte->id] + $p),
                        'lesen' => fn($d) => $kreis->createUrl('/nexus-projekte/projekt/datei-lesen', ['k' => $karte->id, 'd' => $d->id])]) ?>
                <?php endif; ?>
                <div class="nx-pj-felder">
                    <div><label for="nx-pj-dateien"><?= Html::encode(Texte::t($fassungen === [] ? 'dateien' : 'neue_fassung')) ?></label>
                        <input id="nx-pj-dateien" type="file" name="dateien[]" multiple class="form-control" data-max="<?= Datei::MAX_JE_FASSUNG ?>"></div>
                    <div><label for="nx-pj-fassung-hinweis"><?= Html::encode(Texte::t('fassung_hinweis')) ?></label>
                        <input id="nx-pj-fassung-hinweis" class="form-control" name="fassung_hinweis" maxlength="255"></div>
                </div>
                <div class="nx-pj-leise"><?= Html::encode(Texte::t('dateien_im_kreis')) ?></div>
            <?php endif; ?>

            <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t('unterlagen')) ?><?= Hilfe::info('unterlagen') ?></h2>
            <textarea class="form-control" name="links" rows="2" placeholder="Text | https://…" aria-label="<?= Html::encode(Texte::t('unterlagen')) ?>"><?= Html::encode($links) ?></textarea>
            <span class="nx-pj-leise"><?= Html::encode(Texte::t('links_hilfe')) ?></span>

            <div class="nx-pj-knoepfe">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= Html::encode(Texte::t('speichern')) ?></button>
                <a class="btn btn-light" href="<?= Html::encode($neu ? $projekt->url() : $karte->url()) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
            </div>
        <?= Html::endForm() ?>
        <?= implode('', $this->params['nxPjLoeschFormulare'] ?? []) ?>

        <?php if (!$neu && Rechte::darfZurueckziehen($karte, Yii::$app->user->getIdentity())) : ?>
            <hr>
            <?= Html::beginForm($kreis->createUrl('/nexus-projekte/vorschlag/zurueckziehen', ['k' => $karte->id]), 'post', ['data-nx-pj-frage' => Texte::t('zurueckziehen_frage')]) ?>
                <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-undo"></i> <?= Html::encode(Texte::t('zurueckziehen')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>
