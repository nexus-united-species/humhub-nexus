<?php

/**
 * Kategorien einer Sammlung pflegen (nur Redaktion): anlegen, umbenennen, Symbol, Reihenfolge,
 * leere loeschen. Umbenennen aendert den Namen in der eigenen Sprache, die anderen bleiben.
 *
 * @var $sammlung nexus\modules\wissen\services\Sammlung
 * @var $themen nexus\modules\wissen\models\Thema[]
 * @var $anzahl array<string, int> Artikel (mit Entwuerfen) je Kategorie-Code
 * @var $meldung string|null
 * @var $fehler string|null
 */

use humhub\helpers\Html;
use nexus\modules\wissen\services\Texte;
use nexus\modules\wissen\services\Themen;

$symbolAuswahl = function (string $gewaehlt): string {
    $html = '';
    foreach (Themen::SYMBOLE as $s) {
        $html .= '<option value="' . Html::encode($s) . '"' . ($s === $gewaehlt ? ' selected' : '') . '>' . Html::encode($s) . '</option>';
    }
    return $html;
};
$this->title = Texte::t('kategorien');
$letzte = count($themen) - 1;
?>
<div class="panel panel-default nx-ws">
    <div class="panel-body">
        <div class="nx-ws-pfad"><a href="<?= Html::encode($sammlung->url()) ?>"><?= Html::encode($sammlung->name()) ?></a></div>
        <h1 style="font-size:1.4rem"><?= Html::encode(Texte::t('kategorien')) ?></h1>
        <p class="nx-ws-leise"><?= Html::encode(Texte::t('kategorien_text')) ?></p>
        <?php if ($meldung) : ?><div class="nx-ws-meldung"><?= Html::encode($meldung) ?></div><?php endif; ?>
        <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>

        <?php if ($themen === []) : ?>
            <p><?= Html::encode(Texte::t('keine_kategorien')) ?></p>
        <?php endif; ?>
        <ul class="nx-ws-liste">
            <?php foreach ($themen as $i => $t) : ?>
                <?php $n = (int)($anzahl[$t->code] ?? 0); ?>
                <li class="nx-ws-kat">
                    <?= Html::beginForm($sammlung->url('redaktion/thema'), 'post', ['class' => 'nx-ws-kat-zeile']) ?>
                        <?= Html::hiddenInput('id', $t->id) ?>
                        <i class="fa fa-<?= Html::encode($t->symbol) ?> fa-fw"></i>
                        <input class="form-control" name="name" value="<?= Html::encode($t->anzeigeName()) ?>" maxlength="80" required aria-label="<?= Html::encode(Texte::t('feld_name')) ?>">
                        <select class="form-control nx-ws-kat-symbol" name="symbol" aria-label="<?= Html::encode(Texte::t('feld_symbol')) ?>"><?= $symbolAuswahl((string)$t->symbol) ?></select>
                        <span class="nx-ws-leise nx-ws-kat-zahl"><?= Html::encode(sprintf(Texte::t('artikel_im_thema'), $n)) ?></span>
                        <button type="submit" name="aktion" value="aendern" class="btn btn-sm btn-primary"><?= Html::encode(Texte::t('speichern')) ?></button>
                        <button type="submit" name="aktion" value="hoch" class="btn btn-sm btn-light" <?= $i === 0 ? 'disabled' : '' ?> title="<?= Html::encode(Texte::t('hoch')) ?>" formnovalidate><i class="fa fa-arrow-up"></i></button>
                        <button type="submit" name="aktion" value="runter" class="btn btn-sm btn-light" <?= $i === $letzte ? 'disabled' : '' ?> title="<?= Html::encode(Texte::t('runter')) ?>" formnovalidate><i class="fa fa-arrow-down"></i></button>
                        <?php if ($n === 0) : ?>
                            <button type="submit" name="aktion" value="loeschen" class="btn btn-sm btn-danger" formnovalidate
                                    data-nx-ws-bestaetigen="<?= Html::encode(Texte::t('loeschen_frage')) ?>"><?= Html::encode(Texte::t('loeschen')) ?></button>
                        <?php else : ?>
                            <?php /* Immer sichtbar, sonst wirkt der Knopf willkuerlich (Rueckmeldung aus dem Team) -- nur gesperrt */ ?>
                            <span title="<?= Html::encode(Texte::t('loeschen_gesperrt')) ?>">
                                <button type="button" class="btn btn-sm btn-danger" disabled aria-disabled="true"><?= Html::encode(Texte::t('loeschen')) ?></button>
                            </span>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-default" href="<?= Html::encode($sammlung->url('redaktion/neu', ['thema' => $t->code])) ?>"><i class="fa fa-plus"></i> <?= Html::encode(Texte::t('artikel_hier_neu')) ?></a>
                    <?= Html::endForm() ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2 style="font-size:1.15rem;margin-top:1.25rem"><?= Html::encode(Texte::t('neue_kategorie')) ?></h2>
        <?= Html::beginForm($sammlung->url('redaktion/thema'), 'post', ['class' => 'nx-ws-kat-zeile']) ?>
            <?= Html::hiddenInput('aktion', 'neu') ?>
            <input class="form-control" name="name" maxlength="80" required placeholder="<?= Html::encode(Texte::t('feld_name')) ?>" aria-label="<?= Html::encode(Texte::t('feld_name')) ?>">
            <select class="form-control nx-ws-kat-symbol" name="symbol" aria-label="<?= Html::encode(Texte::t('feld_symbol')) ?>"><?= $symbolAuswahl(Themen::VORGABE_SYMBOL) ?></select>
            <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?= Html::encode(Texte::t('anlegen')) ?></button>
        <?= Html::endForm() ?>

        <p style="margin-top:1.25rem"><a href="<?= Html::encode($sammlung->url()) ?>">← <?= Html::encode($sammlung->name()) ?></a></p>
    </div>
</div>
