<?php

/**
 * Einladung + Fusszeile. Fuer Gaeste ist dies oft die einzige Seite des Portals, die sie sehen --
 * deshalb gehoeren Impressum und Datenschutz dazu (Moduleinstellungen "fussLinks", "kontaktEmail").
 *
 * @var $t array
 * @var $imPortal string|null
 */

use humhub\helpers\Html;
use nexus\modules\teilen\Module;

$modul = Module::instanz();
?>
<section class="einladung">
    <?php if (!empty($imPortal)) : ?>
        <a class="knopf" href="<?= Html::encode($imPortal) ?>"><?= Html::encode($t['im_portal']) ?></a>
    <?php else : ?>
        <p><?= Html::encode($t['mitmachen']) ?></p>
        <a class="knopf" href="<?= Html::encode($modul->registrierenUrl()) ?>"><?= Html::encode($t['registrieren']) ?></a>
        <a class="knopf leise" href="<?= Html::encode($modul->anmeldenUrl()) ?>"><?= Html::encode($t['anmelden']) ?></a>
    <?php endif; ?>
</section>
<?php if ($t['fuss_links'] !== [] || $modul->kontaktEmail) : ?>
<footer class="fuss">
    <?= implode(' · ', array_map(
        static fn($text, $link) => Html::a(Html::encode($text), $link, ['target' => '_blank', 'rel' => 'noopener']),
        array_keys($t['fuss_links']),
        $t['fuss_links']
    )) ?>
    <?php if ($modul->kontaktEmail) : ?>
        <br><?= Html::encode($t['kontakt']) ?> <?= Html::mailto(Html::encode($modul->kontaktEmail), $modul->kontaktEmail) ?>
    <?php endif; ?>
</footer>
<?php endif; ?>
