<?php

/**
 * Block "Aus der N.E.X.U.S.-Gemeinschaft" auf der Gast-Startseite des Portals (vor der
 * Registrierung, ueber der Karte). Bringt sein Aussehen selbst mit -- die Seite hat sonst nur
 * die Portal-Styles.
 *
 * @var $t array
 * @var $karten array
 * @var $alle string
 */

use humhub\helpers\Html;

?>
<section class="nsf-gast">
    <style>
        .nsf-gast { max-width: 980px; margin: 8px auto 28px; padding: 0 16px; }
        .nsf-gast h2 { font-size: 22px; font-weight: 700; text-align: center; margin: 0 0 6px; }
        .nsf-gast .nsf-gast-intro { text-align: center; color: #8a8f98; margin: 0 0 18px; }
        .nsf-gast .nsf-alle { text-align: center; margin-top: 14px; }
        .nsf-gast .nsf-alle a { color: #b8941f; font-weight: 600; }
        <?= $this->render('_karten_stil') ?>
    </style>
    <h2>🌱 <?= Html::encode($t['sf_titel']) ?></h2>
    <p class="nsf-gast-intro"><?= Html::encode($t['sf_intro']) ?></p>
    <?= $this->render('_karten', ['t' => $t, 'karten' => $karten]) ?>
    <p class="nsf-alle"><a href="<?= Html::encode($alle) ?>" data-pjax-prevent="1"><?= Html::encode($t['sf_alle']) ?> →</a></p>
</section>
