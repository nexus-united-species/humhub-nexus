<?php

/**
 * Eine Kostenkachel mit Ampel.
 *
 * @var $k nexus\modules\finanzen\models\Kachel
 * @var $stand nexus\modules\finanzen\services\Stand
 * @var $monat string
 */

use humhub\helpers\Html;
use nexus\modules\finanzen\services\Geld;
use nexus\modules\finanzen\services\Texte;

$farbe = $stand->farbe($k, $monat);
$gedeckt = min((int)$k->betrag, $stand->gedecktAnzeige($k, $monat));
$uebertrag = $k->istLaufend() ? $stand->uebertrag($k, $monat) : 0;
$vorgestreckt = $stand->vorgestreckt($k, $monat);
$bezahlt = $stand->bezahlt($k, $monat);
?>
<div class="nx-fin-kachel nx-fin-<?= $farbe ?>">
    <div class="nx-fin-kopf">
        <span class="nx-fin-punkt" aria-hidden="true"></span>
        <strong><?= Html::encode($k->titel) ?></strong>
        <?php if ($bezahlt !== null) : ?>
            <?php /* Der tatsaechlich bezahlte Betrag (Spenden + vorgestreckt) steht vorn; weicht er vom Plan ab, steht der Plan klein dahinter */ ?>
            <span class="nx-fin-betrag"><?= Html::encode(Geld::zeigen($bezahlt['betrag'])) ?> <small><?= Html::encode(Texte::t('bezahlt_klein')) ?><?= $bezahlt['betrag'] !== (int)$k->betrag ? ' · ' . Html::encode(sprintf(Texte::t('plan_klein'), Geld::zeigen((int)$k->betrag))) : '' ?></small></span>
        <?php else : ?>
            <span class="nx-fin-betrag"><?= Html::encode(Geld::zeigen((int)$k->betrag)) ?> <small><?= Html::encode(Texte::t($k->istLaufend() ? 'je_monat' : 'einmalig')) ?></small></span>
        <?php endif; ?>
    </div>
    <?php if ($k->beschreibung) : ?><div class="nx-fin-leise"><?= Html::encode($k->beschreibung) ?></div><?php endif; ?>
    <div class="nx-fin-balken" role="img" aria-label="<?= Html::encode(Texte::t('farbe_' . $farbe)) ?>"><span style="width:<?= $farbe === 'grau' ? 0 : $stand->prozent($k, $monat) ?>%"></span></div>
    <div class="nx-fin-zeile">
        <?php /* Ist schon etwas bezahlt, heisst Gruen "bezahlt" statt "gedeckt" */ ?>
        <span class="nx-fin-status"><?= Html::encode(Texte::t($bezahlt !== null && in_array($farbe, ['gruen', 'gelb'], true) ? 'farbe_bezahlt_' . $farbe : 'farbe_' . $farbe)) ?></span>
        <?php if ($farbe !== 'grau') : ?>
            <span><?= Html::encode(sprintf(Texte::t('gedeckt_von'), Geld::zeigen($gedeckt), Geld::zeigen((int)$k->betrag))) ?></span>
        <?php endif; ?>
        <?php if ($uebertrag > 0 && $farbe !== 'grau') : ?><span class="nx-fin-leise"><?= Html::encode(sprintf(Texte::t('uebertrag'), Geld::zeigen($uebertrag))) ?></span><?php endif; ?>
        <?php /* "geschaetzter Verbrauch" & Co. nur bei Kacheln, die noch nicht bezahlt sind -- bei bezahlten steht der echte Betrag */ ?>
        <?php if ($bezahlt === null) : ?><span class="nx-fin-marke"><?= Html::encode(Texte::t('einordnung_' . $k->einordnung)) ?></span><?php endif; ?>
        <?php if ($k->zusatz) : ?><span class="nx-fin-marke nx-fin-zusatz"><?= Html::encode(Texte::t('zusatz_' . $k->zusatz)) ?></span><?php endif; ?>
    </div>
    <?php if ($bezahlt !== null) : ?>
        <?php $ausSpenden = $bezahlt['betrag'] - ($vorgestreckt['betrag'] ?? 0); ?>
        <div class="nx-fin-vorgestreckt-zeile">
            <?= Html::encode(sprintf(Texte::t($k->istLaufend() ? 'bezahlt_im_monat' : 'bezahlt_bisher'), Geld::zeigen($bezahlt['betrag'], true), Geld::monatName($monat))) ?>
            <?php if ($vorgestreckt === null) : ?>
                – <?= Html::encode(Texte::t('nur_spenden')) ?>
            <?php elseif ($ausSpenden <= 0) : ?>
                – <?= Html::encode(sprintf(Texte::t('vorgestreckt_von'), implode(', ', $vorgestreckt['von']))) ?>
            <?php else : ?>
                – <?= Html::encode(sprintf(Texte::t('davon_spenden'), Geld::zeigen($ausSpenden, true))) ?>
                · <?= Html::encode(sprintf(Texte::t('davon_vorgestreckt'), Geld::zeigen($vorgestreckt['betrag'], true), implode(', ', $vorgestreckt['von']))) ?>
            <?php endif; ?>
            <?php if ($bezahlt['geschaetzt']) : ?><span class="nx-fin-marke"><?= Html::encode(Texte::t('geschaetzt')) ?></span><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
