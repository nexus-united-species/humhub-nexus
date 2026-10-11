<?php

/**
 * Die aufgeklappte Kachel unter der Tafel: wer, bis wann, Unterlagen, Teilschritte, Abstimmung, Knoepfe.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $ich humhub\modules\user\models\User
 */

use humhub\helpers\Html;
use humhub\modules\user\models\User;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;

$url = fn(string $aktion, array $p = []) => $kreis->createUrl('/nexus-projekte/projekt/' . $aktion, ['k' => $karte->id] + $p);
$helfer = $karte->helfer();
$stimmen = $karte->spalte === 'abstimmung' ? $karte->stimmen() : [];
$meine = null;
foreach ($stimmen as $s) {
    if ((int)$s->user_id === (int)$ich->id) {
        $meine = $s;
    }
}
$zustimmung = array_filter($stimmen, fn($s) => $s->art === 'ok');
$aenderungen = array_filter($stimmen, fn($s) => $s->art === 'aenderung');
$fehlen = [];
if ($karte->spalte === 'abstimmung') {
    $ids = array_diff($karte->projekt->beteiligteIds(), array_map(fn($s) => (int)$s->user_id, $stimmen), [(int)$karte->verantwortlich_id]);
    $fehlen = $ids === [] ? [] : User::find()->where(['id' => $ids])->all();
}
$knopf = function (string $ziel, string $text, string $symbol, string $klasse = 'btn-default') use ($karte, $url, $ich) {
    if (!Rechte::darfVerschieben($karte, $ziel, $ich)) {
        return '';
    }
    return Html::beginForm($url('verschieben'), 'post') . Html::hiddenInput('ziel', $ziel)
        . '<button type="submit" class="btn btn-sm ' . $klasse . '"><i class="fa fa-' . $symbol . '"></i> ' . Html::encode(Texte::t($text)) . '</button>'
        . Html::endForm();
};
?>
<div class="nx-pj-detail" id="karte">
    <div style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap">
        <div>
            <h2><?= Html::encode($karte->titel) ?></h2>
            <div class="nx-pj-leise">
                <?php if ($karte->verantwortlich) : ?>
                    <?= Html::encode(Texte::t('verantwortlich')) ?>: <strong><?= Html::encode($karte->verantwortlich->displayName) ?></strong>
                <?php else : ?>
                    <?= Html::encode(Texte::t('niemand')) ?>
                <?php endif; ?>
                <?php if ($helfer !== []) : ?> · <?= Html::encode(Texte::t('hilft_mit')) ?>: <?= Html::encode(implode(', ', array_map(fn($u) => $u->displayName, $helfer))) ?><?php endif; ?>
                <?php if ($karte->frist) : ?> · <span class="<?= $karte->istUeberfaellig() ? 'nx-pj-rot' : '' ?>"><?= Html::encode(Texte::t('frist')) ?>: <?= Html::encode(Texte::datum($karte->frist)) ?></span><?php endif; ?>
            </div>
        </div>
        <span class="nx-pj-leise"><?= Html::encode(Texte::t('spalte_' . $karte->spalte)) ?></span>
    </div>

    <?php if ($karte->beschreibung) : ?>
        <div style="white-space:pre-wrap;margin-top:.6rem"><?= Html::encode($karte->beschreibung) ?></div>
    <?php endif; ?>

    <?php if ($karte->linkListe() !== []) : ?>
        <div class="nx-pj-unterlagen">
            <?php foreach ($karte->linkListe() as $l) : ?>
                <a href="<?= Html::encode($l['url']) ?>" <?= str_starts_with($l['url'], '/') ? '' : 'target="_blank" rel="noopener"' ?>><i class="fa fa-paperclip"></i> <?= Html::encode($l['titel']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($karte->schritte !== []) : ?>
        <div class="nx-pj-label" style="margin-top:.6rem"><?= Html::encode(Texte::t('teilschritte')) ?></div>
        <ul class="nx-pj-schritte">
            <?php $darfAbhaken = Rechte::darfAbhaken($karte, $ich); ?>
            <?php foreach ($karte->schritte as $s) : ?>
                <li>
                    <?php if ($darfAbhaken) : ?>
                        <?= Html::beginForm($url('schritt', ['s' => $s->id]), 'post', ['style' => 'display:inline']) ?>
                            <button type="submit" class="<?= $s->erledigt ? 'nx-pj-erledigt' : '' ?>"><i class="fa fa-<?= $s->erledigt ? 'check-square-o' : 'square-o' ?>"></i> <?= Html::encode($s->text) ?></button>
                        <?= Html::endForm() ?>
                    <?php else : ?>
                        <span class="<?= $s->erledigt ? 'nx-pj-erledigt' : '' ?>"><i class="fa fa-<?= $s->erledigt ? 'check-square-o' : 'square-o' ?>"></i> <?= Html::encode($s->text) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($karte->spalte === 'abstimmung') : ?>
        <div class="nx-pj-abstimmung">
            <?php if ($karte->abstimmung_bis) : ?>
                <div class="nx-pj-leise"><?= Html::encode(Texte::t('rueckmeldung_bis')) ?> <?= Html::encode(Texte::datum($karte->abstimmung_bis, 'php:D d.m.Y')) ?></div>
            <?php endif; ?>
            <div class="nx-pj-gruen"><i class="fa fa-thumbs-o-up"></i> <?= Html::encode(Texte::t('passt')) ?>:
                <?= Html::encode($zustimmung === [] ? '–' : implode(', ', array_map(fn($s) => $s->autor ? $s->autor->displayName : '?', $zustimmung))) ?></div>
            <?php foreach ($aenderungen as $s) : ?>
                <div class="nx-pj-gelb" style="margin-top:.35rem"><i class="fa fa-pencil"></i> <?= Html::encode($s->autor ? $s->autor->displayName : '?') ?>:</div>
                <blockquote><?= Html::encode((string)$s->text) ?></blockquote>
            <?php endforeach; ?>
            <?php if ($fehlen !== []) : ?>
                <div class="nx-pj-leise" style="margin-top:.35rem"><i class="fa fa-clock-o"></i> <?= Html::encode(Texte::t('fehlt_noch')) ?>: <?= Html::encode(implode(', ', array_map(fn($u) => $u->displayName, $fehlen))) ?></div>
            <?php endif; ?>

            <?php if (Rechte::darfAbstimmen($karte, $ich)) : ?>
                <?php if ($meine !== null) : ?>
                    <div style="margin-top:.5rem"><strong><?= Html::encode(sprintf(Texte::t('deine_stimme'), Texte::t($meine->art === 'ok' ? 'passt' : 'aenderung'))) ?></strong></div>
                <?php endif; ?>
                <div class="nx-pj-knoepfe">
                    <?= Html::beginForm($url('stimme'), 'post') ?>
                        <?= Html::hiddenInput('art', 'ok') ?>
                        <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-thumbs-o-up"></i> <?= Html::encode(Texte::t('passt')) ?></button>
                    <?= Html::endForm() ?>
                    <button type="button" class="btn btn-sm btn-default" data-nx-pj-oeffne="nx-pj-aenderung"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t('aenderung')) ?></button>
                </div>
                <div id="nx-pj-aenderung" hidden style="margin-top:.6rem">
                    <?= Html::beginForm($url('stimme'), 'post') ?>
                        <?= Html::hiddenInput('art', 'aenderung') ?>
                        <label class="nx-pj-label" for="nx-pj-aenderung-text"><?= Html::encode(Texte::t('aenderung_text')) ?></label>
                        <textarea id="nx-pj-aenderung-text" class="form-control" name="text" rows="3" required minlength="5" maxlength="3000"><?= Html::encode($meine && $meine->art === 'aenderung' ? (string)$meine->text : '') ?></textarea>
                        <div class="nx-pj-knoepfe"><button type="submit" class="btn btn-primary btn-sm"><?= Html::encode(Texte::t('absenden')) ?></button></div>
                    <?= Html::endForm() ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="nx-pj-knoepfe">
        <?php if (Rechte::darfUebernehmen($karte, $ich)) : ?>
            <?= Html::beginForm($url('uebernehmen'), 'post') ?>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-hand-paper-o"></i> <?= Html::encode(Texte::t('uebernehmen')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>
        <?php if ($karte->spalte === 'arbeit' || $karte->spalte === 'offen') : ?>
            <?php if (Rechte::darfVerschieben($karte, 'abstimmung', $ich)) : ?>
                <?= Html::beginForm($url('verschieben'), 'post', ['style' => 'display:inline-flex;gap:.4rem;align-items:center;flex-wrap:wrap']) ?>
                    <?= Html::hiddenInput('ziel', 'abstimmung') ?>
                    <label class="nx-pj-leise" for="nx-pj-bis" style="margin:0"><?= Html::encode(Texte::t('rueckmeldung_bis')) ?></label>
                    <input id="nx-pj-bis" type="date" name="abstimmung_bis" class="form-control input-sm" style="width:auto" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" min="<?= date('Y-m-d') ?>">
                    <button type="submit" class="btn btn-sm btn-default" title="<?= Html::encode(Texte::t('abstimmung_hinweis')) ?>"><i class="fa fa-thumbs-o-up"></i> <?= Html::encode(Texte::t('zur_abstimmung')) ?></button>
                <?= Html::endForm() ?>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($karte->spalte === 'abstimmung') : ?>
            <?= $knopf('arbeit', 'in_arbeit', 'undo') ?>
        <?php endif; ?>
        <?php if ($karte->spalte !== 'fertig') : ?>
            <?= $knopf('fertig', 'als_fertig', 'check', 'btn-success') ?>
        <?php else : ?>
            <?= $knopf('arbeit', 'wieder_oeffnen', 'undo') ?>
        <?php endif; ?>
        <?php if (Rechte::darfKarteBearbeiten($karte, $ich)) : ?>
            <a class="btn btn-sm btn-light" href="<?= Html::encode($url('karte', ['p' => $karte->projekt_id])) ?>"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t('bearbeiten')) ?></a>
        <?php endif; ?>
    </div>
</div>
