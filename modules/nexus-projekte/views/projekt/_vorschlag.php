<?php

/**
 * Inhalt eines Vorschlags in der aufgeklappten Kachel: Zusammenfassung, Begruendung, Textaenderungen
 * (vorher/nachher), Dateien und -- wenn entschieden -- Ergebnis, Weitergabe, Umsetzung.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $ich humhub\modules\user\models\User
 */

use humhub\helpers\Html;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\services\Dateien;
use nexus\modules\projekte\services\Diff;
use nexus\modules\projekte\services\Entscheidung;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;

$projekt = $karte->projekt;
$url = fn(string $aktion, array $p = []) => $kreis->createUrl('/nexus-projekte/vorschlag/' . $aktion, ['k' => $karte->id] + $p);
$fassungen = Dateien::verfuegbar() ? Dateien::fassungen($karte) : [];
$farbe = ['angenommen' => 'nx-pj-gruen', 'abgelehnt' => 'nx-pj-rot', 'zurueckgezogen' => 'nx-pj-leise'];
?>
<div class="nx-pj-vorschlag">
    <div class="nx-pj-leise">
        <i class="fa fa-lightbulb-o"></i> <?= Html::encode(Texte::t('vorschlag')) ?>
        · <?= Html::encode(Texte::t('regel_kurz_' . Entscheidung::regelFuer($karte))) ?>
        <?php if ($karte->runde > 1) : ?> · <?= Html::encode(sprintf(Texte::t('runde_nr'), $karte->runde)) ?><?php endif; ?>
    </div>

    <?php if ($karte->ergebnis) : ?>
        <div class="nx-pj-ergebnis">
            <strong class="<?= $farbe[$karte->ergebnis] ?? '' ?>"><?= Html::encode(Texte::t('ergebnis_' . $karte->ergebnis)) ?></strong>
            <?php if ($karte->ergebnis_text) : ?> · <?= Html::encode($karte->ergebnis_text) ?><?php endif; ?>
            <?php if ($karte->ergebnis_am) : ?> · <span class="nx-pj-leise"><?= Html::encode(sprintf(Texte::t('entschieden'), Texte::datum($karte->ergebnis_am))) ?></span><?php endif; ?>

            <?php if ($karte->ergebnis === 'angenommen') : ?>
                <div style="margin-top:.4rem">
                    <?php if ($projekt->weitergabe) : ?>
                        <?= Html::encode(sprintf(Texte::t('weitergabe_status'), $projekt->weitergabe)) ?>:
                        <strong><?= Html::encode(Texte::t('weitergabe_' . ($karte->weitergabe_status ?: 'offen'))) ?></strong>
                        <?php if ($karte->weitergabe_am) : ?><span class="nx-pj-leise">(<?= Html::encode(Texte::datum($karte->weitergabe_am)) ?>)</span><?php endif; ?>
                        <br>
                    <?php endif; ?>
                    <?= Html::encode(Texte::t('umgesetzt')) ?>:
                    <strong><?= Html::encode($karte->umgesetzt ?: Texte::t('nicht_umgesetzt')) ?></strong>
                    <?php if ($karte->umgesetzt_am) : ?><span class="nx-pj-leise">(<?= Html::encode(Texte::datum($karte->umgesetzt_am)) ?>)</span><?php endif; ?>
                </div>
                <?php if (Rechte::darfEntscheiden($karte, $ich)) : ?>
                    <?= Html::beginForm($url('weitergabe'), 'post', ['class' => 'nx-pj-zeile']) ?>
                        <?php if ($projekt->weitergabe) : ?>
                            <select name="weitergabe_status" class="form-control input-sm" aria-label="<?= Html::encode(sprintf(Texte::t('weitergabe_status'), $projekt->weitergabe)) ?>">
                                <option value=""><?= Html::encode(Texte::t('weitergabe_offen')) ?></option>
                                <?php foreach (Karte::WEITERGABE as $s) : ?>
                                    <option value="<?= $s ?>" <?= $karte->weitergabe_status === $s ? 'selected' : '' ?>><?= Html::encode(Texte::t('weitergabe_' . $s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <input name="umgesetzt" class="form-control input-sm" maxlength="190" value="<?= Html::encode((string)$karte->umgesetzt) ?>"
                               placeholder="<?= Html::encode(Texte::t('umgesetzt') . ' – ' . Texte::t('umgesetzt_hilfe')) ?>" aria-label="<?= Html::encode(Texte::t('umgesetzt')) ?>">
                        <button type="submit" class="btn btn-sm btn-default"><?= Html::encode(Texte::t('vermerken')) ?></button>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($karte->ergebnis !== 'zurueckgezogen') : ?>
                <div class="nx-pj-knoepfe">
                    <a class="btn btn-sm btn-default" href="<?= Html::encode($url('vorlage')) ?>" target="_blank" rel="noopener"><i class="fa fa-print"></i> <?= Html::encode(Texte::t('beschlussvorlage')) ?></a>
                    <a class="btn btn-sm btn-light" href="<?= Html::encode($url('vorlage', ['format' => 'word'])) ?>"><i class="fa fa-file-word-o"></i> <?= Html::encode(Texte::t('als_word')) ?></a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($karte->zusammenfassung) : ?>
        <div class="nx-pj-label" style="margin-top:.6rem"><?= Html::encode(Texte::t('zusammenfassung')) ?></div>
        <div style="white-space:pre-wrap"><?= Html::encode($karte->zusammenfassung) ?></div>
    <?php endif; ?>
    <?php if ($karte->begruendung) : ?>
        <div class="nx-pj-label" style="margin-top:.6rem"><?= Html::encode(Texte::t('begruendung')) ?></div>
        <div style="white-space:pre-wrap"><?= Html::encode($karte->begruendung) ?></div>
    <?php endif; ?>

    <?php if ($karte->aenderungen !== []) : ?>
        <div class="nx-pj-label" style="margin-top:.6rem"><?= Html::encode(Texte::t('textaenderungen')) ?></div>
        <?php foreach ($karte->aenderungen as $a) : ?>
            <div class="nx-pj-diff">
                <div class="nx-pj-leise"><i class="fa fa-map-marker"></i> <?= Html::encode($a->stelle) ?></div>
                <div class="nx-pj-diff-text"><?= Diff::html($a->alt, $a->neu) ?></div>
                <details>
                    <summary class="nx-pj-leise"><?= Html::encode(Texte::t('bisher')) ?> / <?= Html::encode(Texte::t('kuenftig')) ?></summary>
                    <div class="nx-pj-zweispaltig">
                        <div><div class="nx-pj-label"><?= Html::encode(Texte::t('bisher')) ?></div><div class="nx-pj-roh"><?= Html::encode((string)$a->alt ?: Texte::t('bisher_leer')) ?></div></div>
                        <div><div class="nx-pj-label"><?= Html::encode(Texte::t('kuenftig')) ?></div><div class="nx-pj-roh"><?= Html::encode((string)$a->neu ?: Texte::t('kuenftig_leer')) ?></div></div>
                    </div>
                </details>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($fassungen !== []) : ?>
        <div class="nx-pj-label" style="margin-top:.6rem"><?= Html::encode(Texte::t('dateien')) ?></div>
        <?= $this->render('_dateien', ['fassungen' => $fassungen]) ?>
    <?php endif; ?>
</div>
