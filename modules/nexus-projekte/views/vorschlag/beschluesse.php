<?php

/**
 * Beschlussliste eines Projekts: was entschieden ist (mit Weitergabe und Umsetzung) und was noch laeuft.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $projekt nexus\modules\projekte\models\Projekt
 * @var $entschieden nexus\modules\projekte\models\Karte[]
 * @var $laufend nexus\modules\projekte\models\Karte[]
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Entscheidung;
use nexus\modules\projekte\services\Texte;

$this->title = sprintf(Texte::t('beschluesse_titel'), $projekt->titel);
$farbe = ['angenommen' => 'nx-pj-gruen', 'abgelehnt' => 'nx-pj-rot', 'zurueckgezogen' => 'nx-pj-leise'];
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <div class="nx-pj-leise"><a href="<?= Html::encode($projekt->url()) ?>"><i class="fa fa-columns"></i> <?= Html::encode($projekt->titel) ?></a></div>
        <h1 style="font-size:1.4rem"><i class="fa fa-gavel"></i> <?= Html::encode(Texte::t('beschluesse')) ?></h1>
        <div class="nx-pj-leise"><?= Html::encode(sprintf(Texte::t('regel_hinweis'), Texte::t('regel_kurz_' . Entscheidung::regel($projekt->regel)), (int)$projekt->frist_tage)) ?>
            <?php if ($projekt->weitergabe) : ?> · <?= Html::encode(Texte::t('weitergabe')) ?>: <?= Html::encode($projekt->weitergabe) ?><?php endif; ?></div>

        <?php if ($entschieden === []) : ?>
            <p style="margin-top:1rem"><?= Html::encode(Texte::t('keine_beschluesse')) ?></p>
        <?php else : ?>
            <ul class="nx-pj-liste" style="margin-top:1rem">
                <?php foreach ($entschieden as $v) : ?>
                    <li>
                        <div>
                            <a href="<?= Html::encode($v->url()) ?>"><strong><?= Html::encode($v->titel) ?></strong></a>
                            <div class="nx-pj-leise">
                                <?= Html::encode(Texte::datum($v->ergebnis_am)) ?>
                                <?php if ($v->verantwortlich) : ?> · <?= Html::encode(sprintf(Texte::t('eingebracht_von'), $v->verantwortlich->displayName)) ?><?php endif; ?>
                                <?php if ($v->ergebnis_text) : ?> · <?= Html::encode($v->ergebnis_text) ?><?php endif; ?>
                            </div>
                            <?php if ($v->ergebnis === 'angenommen') : ?>
                                <div class="nx-pj-leise">
                                    <?php if ($projekt->weitergabe) : ?>
                                        <?= Html::encode(sprintf(Texte::t('weitergabe_status'), $projekt->weitergabe)) ?>: <?= Html::encode(Texte::t('weitergabe_' . ($v->weitergabe_status ?: 'offen'))) ?> ·
                                    <?php endif; ?>
                                    <?= Html::encode(Texte::t('umgesetzt')) ?>: <?= Html::encode($v->umgesetzt ?: Texte::t('nicht_umgesetzt')) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <strong class="<?= $farbe[$v->ergebnis] ?? '' ?>" style="white-space:nowrap"><?= Html::encode(Texte::t('ergebnis_' . $v->ergebnis)) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($laufend !== []) : ?>
            <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t('spalte_arbeit')) ?> / <?= Html::encode(Texte::t('spalte_abstimmung')) ?></h2>
            <ul class="nx-pj-liste">
                <?php foreach ($laufend as $v) : ?>
                    <li>
                        <a href="<?= Html::encode($v->url()) ?>"><?= Html::encode($v->titel) ?></a>
                        <span class="nx-pj-leise"><?= Html::encode(Texte::t('spalte_' . $v->spalte)) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
