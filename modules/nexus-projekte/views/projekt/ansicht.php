<?php

/**
 * Die Projekt-Tafel: Kopf mit Ziel und Fortschritt, vier Spalten mit Kacheln, darunter die gewaehlte Kachel.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $projekt nexus\modules\projekte\models\Projekt
 * @var $karten nexus\modules\projekte\models\Karte[]
 * @var $stimmenZahl array<int, array{ok: int, aenderung: int}>
 * @var $gewaehlt nexus\modules\projekte\models\Karte|null
 * @var $ich humhub\modules\user\models\User
 * @var $meldung string|null
 */

use humhub\helpers\Html;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\services\Entscheidung;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;

$fertig = count(array_filter($karten, fn($k) => $k->spalte === 'fertig'));
$prozent = $karten === [] ? 0 : round(100 * $fertig / count($karten));
$darfLeiten = Rechte::darfLeiten($projekt, $ich);
$istMitglied = Rechte::istMitglied($kreis, $ich);
$machtMit = in_array((int)$ich->id, $projekt->mitmacherIds(), true);
$hatVorschlaege = array_filter($karten, fn($k) => $k->istVorschlag()) !== [];
$symbole = ['offen' => 'circle-o', 'arbeit' => 'pencil', 'abstimmung' => 'thumbs-o-up', 'fertig' => 'check-circle'];
$this->title = $projekt->titel;
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <div class="nx-pj-leise"><a href="<?= Html::encode($kreis->createUrl('/nexus-projekte/projekt/index')) ?>"><i class="fa fa-columns"></i> <?= Html::encode(Texte::t('projekte')) ?></a></div>
        <div class="nx-pj-kopf">
            <div style="flex:1;min-width:220px">
                <h1><?= Html::encode($projekt->titel) ?><?php if ($projekt->status !== 'aktiv') : ?> <span class="nx-pj-leise">(<?= Html::encode(Texte::t('abgeschlossen')) ?>)</span><?php endif; ?></h1>
                <?php if ($projekt->ziel) : ?><div style="white-space:pre-wrap"><?= Html::encode($projekt->ziel) ?></div><?php endif; ?>
                <div class="nx-pj-leise">
                    <?= Html::encode(Texte::t('leitung')) ?>: <?= Html::encode($projekt->leitungNamen()) ?>
                    · <?= Html::encode($projekt->frist ? sprintf(Texte::t('bis'), Texte::datum($projekt->frist)) : Texte::t('laufend')) ?>
                </div>
            </div>
            <div>
                <div class="nx-pj-leise"><?= Html::encode(sprintf(Texte::t('fertig_von'), $fertig, count($karten))) ?></div>
                <div class="nx-pj-balken"><span style="width:<?= (int)$prozent ?>%"></span></div>
                <div class="nx-pj-knoepfe" style="margin-top:.4rem">
                    <?php if ($darfLeiten) : ?>
                        <a class="btn btn-primary btn-sm" href="<?= Html::encode($kreis->createUrl('/nexus-projekte/projekt/karte', ['p' => $projekt->id])) ?>"><i class="fa fa-plus"></i> <?= Html::encode(Texte::t('neue_karte')) ?></a>
                        <a class="btn btn-default btn-sm" href="<?= Html::encode($kreis->createUrl('/nexus-projekte/projekt/formular', ['p' => $projekt->id])) ?>"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t('bearbeiten')) ?></a>
                    <?php endif; ?>
                    <?php if (Rechte::darfVorschlagen($projekt, $ich)) : ?>
                        <a class="btn btn-default btn-sm" href="<?= Html::encode($kreis->createUrl('/nexus-projekte/vorschlag/formular', ['p' => $projekt->id])) ?>" title="<?= Html::encode(Texte::t('vorschlag_hinweis')) ?>"><i class="fa fa-lightbulb-o"></i> <?= Html::encode(Texte::t('neuer_vorschlag')) ?></a>
                    <?php endif; ?>
                    <?php if ($hatVorschlaege) : ?>
                        <a class="btn btn-light btn-sm" href="<?= Html::encode($kreis->createUrl('/nexus-projekte/vorschlag/beschluesse', ['p' => $projekt->id])) ?>"><i class="fa fa-gavel"></i> <?= Html::encode(Texte::t('beschluesse')) ?></a>
                    <?php endif; ?>
                    <?php if ($istMitglied && !$projekt->istLeitung((int)$ich->id)) : ?>
                        <?= Html::beginForm($kreis->createUrl('/nexus-projekte/projekt/mitmachen', ['p' => $projekt->id]), 'post', ['title' => Texte::t('mitmacher_hinweis')]) ?>
                            <?= Html::hiddenInput('an', $machtMit ? '0' : '1') ?>
                            <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-<?= $machtMit ? 'check' : 'hand-paper-o' ?>"></i> <?= Html::encode(Texte::t($machtMit ? 'nicht_mehr' : 'mitmachen')) ?></button>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($meldung) : ?><div class="nx-pj-meldung"><?= Html::encode($meldung) ?></div><?php endif; ?>

        <div class="nx-pj-tafel">
            <?php foreach (Karte::SPALTEN as $spalte) : ?>
                <?php $inSpalte = array_filter($karten, fn($k) => $k->spalte === $spalte); ?>
                <section class="nx-pj-spalte" aria-label="<?= Html::encode(Texte::t('spalte_' . $spalte)) ?>">
                    <h2><span><i class="fa fa-<?= $symbole[$spalte] ?>"></i> <?= Html::encode(Texte::t('spalte_' . $spalte)) ?></span><span><?= count($inSpalte) ?></span></h2>
                    <?php foreach ($inSpalte as $k) : ?>
                        <?php $schritte = $k->schritte; $erledigt = count(array_filter($schritte, fn($s) => $s->erledigt)); ?>
                        <a class="nx-pj-karte<?= $gewaehlt && $gewaehlt->id === $k->id ? ' nx-pj-gewaehlt' : '' ?>" href="<?= Html::encode($k->url()) ?>">
                            <strong><?php if ($k->istVorschlag()) : ?><i class="fa fa-lightbulb-o" title="<?= Html::encode(Texte::t('vorschlag')) ?>"></i> <?php endif; ?><?= Html::encode($k->titel) ?></strong>
                            <span class="nx-pj-meta">
                                <?php if ($k->verantwortlich) : ?>
                                    <span><i class="fa fa-user"></i> <?= Html::encode($k->verantwortlich->displayName) ?></span>
                                <?php elseif ($spalte !== 'fertig') : ?>
                                    <span class="nx-pj-blau"><i class="fa fa-hand-paper-o"></i> <?= Html::encode(Texte::t('wer_mag')) ?></span>
                                <?php endif; ?>
                                <?php $datum = $spalte === 'abstimmung' ? $k->abstimmung_bis : ($spalte !== 'fertig' ? $k->frist : null); ?>
                                <?php if ($datum) : ?>
                                    <span class="<?= $datum < date('Y-m-d') ? 'nx-pj-rot' : '' ?>"><i class="fa fa-clock-o"></i> <?= Html::encode(Texte::datum($datum, 'php:d.m.')) ?></span>
                                <?php endif; ?>
                                <?php if ($schritte !== []) : ?><span><i class="fa fa-check-square-o"></i> <?= $erledigt ?>/<?= count($schritte) ?></span><?php endif; ?>
                                <?php if (isset($stimmenZahl[$k->id])) : ?>
                                    <?php foreach (Entscheidung::optionen(Entscheidung::regelFuer($k)) as $art => [$text, $symbol, $farbe]) : ?>
                                        <?php if ($stimmenZahl[$k->id][$art] > 0 || Entscheidung::istPositiv($art)) : ?>
                                            <span class="<?= $farbe ?>" title="<?= Html::encode(Texte::t($text)) ?>"><i class="fa fa-<?= $symbol ?>"></i> <?= (int)$stimmenZahl[$k->id][$art] ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if ($k->ergebnis) : ?>
                                    <span class="<?= ['angenommen' => 'nx-pj-gruen', 'abgelehnt' => 'nx-pj-rot'][$k->ergebnis] ?? '' ?>"><?= Html::encode(Texte::t('ergebnis_' . $k->ergebnis)) ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>

        <?php if ($gewaehlt !== null) : ?>
            <?= $this->render('_detail', ['kreis' => $kreis, 'karte' => $gewaehlt, 'ich' => $ich]) ?>
        <?php endif; ?>
    </div>
</div>
