<?php

/**
 * Neue Kachel, Schritt 1: Welche Art? Drei grosse Karten statt eines Auswahlfelds.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $projekt nexus\modules\projekte\models\Projekt
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$this->title = Texte::t('neue_karte');
$arten = [
    ['fa-check-square-o', 'art_aufgabe_titel', 'art_aufgabe_text', $kreis->createUrl('/nexus-projekte/projekt/karte', ['p' => $projekt->id, 'art' => 'aufgabe'])],
    ['fa-file-text-o', 'art_dokument_titel', 'art_dokument_text', $kreis->createUrl('/nexus-projekte/vorschlag/formular', ['p' => $projekt->id])],
    ['fa-picture-o', 'art_auswahl_titel', 'art_auswahl_text', $kreis->createUrl('/nexus-projekte/projekt/karte', ['p' => $projekt->id, 'art' => 'auswahl'])],
];
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <div class="nx-pj-leise"><a href="<?= Html::encode($projekt->url()) ?>"><?= Html::encode($projekt->titel) ?></a></div>
        <h1 style="font-size:1.4rem"><?= Html::encode(Texte::t('art_titel')) ?></h1>
        <div class="nx-pj-artwahl">
            <?php foreach ($arten as [$icon, $titel, $text, $adresse]) : ?>
                <a class="nx-pj-artkarte" href="<?= Html::encode($adresse) ?>">
                    <i class="fa <?= $icon ?>"></i>
                    <strong><?= Html::encode(Texte::t($titel)) ?></strong>
                    <span><?= Html::encode(Texte::t($text)) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="nx-pj-knoepfe">
            <a class="btn btn-light" href="<?= Html::encode($projekt->url()) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
        </div>
    </div>
</div>
