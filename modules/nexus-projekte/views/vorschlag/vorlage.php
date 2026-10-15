<?php

/**
 * Beschlussvorlage: ein eigenstaendiges Dokument zum Weitergeben (z. B. an die DAO).
 * Im Browser druckbar bzw. als PDF zu speichern; mit $word als .doc-Datei (Word oeffnet HTML),
 * deshalb nur einfache Gestaltung mit Inline-Stilen.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $stimmen nexus\modules\projekte\models\Stimme[]
 * @var $fassungen array<int, nexus\modules\projekte\models\Datei[]>
 * @var $word bool
 */

use humhub\helpers\Html;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\services\Diff;
use nexus\modules\projekte\services\Entscheidung;
use nexus\modules\projekte\services\Texte;

$projekt = $karte->projekt;
$regel = Entscheidung::regelFuer($karte);
$optionen = Entscheidung::optionen($regel);
$text = fn(?string $t) => nl2br(Html::encode((string)$t), false);
$diff = fn(?string $a, ?string $b) => str_replace(
    ['<del>', '<ins>'],
    ['<del style="color:#b00020;background:#fde7ea">', '<ins style="color:#0a6b2d;background:#e3f6e8;text-decoration:underline">'],
    Diff::html($a, $b)
);
$zeilen = [
    Texte::t('projekte') => $kreis->displayName . ' – ' . $projekt->titel,
    Texte::t('vorschlag') => $karte->titel,
    Texte::t('eingebracht_von') => $karte->verantwortlich?->displayName ?? '–',
    Texte::t('regel') => Texte::t('regel_' . $regel),
];
if ($karte->ergebnis) {
    $zeilen[Texte::t('ergebnis')] = Texte::t('ergebnis_' . $karte->ergebnis)
        . ($karte->ergebnis_text ? ' (' . $karte->ergebnis_text . ')' : '')
        . ($karte->ergebnis_am ? ' – ' . Texte::datum($karte->ergebnis_am) : '');
} else {
    $a = Entscheidung::auswertung($karte);
    $zeilen[Texte::t('ergebnis')] = sprintf(Texte::t('stand_jetzt'), $a['text']);
}
if ($karte->runde > 1) {
    $zeilen[Texte::t('stellungnahmen')] = sprintf(Texte::t('runde_nr'), $karte->runde);
}
?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Texte::sprache()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode(Texte::t('beschlussvorlage') . ': ' . $karte->titel) ?></title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; color: #1b1b1b; max-width: 46rem; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        h1 { font-size: 1.6rem; margin: 0 0 .2rem; } h2 { font-size: 1.15rem; margin: 1.6rem 0 .4rem; border-bottom: 1px solid #ccc; padding-bottom: .2rem; }
        table { border-collapse: collapse; width: 100%; } td { vertical-align: top; padding: .25rem .5rem .25rem 0; }
        td:first-child { font-weight: bold; white-space: nowrap; width: 1%; }
        .stelle { font-weight: bold; margin-top: 1rem; } .kasten { border: 1px solid #ccc; padding: .5rem .7rem; margin: .3rem 0; }
        .leise { color: #666; font-size: .9rem; } .knopf { margin: 1rem 0; }
        @media print { .knopf { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
<?php if (!$word) : ?>
    <div class="knopf"><button type="button" onclick="window.print()"><?= Html::encode(Texte::t('drucken')) ?></button></div>
<?php endif; ?>
<div class="leise">N.E.X.U.S. · <?= Html::encode($kreis->displayName) ?></div>
<h1><?= Html::encode(Texte::t('beschlussvorlage')) ?>: <?= Html::encode($karte->titel) ?></h1>
<?php if ($projekt->weitergabe) : ?><div class="leise"><?= Html::encode(Texte::t('weitergabe')) ?>: <?= Html::encode($projekt->weitergabe) ?></div><?php endif; ?>

<table style="margin-top:1rem">
    <?php foreach ($zeilen as $titel => $wert) : ?>
        <tr><td><?= Html::encode($titel) ?>:</td><td><?= Html::encode($wert) ?></td></tr>
    <?php endforeach; ?>
</table>

<h2><?= Html::encode(Texte::t('zusammenfassung')) ?></h2>
<p><?= $text($karte->zusammenfassung) ?></p>

<?php if ($karte->begruendung) : ?>
    <h2><?= Html::encode(Texte::t('begruendung')) ?></h2>
    <p><?= $text($karte->begruendung) ?></p>
<?php endif; ?>

<?php if ($karte->aenderungen !== []) : ?>
    <h2><?= Html::encode(Texte::t('textaenderungen')) ?></h2>
    <?php foreach ($karte->aenderungen as $a) : ?>
        <div class="stelle"><?= Html::encode($a->stelle) ?></div>
        <div class="kasten"><?= $diff($a->alt, $a->neu) ?></div>
        <table>
            <tr><td><?= Html::encode(Texte::t('bisher')) ?>:</td><td><?= $a->alt ? $text($a->alt) : '<em>' . Html::encode(Texte::t('bisher_leer')) . '</em>' ?></td></tr>
            <tr><td><?= Html::encode(Texte::t('kuenftig')) ?>:</td><td><?= $a->neu ? $text($a->neu) : '<em>' . Html::encode(Texte::t('kuenftig_leer')) . '</em>' ?></td></tr>
        </table>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($fassungen !== []) : ?>
    <?php $nr = array_key_first($fassungen); ?>
    <h2><?= Html::encode(Texte::t('dateien')) ?> – <?= Html::encode(sprintf(Texte::t('fassung'), $nr)) ?></h2>
    <ul>
        <?php foreach ($fassungen[$nr] as $d) : ?>
            <?php $info = $d->info(); ?>
            <li><?php if ($info) : ?><a href="<?= Html::encode(Projekt::voll($info['url'])) ?>"><?= Html::encode($info['name']) ?></a><?php else : ?><?= Html::encode(Texte::t('datei_fehlt')) ?><?php endif; ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($stimmen !== []) : ?>
    <h2><?= Html::encode(Texte::t('stellungnahmen')) ?></h2>
    <table>
        <?php foreach ($stimmen as $s) : ?>
            <tr>
                <td><?= Html::encode($s->autor ? $s->autor->displayName : '?') ?>:</td>
                <td><?= Html::encode(Texte::t($optionen[$s->art][0] ?? $s->art)) ?><?php if ($s->text) : ?> – <?= $text($s->text) ?><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<p class="leise" style="margin-top:2rem"><?= Html::encode(Projekt::voll($karte->url(true))) ?> · <?= Html::encode(Texte::datum(date('Y-m-d'))) ?></p>
</body>
</html>
