<?php

/**
 * Finanzierungs-Ampel fuer alle Mitglieder.
 *
 * @var $stand nexus\modules\finanzen\services\Stand
 * @var $monat string
 * @var $rueckblick string|null
 * @var $darfVerwalten bool
 * @var $deckel int
 * @var $mithelfenUrl string
 * @var $fuerAlle bool
 */

use humhub\helpers\Html;
use nexus\modules\finanzen\services\Geld;
use nexus\modules\finanzen\services\Texte;
use yii\helpers\Url;

$summen = $stand->summen($monat);
?>
<div class="container">
<div class="panel panel-default nx-fin">
    <div class="panel-body">
        <div class="nx-fin-titelzeile">
            <h1><i class="fa fa-heartbeat"></i> <?= Html::encode(Texte::t('titel')) ?></h1>
            <?php if ($darfVerwalten) : ?>
                <a class="btn btn-light btn-sm" href="<?= Html::encode(Url::to(['/nexus-finanzen/verwaltung/index', 'monat' => $monat])) ?>"><i class="fa fa-cog"></i> <?= Html::encode(Texte::t('verwaltung')) ?></a>
            <?php endif; ?>
        </div>
        <?php if (!$fuerAlle) : ?><div class="nx-fin-hinweis"><?= Html::encode(Texte::t('nur_intern')) ?></div><?php endif; ?>
        <p><?= Html::encode(Texte::t('einleitung')) ?></p>

        <nav class="nx-fin-monate" aria-label="<?= Html::encode(Texte::t('monat')) ?>">
            <a href="<?= Html::encode(Url::to(['index', 'monat' => Geld::vorMonat($monat)])) ?>"><?= Html::encode(Texte::t('vorher')) ?></a>
            <strong><?= Html::encode(Geld::monatName($monat)) ?></strong>
            <a href="<?= Html::encode(Url::to(['index', 'monat' => Geld::nachMonat($monat)])) ?>"><?= Html::encode(Texte::t('nachher')) ?></a>
        </nav>

        <?php /* Zwei getrennte Zeilen, damit klar ist, was zum Monat und was zur Gesamtbilanz gehoert. Leere Zusatzfelder bleiben weg. */
        $zeilen = [
            sprintf(Texte::t('zeile_monat'), Geld::monatName($monat)) => ['im_monat', 'bezahlt_monat', 'vorgestreckt_monat'],
            Texte::t('zeile_gesamt') => ['eingegangen', 'bezahlt_gesamt', 'ausgegeben', 'vorgestreckt', 'frei', 'reserviert'],
        ];
        $nurWennNichtNull = ['vorgestreckt_monat', 'vorgestreckt', 'reserviert']; ?>
        <?php foreach ($zeilen as $kopf => $felder) : ?>
            <h3 class="nx-fin-summenkopf"><?= Html::encode($kopf) ?></h3>
            <div class="nx-fin-summen">
                <?php foreach ($felder as $s) : ?>
                    <?php if (in_array($s, $nurWennNichtNull, true) && $summen[$s] === 0) { continue; } ?>
                    <div><span><?= Html::encode(Texte::t($s)) ?></span><strong><?= Html::encode(Geld::zeigen($summen[$s])) ?></strong></div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <?php foreach (['grund', 'optional', 'entwicklung'] as $bereich) : ?>
            <?php $kacheln = $stand->kacheln($bereich); if ($kacheln === []) { continue; } $b = $stand->bereich($bereich, $monat); ?>
            <section class="nx-fin-bereich">
                <h2><?= Html::encode(Texte::t('bereich_' . $bereich)) ?></h2>
                <p class="nx-fin-leise"><?= Html::encode(Texte::t('bereich_' . $bereich . '_text')) ?></p>
                <div class="nx-fin-ziel">
                    <strong><?= Html::encode(sprintf(Texte::t('ziel_geplant'), Geld::zeigen($b['ziel']))) ?></strong>
                    <?php if ($b['bezahlt'] > 0) : ?>
                        · <strong><?= Html::encode(sprintf(Texte::t('ziel_bezahlt'), Geld::zeigen($b['bezahlt'], true))) ?></strong>
                    <?php endif; ?>
                    <?php if ($b['freigegeben'] > 0) : ?>
                        · <?= Html::encode(sprintf(Texte::t('ziel_freigegeben'), Geld::zeigen($b['freigegeben']), Geld::zeigen($b['gedeckt']))) ?>
                        <div class="nx-fin-balken nx-fin-gross"><span style="width:<?= (int)$b['prozent'] ?>%"></span></div>
                    <?php endif; ?>
                    <?php if ($bereich === 'entwicklung' && $deckel > 0) : ?><div class="nx-fin-leise"><?= Html::encode(sprintf(Texte::t('deckel'), Geld::zeigen($deckel))) ?></div><?php endif; ?>
                </div>
                <?php
                // Zwei Teile je Bereich: oben, was in diesem Monat bezahlt wurde; darunter, was noch im Plan steht.
                $teile = ['bezahlt' => [], 'plan' => []];
                foreach ($kacheln as $k) {
                    $teile[$stand->bezahlt($k, $monat) !== null ? 'bezahlt' : 'plan'][] = $k;
                }
                $betragIn = static fn(string $teil, $x): int => $teil === 'bezahlt' ? (int)($stand->bezahlt($x, $monat)['betrag'] ?? 0) : (int)$x->betrag;
                ?>
                <?php foreach ($teile as $teil => $teilKacheln) : ?>
                    <?php if ($teilKacheln === []) { continue; } ?>
                    <div class="nx-fin-teil nx-fin-teil-<?= $teil ?>">
                        <h3 class="nx-fin-teilkopf">
                            <?= Html::encode($teil === 'bezahlt' ? sprintf(Texte::t('teil_bezahlt'), Geld::monatName($monat)) : Texte::t('teil_plan')) ?>
                            <small><?= Html::encode(Geld::zeigen(array_sum(array_map(fn($x) => $betragIn($teil, $x), $teilKacheln)), $teil === 'bezahlt')) ?></small>
                        </h3>
                        <?php
                        // Anzeige-Bloecke: eine Gruppe steht geschlossen an der Stelle ihrer ersten Kachel (Verteilung bleibt nach Rang)
                        $bloecke = [];
                        foreach ($teilKacheln as $k) {
                            $schluessel = $k->gruppe === null ? 'k' . $k->id : 'g' . $k->gruppe;
                            $bloecke[$schluessel][] = $k;
                        }
                        ?>
                        <?php foreach ($bloecke as $schluessel => $liste) : ?>
                            <?php if ($schluessel[0] === 'g') : ?>
                                <div class="nx-fin-gruppe"><h4><?= Html::encode($liste[0]->gruppe) ?> <small><?= Html::encode(Geld::zeigen(array_sum(array_map(fn($x) => $betragIn($teil, $x), $liste)), $teil === 'bezahlt')) ?></small></h4>
                            <?php endif; ?>
                            <?php foreach ($liste as $k) : ?>
                                <?= $this->render('_kachel', ['k' => $k, 'stand' => $stand, 'monat' => $monat]) ?>
                            <?php endforeach; ?>
                            <?php if ($schluessel[0] === 'g') : ?></div><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>

        <?php if ($stand->kacheln() === []) : ?><p><?= Html::encode(Texte::t('keine')) ?></p><?php endif; ?>

        <?php if ($rueckblick) : ?>
            <section class="nx-fin-bereich">
                <h2><?= Html::encode(Texte::t('rueckblick')) ?> – <?= Html::encode(Geld::monatName($monat)) ?></h2>
                <div class="nx-fin-text"><?= Html::encode($rueckblick) ?></div>
            </section>
        <?php endif; ?>

        <p class="nx-fin-leise nx-fin-legende"><?= Html::encode(Texte::t('legende')) ?></p>
        <?php if ($mithelfenUrl !== '') : ?>
            <div class="nx-fin-mithelfen">
                <strong><?= Html::encode(Texte::t('mithelfen')) ?></strong> – <?= Html::encode(Texte::t('mithelfen_text')) ?>
                <a href="<?= Html::encode($mithelfenUrl) ?>" target="_blank" rel="noopener"><?= Html::encode(Texte::t('mithelfen')) ?> →</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>
