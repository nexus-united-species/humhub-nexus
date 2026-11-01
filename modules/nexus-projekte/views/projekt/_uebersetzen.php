<?php

/**
 * Knopf zum Uebersetzen: tauscht alle Texte mit data-nx-pj-tr im umgebenden Bereich aus (projekte.js).
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $lang bool  lange Datei: Wartehinweis statt Zaehler
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$lang = $lang ?? false;
?>
<div class="nx-pj-tr">
    <button type="button" class="btn btn-sm btn-default" data-nx-pj-tr-knopf
            data-url="<?= Html::encode($kreis->createUrl('/nexus-projekte/projekt/uebersetzen')) ?>"
            data-t-an="<?= Html::encode(Texte::t('tr_knopf')) ?>"
            data-t-aus="<?= Html::encode(Texte::t('tr_original')) ?>"
            data-t-laeuft="<?= Html::encode(Texte::t($lang ? 'tr_laeuft_lang' : 'tr_laeuft')) ?>"
            data-t-hinweis="<?= Html::encode(Texte::t('tr_hinweis')) ?>"
            data-t-fehler="<?= Html::encode(Texte::t('tr_fehler')) ?>"><i class="fa fa-language"></i> <span><?= Html::encode(Texte::t('tr_knopf')) ?></span></button>
    <span class="nx-pj-leise nx-pj-tr-hinweis" role="status" aria-live="polite" hidden></span>
</div>
