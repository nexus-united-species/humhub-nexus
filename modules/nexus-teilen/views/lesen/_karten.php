<?php

/**
 * Karten der Schaufenster-Beitraege -- gemeinsam fuer die oeffentliche Seite und die Gast-Startseite.
 * Eigene Klassen mit Vorsilbe "nsf-", damit sich das Portal-Design und diese Karten nicht stoeren.
 *
 * @var $t array
 * @var $karten array<int, array{titel: string, text: string, bild: ?string, datum: string, link: string}>
 */

use humhub\helpers\Html;

?>
<div class="nsf-karten">
    <?php foreach ($karten as $k) : ?>
        <?php $zeit = strtotime($k['datum'] . ' UTC'); ?>
        <a class="nsf-karte" href="<?= Html::encode($k['link']) ?>" data-pjax-prevent="1">
            <?php if ($k['bild'] !== null) : ?>
                <span class="nsf-bild<?= !empty($k['video']) ? ' nsf-video' : '' ?>" style="background-image:url('<?= Html::encode($k['bild']) ?>')" role="img" aria-label=""></span>
            <?php else : ?>
                <span class="nsf-bild nsf-ohne-bild" aria-hidden="true">🌱</span>
            <?php endif; ?>
            <span class="nsf-inhalt">
                <?php if ($zeit) : ?><span class="nsf-datum"><?= date('d.m.Y', $zeit) ?></span><?php endif; ?>
                <span class="nsf-titel"><?= Html::encode($k['titel']) ?></span>
                <?php if ($k['text'] !== '') : ?><span class="nsf-text"><?= Html::encode($k['text']) ?></span><?php endif; ?>
                <span class="nsf-weiter"><?= Html::encode($t['sf_weiterlesen']) ?> →</span>
            </span>
        </a>
    <?php endforeach; ?>
</div>
