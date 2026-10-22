<?php

/**
 * Bild-Auswahl einer Kachel (services/Auswahl.php): Entwuerfe A, B, C … mit grossem Bild, je Entwurf
 * Zustimmung / Bedenken / Einwand, fuer die Leitung der Stand und "Entwurf X waehlen".
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $ich humhub\modules\user\models\User
 */

use humhub\helpers\Html;
use humhub\modules\user\models\User;
use nexus\modules\projekte\services\Auswahl;
use nexus\modules\projekte\services\Dateien;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;

$aUrl = fn(string $aktion, array $p = []) => $kreis->createUrl('/nexus-projekte/auswahl/' . $aktion, ['k' => $karte->id] + $p);
$optionen = Auswahl::optionen($karte);
$buchstaben = Auswahl::buchstaben($optionen);
$abstimmung = $karte->spalte === 'abstimmung';
$stimmen = $abstimmung ? Auswahl::stimmen($karte) : [];
$darfAbstimmen = $abstimmung && Rechte::darfAbstimmen($karte, $ich);
$gewaehlt = $karte->spalte === 'fertig' ? Auswahl::gewaehlt($optionen) : null;
$name = fn($s) => $s->autor ? $s->autor->displayName : '?';

$karteHtml = function ($o, bool $gross) use ($buchstaben, $stimmen, $darfAbstimmen, $aUrl, $karte, $ich, $name, $gewaehlt) {
    $info = $o->info();
    $b = $buchstaben[(int)$o->id];
    $meine = null;
    $nachArt = array_fill_keys(array_keys(Auswahl::ARTEN), []);
    foreach ($stimmen[(int)$o->id] ?? [] as $s) {
        $nachArt[$s->art][] = $s;
        if ((int)$s->user_id === (int)$ich->id) {
            $meine = $s;
        }
    }
    ob_start(); ?>
    <div class="nx-pj-option<?= $gewaehlt && (int)$gewaehlt->id === (int)$o->id ? ' nx-pj-gewaehlt' : '' ?><?= $gross ? ' nx-pj-option-gross' : '' ?>" id="entwurf-<?= (int)$o->id ?>">
        <div class="nx-pj-option-kopf"><span class="nx-pj-buchstabe"><?= Html::encode($b) ?></span> <strong><?= Html::encode($o->titel) ?></strong></div>
        <?php if ($info === null) : ?>
            <div class="nx-pj-leise"><i class="fa fa-ban"></i> <?= Html::encode(Texte::t('datei_fehlt')) ?></div>
        <?php else : ?>
            <a class="nx-pj-option-bild" href="<?= Html::encode($info['url']) ?>" target="_blank" rel="noopener"><img src="<?= Html::encode($info['url']) ?>" alt="<?= Html::encode(Texte::t('auswahl_entwurf') . ' ' . $b . ': ' . $o->titel) ?>" loading="lazy"></a>
        <?php endif; ?>
        <div class="nx-pj-leise"><?= Html::encode(sprintf(Texte::t('auswahl_von'), $o->autor ? $o->autor->displayName : '?', Texte::datum($o->created_at))) ?></div>

        <?php if ($stimmen !== []) : ?>
            <?php foreach (Auswahl::ARTEN as $art => [$text, $symbol, $farbe]) : ?>
                <?php if ($nachArt[$art] === []) { continue; } ?>
                <div class="<?= $farbe ?>"><i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode(Texte::t($text)) ?>: <?= Html::encode(implode(', ', array_map($name, $nachArt[$art]))) ?></div>
                <?php foreach ($nachArt[$art] as $s) : ?>
                    <?php if ($s->text) : ?><blockquote><strong><?= Html::encode($name($s)) ?>:</strong> <?= Html::encode((string)$s->text) ?></blockquote><?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($darfAbstimmen) : ?>
            <?php if ($meine !== null) : ?>
                <div><strong><?= Html::encode(sprintf(Texte::t('deine_stimme'), Texte::t(Auswahl::ARTEN[$meine->art][0] ?? ''))) ?></strong></div>
            <?php endif; ?>
            <div class="nx-pj-knoepfe">
                <?php foreach (Auswahl::ARTEN as $art => [$text, $symbol, $farbe, $mitText]) : ?>
                    <?php if ($mitText) : ?>
                        <button type="button" class="btn btn-sm btn-default" data-nx-pj-oeffne="nx-pj-o<?= (int)$o->id ?>-<?= $art ?>"><i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode(Texte::t($text)) ?></button>
                    <?php else : ?>
                        <?= Html::beginForm($aUrl('stimme', ['o' => $o->id]), 'post') ?>
                            <?= Html::hiddenInput('art', $art) ?>
                            <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode(Texte::t($text)) ?></button>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php foreach (Auswahl::ARTEN as $art => [$text, $symbol, $farbe, $mitText]) : ?>
                <?php if (!$mitText) { continue; } ?>
                <div id="nx-pj-o<?= (int)$o->id ?>-<?= $art ?>" hidden style="margin-top:.5rem">
                    <?= Html::beginForm($aUrl('stimme', ['o' => $o->id]), 'post') ?>
                        <?= Html::hiddenInput('art', $art) ?>
                        <label class="nx-pj-label" for="nx-pj-o<?= (int)$o->id ?>-<?= $art ?>-text"><?= Html::encode(Texte::t($text)) ?> – <?= Html::encode(Texte::t('stellung_text')) ?></label>
                        <textarea id="nx-pj-o<?= (int)$o->id ?>-<?= $art ?>-text" class="form-control" name="text" rows="3" required minlength="5" maxlength="3000"><?= Html::encode($meine && $meine->art === $art ? (string)$meine->text : '') ?></textarea>
                        <div class="nx-pj-knoepfe"><button type="submit" class="btn btn-primary btn-sm"><?= Html::encode(Texte::t('absenden')) ?></button></div>
                    <?= Html::endForm() ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (Rechte::darfEntwurfLoeschen($karte, $o->created_by, $ich)) : ?>
            <?= Html::beginForm($aUrl('loeschen', ['o' => $o->id]), 'post', ['data-nx-pj-frage' => sprintf(Texte::t('auswahl_loeschen_frage'), $b)]) ?>
                <button type="submit" class="btn btn-sm btn-light"><i class="fa fa-trash-o"></i> <?= Html::encode(Texte::t('auswahl_loeschen')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
    <?php return ob_get_clean();
};
?>
<div class="nx-pj-auswahl">
    <div class="nx-pj-label"><i class="fa fa-picture-o"></i> <?= Html::encode(Texte::t('auswahl')) ?>
        <span class="nx-pj-leise" style="font-weight:normal">– <?= Html::encode(Texte::t($abstimmung ? 'auswahl_erklaerung_abstimmung' : 'auswahl_erklaerung')) ?></span>
    </div>

    <?php if ($gewaehlt !== null) : ?>
        <div class="nx-pj-optionen"><?= $karteHtml($gewaehlt, true) ?></div>
        <?php $andere = array_filter($optionen, fn($o) => (int)$o->id !== (int)$gewaehlt->id); ?>
        <?php if ($andere !== []) : ?>
            <details class="nx-pj-andere">
                <summary><?= Html::encode(sprintf(Texte::t('auswahl_andere'), count($andere))) ?></summary>
                <div class="nx-pj-optionen"><?php foreach ($andere as $o) { echo $karteHtml($o, false); } ?></div>
            </details>
        <?php endif; ?>
    <?php elseif ($optionen === []) : ?>
        <div class="nx-pj-leise"><?= Html::encode(Texte::t('auswahl_keine')) ?></div>
    <?php else : ?>
        <div class="nx-pj-optionen"><?php foreach ($optionen as $o) { echo $karteHtml($o, false); } ?></div>
    <?php endif; ?>

    <?php if (Rechte::darfEntwurfEinstellen($karte, $ich) && Dateien::hochladbar($kreis)) : ?>
        <?= Html::beginForm($aUrl('hochladen'), 'post', ['enctype' => 'multipart/form-data', 'class' => 'nx-pj-hochladen']) ?>
            <label for="nx-pj-option-datei"><?= Html::encode(Texte::t('auswahl_hochladen')) ?></label>
            <input id="nx-pj-option-datei" type="file" name="datei" accept="image/jpeg,image/png,image/gif,image/webp" required class="form-control">
            <input type="text" name="titel" maxlength="120" class="form-control" placeholder="<?= Html::encode(Texte::t('auswahl_titel')) ?>" aria-label="<?= Html::encode(Texte::t('auswahl_titel')) ?>">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-upload"></i> <?= Html::encode(Texte::t('hochladen')) ?></button>
        <?= Html::endForm() ?>
    <?php endif; ?>

    <?php if ($abstimmung) : ?>
        <?php
        $abgestimmt = [];
        foreach ($stimmen as $liste) {
            foreach ($liste as $s) {
                $abgestimmt[(int)$s->user_id] = true;
            }
        }
        $ids = array_diff($karte->projekt->beteiligteIds(), array_keys($abgestimmt));
        $fehlen = $ids === [] ? [] : User::find()->where(['id' => $ids])->all();
        ?>
        <?php if ($karte->abstimmung_bis) : ?>
            <div class="nx-pj-leise" style="margin-top:.5rem"><?= Html::encode(Texte::t('rueckmeldung_bis')) ?> <?= Html::encode(Texte::datum($karte->abstimmung_bis, 'php:D d.m.Y')) ?></div>
        <?php endif; ?>
        <?php if ($fehlen !== []) : ?>
            <div class="nx-pj-leise"><i class="fa fa-clock-o"></i> <?= Html::encode(Texte::t('fehlt_noch')) ?>: <?= Html::encode(implode(', ', array_map(fn($u) => $u->displayName, $fehlen))) ?></div>
        <?php endif; ?>

        <?php if (Rechte::darfAuswahlEntscheiden($karte, $ich)) : ?>
            <?php $auswertung = Auswahl::auswertung($karte, $optionen); ?>
            <div class="nx-pj-auswerten">
                <div class="nx-pj-label"><?= Html::encode(Texte::t('runde_auswerten')) ?></div>
                <?php foreach ($optionen as $o) : ?>
                    <div class="nx-pj-leise<?= in_array((int)$o->id, $auswertung['waehlbar'], true) ? '' : ' nx-pj-durch' ?>">
                        <?= Html::encode($buchstaben[(int)$o->id] . ' „' . $o->titel . '“: ' . Auswahl::zahlenText($auswertung['zahlen'][(int)$o->id])) ?>
                    </div>
                <?php endforeach; ?>
                <?php if ($auswertung['sieger'] === []) : ?>
                    <div class="nx-pj-leise nx-pj-gelb"><?= Html::encode(Texte::t('auswahl_keiner_traegt')) ?></div>
                <?php elseif (count($auswertung['sieger']) > 1) : ?>
                    <div class="nx-pj-leise"><?= Html::encode(Texte::t('auswahl_gleichstand')) ?></div>
                <?php endif; ?>
                <div class="nx-pj-knoepfe">
                    <?php foreach ($auswertung['sieger'] as $id) : ?>
                        <?= Html::beginForm($aUrl('festhalten', ['o' => $id]), 'post', ['data-nx-pj-frage' => sprintf(Texte::t('auswahl_festhalten_frage'), $buchstaben[$id])]) ?>
                            <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i> <?= Html::encode(sprintf(Texte::t('auswahl_waehlen'), $buchstaben[$id])) ?></button>
                        <?= Html::endForm() ?>
                    <?php endforeach; ?>
                    <?= Html::beginForm($aUrl('zurueck'), 'post', ['data-nx-pj-frage' => Texte::t('auswahl_zurueck_frage')]) ?>
                        <button type="submit" class="btn btn-sm btn-default"><i class="fa fa-undo"></i> <?= Html::encode(Texte::t('auswahl_zurueck')) ?></button>
                    <?= Html::endForm() ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
