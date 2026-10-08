<?php

/**
 * Oeffentliche Seite "Aus der N.E.X.U.S.-Gemeinschaft" (Josh, 03.10.2026): alle Inhalte, die ein
 * Admin ins Schaufenster gestellt hat -- anonym, ohne Kommentare, ohne Kreis.
 *
 * @var $t array
 * @var $sprache string
 * @var $seite int
 * @var $karten array
 * @var $aeltere bool
 * @var $karte string|null  Karte der Gemeinschaften (nur Seite 1)
 * @var $imPortal string|null
 */

use humhub\helpers\Html;
use nexus\modules\teilen\Module;
use yii\helpers\Url;

$namen = ['de' => 'Deutsch', 'en' => 'English', 'es' => 'Español'];
$seitenLink = fn(string $code, int $nr) => Url::to(['/nexus-teilen/lesen/schaufenster', 'sprache' => $code, 'seite' => $nr > 1 ? $nr : null]);
?>
<section class="nsf-kopf">
    <h1 class="titel"><?= Html::encode($t['sf_titel']) ?></h1>
    <p><?= Html::encode($t['sf_intro']) ?></p>
    <nav class="sprachen" aria-label="Sprache / Language / Idioma">
        <?php foreach (Module::SPRACHEN as $code) : ?>
            <?php if ($code === $sprache) : ?>
                <strong><?= Html::encode($namen[$code]) ?></strong>
            <?php else : ?>
                <a href="<?= Html::encode($seitenLink($code, $seite)) ?>" hreflang="<?= $code ?>"><?= Html::encode($namen[$code]) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</section>

<?php if ($karten === []) : ?>
    <article class="karte"><p><?= Html::encode($t['sf_leer']) ?></p></article>
<?php else : ?>
    <?= $this->render('_karten', ['t' => $t, 'karten' => $karten]) ?>
<?php endif; ?>

<?php if (!empty($karte)) : ?>
    <div class="nsf-karte-block"><?= $karte /* fertig aus nexus-karte, dort maskiert */ ?></div>
<?php endif; ?>

<?php if ($seite > 1 || $aeltere) : ?>
    <nav class="nsf-blaettern">
        <?php if ($seite > 1) : ?><a href="<?= Html::encode($seitenLink($sprache, $seite - 1)) ?>"><?= Html::encode($t['sf_neuere']) ?></a><?php endif; ?>
        <?php if ($aeltere) : ?><a href="<?= Html::encode($seitenLink($sprache, $seite + 1)) ?>"><?= Html::encode($t['sf_aeltere']) ?></a><?php endif; ?>
    </nav>
<?php endif; ?>

<?= $this->render('_fuss', ['t' => $t, 'imPortal' => $imPortal]) ?>
