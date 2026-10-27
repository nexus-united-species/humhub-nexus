<?php

/**
 * Antworten unter einer Rueckmeldung (Stimme mit Text) und der Knopf "Antworten".
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $ich humhub\modules\user\models\User
 * @var $s nexus\modules\projekte\models\Stimme
 */

use humhub\helpers\Html;
use nexus\modules\projekte\models\Antwort;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;

$antworten = $s->antworten;
?>
<?php if ($antworten !== []) : ?>
    <div class="nx-pj-antworten">
        <?php foreach ($antworten as $a) : ?>
            <div class="nx-pj-antwort">
                <strong><?= Html::encode($a->autor ? $a->autor->displayName : '?') ?>:</strong>
                <?= Html::encode($a->text) ?>
                <span class="nx-pj-leise"> · <?= Html::encode(Texte::datum($a->created_at, 'php:d.m. H:i')) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php if (Rechte::darfAntworten($karte, $ich)) : ?>
    <div class="nx-pj-antwort-knopf">
        <button type="button" class="btn btn-xs btn-default" data-nx-pj-oeffne="nx-pj-antwort-<?= (int)$s->id ?>"><i class="fa fa-reply"></i> <?= Html::encode(Texte::t('antworten')) ?></button>
    </div>
    <div id="nx-pj-antwort-<?= (int)$s->id ?>" hidden style="margin:.4rem 0 .6rem">
        <?= Html::beginForm($kreis->createUrl('/nexus-projekte/projekt/antwort', ['k' => $karte->id, 's' => $s->id]), 'post') ?>
            <label class="nx-pj-label" for="nx-pj-antwort-text-<?= (int)$s->id ?>"><?= Html::encode(Texte::t('antwort_label')) ?></label>
            <textarea id="nx-pj-antwort-text-<?= (int)$s->id ?>" class="form-control" name="text" rows="3" required minlength="<?= Antwort::MIN_ZEICHEN ?>" maxlength="<?= Antwort::MAX_ZEICHEN ?>"></textarea>
            <div class="nx-pj-knoepfe"><button type="submit" class="btn btn-primary btn-sm"><?= Html::encode(Texte::t('absenden')) ?></button></div>
        <?= Html::endForm() ?>
    </div>
<?php endif; ?>
