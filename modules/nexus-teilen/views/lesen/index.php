<?php

/**
 * Ein freigegebener Beitrag / eine freigegebene Wiki-Seite. $html ist fertig auf dem Server
 * gerendert (HumHubs RichTextToHtmlConverter: ohne Skripte und Ereignis-Attribute), die
 * Datei-Adressen sind schon auf die oeffentliche Datei-Adresse umgebogen.
 *
 * @var $t array
 * @var $sprache string
 * @var $fassung array{titel: string, text: string, sprache: ?string, uebersetzt: bool}
 * @var $original string|null
 * @var $html string
 * @var $anhaenge array
 * @var $datum string
 * @var $schluessel string
 * @var $imPortal string|null
 */

use humhub\helpers\Html;
use nexus\modules\teilen\Module;
use yii\helpers\Url;

$namen = ['de' => 'Deutsch', 'en' => 'English', 'es' => 'Español'];
$zeit = strtotime($datum . ' UTC');
$sprachLink = fn(string $code) => Url::to(['/nexus-teilen/lesen/index', 't' => $schluessel, 'sprache' => $code]);
?>
<article class="karte">
    <div class="herkunft">
        <?= Html::encode($t['aus_gemeinschaft']) ?>
        <?php if ($zeit) : ?> · <time datetime="<?= date('Y-m-d', $zeit) ?>"><?= date('d.m.Y', $zeit) ?></time><?php endif; ?>
    </div>

    <nav class="sprachen" aria-label="Sprache / Language / Idioma">
        <?php foreach (Module::SPRACHEN as $code) : ?>
            <?php if ($code === $sprache) : ?>
                <strong><?= Html::encode($namen[$code]) ?></strong>
            <?php else : ?>
                <a href="<?= Html::encode($sprachLink($code)) ?>" hreflang="<?= $code ?>"><?= Html::encode($namen[$code]) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <?php if ($fassung['uebersetzt'] && $original !== null) : ?>
        <p class="hinweis-uebersetzt">
            <?= Html::encode(sprintf($t['automatisch'], $t['sprachname'][$original] ?? $original)) ?>
            <a href="<?= Html::encode($sprachLink($original)) ?>"><?= Html::encode($t['original']) ?></a>
        </p>
    <?php endif; ?>

    <?php if ($fassung['titel'] !== '') : ?>
        <h1 class="titel"><?= Html::encode($fassung['titel']) ?></h1>
    <?php endif; ?>

    <div class="inhalt"><?= $html ?></div>

    <?php if ($anhaenge !== []) : ?>
        <section class="anhaenge">
            <h2><?= Html::encode($t['anhaenge']) ?></h2>
            <?php foreach ($anhaenge as $a) : ?>
                <div class="medium">
                    <?php if ($a['art'] === 'bild') : ?>
                        <img src="<?= Html::encode($a['url']) ?>" alt="<?= Html::encode($a['name']) ?>" loading="lazy">
                    <?php elseif ($a['art'] === 'video') : ?>
                        <video src="<?= Html::encode($a['url']) ?>" controls preload="metadata" playsinline
                               <?= $a['standbild'] ? 'poster="' . Html::encode($a['standbild']) . '"' : '' ?>
                               class="<?= $a['rund'] ? 'rund' : '' ?>"></video>
                    <?php else : ?>
                        📎 <a href="<?= Html::encode($a['url']) ?>" target="_blank" rel="noopener"><?= Html::encode($a['name']) ?></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</article>
<p class="mehr"><a href="<?= Html::encode(Url::to(['/nexus-teilen/lesen/schaufenster', 'sprache' => $sprache])) ?>"><?= Html::encode($t['sf_mehr']) ?> →</a></p>
<?= $this->render('_fuss', ['t' => $t, 'imPortal' => $imPortal]) ?>
