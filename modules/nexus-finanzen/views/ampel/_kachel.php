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
$gedeckt = min((int)$k->betrag, $stand->gedeckt($k, $monat));
$uebertrag = $k->istLaufend() ? $stand->uebertrag($k, $monat) : 0;
?>
<div class="nx-fin-kachel nx-fin-<?= $farbe ?>">
    <div class="nx-fin-kopf">
        <span class="nx-fin-punkt" aria-hidden="true"></span>
        <strong><?= Html::encode($k->titel) ?></strong>
        <span class="nx-fin-betrag"><?= Html::encode(Geld::zeigen((int)$k->betrag)) ?> <small><?= Html::encode(Texte::t($k->istLaufend() ? 'je_monat' : 'einmalig')) ?></small></span>
    </div>
    <?php if ($k->beschreibung) : ?><div class="nx-fin-leise"><?= Html::encode($k->beschreibung) ?></div><?php endif; ?>
    <div class="nx-fin-balken" role="img" aria-label="<?= Html::encode(Texte::t('farbe_' . $farbe)) ?>"><span style="width:<?= $farbe === 'grau' ? 0 : $stand->prozent($k, $monat) ?>%"></span></div>
    <div class="nx-fin-zeile">
        <span class="nx-fin-status"><?= Html::encode(Texte::t('farbe_' . $farbe)) ?></span>
        <?php if ($farbe !== 'grau') : ?>
            <span><?= Html::encode(sprintf(Texte::t('gedeckt_von'), Geld::zeigen($gedeckt), Geld::zeigen((int)$k->betrag))) ?></span>
        <?php endif; ?>
        <?php if ($uebertrag > 0) : ?><span class="nx-fin-leise"><?= Html::encode(sprintf(Texte::t('uebertrag'), Geld::zeigen($uebertrag))) ?></span><?php endif; ?>
        <span class="nx-fin-marke"><?= Html::encode(Texte::t('einordnung_' . $k->einordnung)) ?></span>
        <?php if ($k->zusatz) : ?><span class="nx-fin-marke nx-fin-zusatz"><?= Html::encode(Texte::t('zusatz_' . $k->zusatz)) ?></span><?php endif; ?>
    </div>
</div>
