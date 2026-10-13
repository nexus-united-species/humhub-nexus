<?php

/**
 * @var $person humhub\modules\user\models\User
 * @var $titel string
 * @var $seite int
 * @var $daten array{eintraege: array, aeltere: bool}
 */

use humhub\helpers\Html;
use humhub\widgets\TimeAgo;
use nexus\modules\orientierung\services\Texte;

$url = fn(int $s) => $person->createUrl('/nexus-orientierung/beitraege/index', $s > 1 ? ['seite' => $s] : []);
?>
<div class="panel panel-default nx-or">
    <div class="panel-heading"><strong><?= Html::encode($titel) ?></strong></div>
    <div class="panel-body">
        <p class="nx-or-leise"><?= Html::encode(Texte::t('einleitung')) ?></p>

        <?php if ($daten['eintraege'] === []) : ?>
            <p><?= Html::encode(Texte::t('keine')) ?></p>
        <?php else : ?>
            <ol class="nx-or-liste">
                <?php foreach ($daten['eintraege'] as $e) : ?>
                    <?php $istKommentar = $e['art'] === 'kommentar'; ?>
                    <li class="nx-or-eintrag<?= $istKommentar ? ' nx-or-kommentar' : '' ?>">
                        <a href="<?= Html::encode($e['url']) ?>" class="nx-or-link">
                            <span class="nx-or-kopf">
                                <i class="fa fa-<?= $istKommentar ? 'comment-o' : 'file-text-o' ?>" aria-hidden="true"></i>
                                <strong><?= Html::encode(Texte::t($istKommentar ? 'kommentar' : 'beitrag')) ?></strong>
                                <?php if (!$istKommentar && $e['name'] !== '' && $e['name'] !== Texte::t('beitrag')) : ?>
                                    <span class="nx-or-leise">(<?= Html::encode($e['name']) ?>)</span>
                                <?php endif; ?>
                                <?php if ($e['ort'] !== '') : ?><span class="nx-or-ort"><?= Html::encode($e['ort']) ?></span><?php endif; ?>
                                <span class="nx-or-zeit"><?= TimeAgo::widget(['timestamp' => $e['datum']]) ?></span>
                            </span>
                            <?php if ($e['bezug'] !== '') : ?>
                                <span class="nx-or-bezug"><?= Html::encode(Texte::t('zu')) ?>: „<?= Html::encode($e['bezug']) ?>“</span>
                            <?php endif; ?>
                            <span class="nx-or-text"><?= Html::encode($e['text']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <div class="nx-or-blaettern">
            <?php if ($seite > 1) : ?>
                <a class="btn btn-default" href="<?= Html::encode($url($seite - 1)) ?>">← <?= Html::encode(Texte::t('neuere')) ?></a>
            <?php endif; ?>
            <?php if ($daten['aeltere']) : ?>
                <a class="btn btn-primary" href="<?= Html::encode($url($seite + 1)) ?>"><?= Html::encode(Texte::t('aeltere')) ?> →</a>
            <?php endif; ?>
        </div>
    </div>
</div>
