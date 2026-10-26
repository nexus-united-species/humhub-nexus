<?php

/**
 * Oeffentliche Leseseite eines Wissensartikels (ohne Anmeldung). Rahmen und Stil: nexus-teilen.
 * $html ist mit HtmlPurifier gereinigt (Darstellung::oeffentlich), ohne Verweise auf andere Artikel.
 *
 * @var $t array          Texte des Moduls nexus-teilen
 * @var $artikel nexus\modules\wissen\models\Artikel
 * @var $sammlungName string
 * @var $html string
 * @var $datum string
 * @var $imPortal string|null
 */

use humhub\helpers\Html;

$zeit = strtotime($datum . ' UTC');
?>
<article class="karte">
    <div class="herkunft">
        <?= Html::encode($t['aus_gemeinschaft']) ?> · <?= Html::encode($sammlungName) ?>
        <?php if ($zeit) : ?> · <time datetime="<?= date('Y-m-d', $zeit) ?>"><?= date('d.m.Y', $zeit) ?></time><?php endif; ?>
    </div>
    <h1 class="titel"><?= Html::encode($artikel->titel) ?></h1>
    <div class="inhalt"><?= $html ?></div>
</article>
<?= $this->render('@nexus-teilen/views/lesen/_fuss', ['t' => $t, 'imPortal' => $imPortal]) ?>
