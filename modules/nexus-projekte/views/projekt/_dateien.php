<?php

/**
 * Dateien eines Vorschlags nach Fassung: die neueste offen, aeltere zum Aufklappen. Bilder mit Vorschau.
 *
 * @var $fassungen array<int, nexus\modules\projekte\models\Datei[]>
 * @var $kompakt bool  ohne Bildvorschau (im Formular)
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$kompakt = $kompakt ?? false;
$liste = function (array $dateien) use ($kompakt) {
    $html = '<div class="nx-pj-dateien">';
    foreach ($dateien as $d) {
        $info = $d->info();
        if ($info === null) {
            $html .= '<span class="nx-pj-leise"><i class="fa fa-ban"></i> ' . Html::encode(Texte::t('datei_fehlt')) . '</span>';
            continue;
        }
        if ($info['bild'] && !$kompakt) {
            $html .= '<a class="nx-pj-bild" href="' . Html::encode($info['url']) . '" target="_blank" rel="noopener">'
                . '<img src="' . Html::encode($info['url']) . '" alt="' . Html::encode($info['name']) . '" loading="lazy">'
                . '<span>' . Html::encode($info['name']) . '</span></a>';
        } else {
            $html .= '<a href="' . Html::encode($info['url']) . '" target="_blank" rel="noopener"><i class="fa fa-file-o"></i> '
                . Html::encode($info['name']) . ' <span class="nx-pj-leise">(' . Yii::$app->formatter->asShortSize($info['groesse'], 1) . ')</span></a>';
        }
    }
    return $html . '</div>';
};
$kopf = function (int $nr, array $dateien) {
    $erste = $dateien[0];
    $teile = [sprintf(Texte::t('fassung'), $nr), Texte::datum($erste->created_at)];
    if ($erste->autor) {
        $teile[] = $erste->autor->displayName;
    }
    return Html::encode(implode(' · ', $teile)) . ($erste->hinweis ? ' – <em>' . Html::encode($erste->hinweis) . '</em>' : '');
};
$nummern = array_keys($fassungen);
$neueste = array_shift($nummern);
?>
<div class="nx-pj-fassung">
    <div class="nx-pj-leise"><?= $kopf($neueste, $fassungen[$neueste]) ?></div>
    <?= $liste($fassungen[$neueste]) ?>
</div>
<?php if ($nummern !== []) : ?>
    <details class="nx-pj-fassung">
        <summary class="nx-pj-leise"><?= Html::encode(Texte::t('aeltere_fassungen')) ?> (<?= count($nummern) ?>)</summary>
        <?php foreach ($nummern as $nr) : ?>
            <div class="nx-pj-leise" style="margin-top:.4rem"><?= $kopf($nr, $fassungen[$nr]) ?></div>
            <?= $liste($fassungen[$nr]) ?>
        <?php endforeach; ?>
    </details>
<?php endif; ?>
