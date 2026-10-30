<?php

/**
 * Die aufgeklappte Kachel unter der Tafel: wer, bis wann, Unterlagen, Teilschritte, Abstimmung, Knoepfe.
 * Vorschlaege zeigen zusaetzlich ihren Inhalt (_vorschlag.php) und werden nach der Regel des Projekts
 * entschieden (services/Entscheidung.php).
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $karte nexus\modules\projekte\models\Karte
 * @var $ich humhub\modules\user\models\User
 */

use humhub\helpers\Html;
use humhub\modules\user\models\User;
use nexus\modules\projekte\services\Dateien;
use nexus\modules\projekte\services\Entscheidung;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;

$url = fn(string $aktion, array $p = []) => $kreis->createUrl('/nexus-projekte/projekt/' . $aktion, ['k' => $karte->id] + $p);
$vUrl = fn(string $aktion, array $p = []) => $kreis->createUrl('/nexus-projekte/vorschlag/' . $aktion, ['k' => $karte->id] + $p);
$istVorschlag = $karte->istVorschlag();
$istAuswahl = $karte->istAuswahl();
$regel = Entscheidung::regelFuer($karte);
$optionen = Entscheidung::optionen($regel);
$helfer = $karte->helfer();
$stimmen = $karte->spalte === 'abstimmung' ? $karte->stimmen() : [];
$meine = null;
$nachArt = array_fill_keys(array_keys($optionen), []);
foreach ($stimmen as $s) {
    if ((int)$s->user_id === (int)$ich->id) {
        $meine = $s;
    }
    $nachArt[$s->art][] = $s;
}
$fehlen = [];
if ($karte->spalte === 'abstimmung') {
    $ids = array_diff($karte->projekt->beteiligteIds(), array_map(fn($s) => (int)$s->user_id, $stimmen), [(int)$karte->verantwortlich_id], $karte->projekt->leitungIds());
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
$name = fn($s) => $s->autor ? $s->autor->displayName : '?';
?>
<div class="nx-pj-detail" id="karte">
    <div style="display:flex;justify-content:space-between;gap:.75rem;flex-wrap:wrap">
        <div>
            <h2><?php if ($istVorschlag) : ?><i class="fa fa-lightbulb-o"></i> <?php elseif ($istAuswahl) : ?><i class="fa fa-picture-o"></i> <?php endif; ?><?= Html::encode($karte->titel) ?></h2>
            <div class="nx-pj-leise">
                <?php if ($karte->verantwortlich) : ?>
                    <?php if ($istVorschlag) : ?>
                        <?= Html::encode(sprintf(Texte::t('eingebracht_von'), $karte->verantwortlich->displayName)) ?>
                    <?php else : ?>
                        <?= Html::encode(Texte::t('verantwortlich')) ?>: <strong><?= Html::encode($karte->verantwortlich->displayName) ?></strong>
                    <?php endif; ?>
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

    <?php if ($istVorschlag) : ?>
        <?= $this->render('_vorschlag', ['kreis' => $kreis, 'karte' => $karte, 'ich' => $ich]) ?>
    <?php elseif ($istAuswahl) : ?>
        <?= $this->render('_auswahl', ['kreis' => $kreis, 'karte' => $karte, 'ich' => $ich]) ?>
    <?php endif; ?>

    <?php if ($karte->linkListe() !== []) : ?>
        <div class="nx-pj-unterlagen">
            <?php foreach ($karte->linkListe() as $l) : ?>
                <a href="<?= Html::encode($l['url']) ?>" <?= str_starts_with($l['url'], '/') ? '' : 'target="_blank" rel="noopener"' ?>><i class="fa fa-paperclip"></i> <?= Html::encode($l['titel']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    // Entwuerfe & Bilder: ueber Teilschritten und Abstimmung -- wer abstimmt, sieht sofort, worum es geht.
    // Bei der Bild-Auswahl sind die Bilder die Entwuerfe A, B, C … (_auswahl.php), nicht noch einmal hier.
    $fassungen = !$istVorschlag && !$istAuswahl && Dateien::verfuegbar() ? Dateien::fassungen($karte) : [];
    $darfHochladen = !$istVorschlag && !$istAuswahl && $karte->spalte !== 'fertig' && Rechte::darfAbhaken($karte, $ich) && Dateien::hochladbar($kreis);
    ?>
    <?php if ($fassungen !== [] || $darfHochladen) : ?>
        <div class="nx-pj-entwuerfe">
            <div class="nx-pj-label"><i class="fa fa-picture-o"></i> <?= Html::encode(Texte::t('entwuerfe')) ?></div>
            <?php if ($fassungen !== []) : ?>
                <?= $this->render('_dateien', ['fassungen' => $fassungen, 'karte' => $karte, 'ich' => $ich, 'url' => $url, 'lesen' => fn($d) => $url('datei-lesen', ['d' => $d->id])]) ?>
            <?php else : ?>
                <div class="nx-pj-leise"><?= Html::encode(Texte::t('entwuerfe_leer')) ?></div>
            <?php endif; ?>
            <?php if ($darfHochladen) : ?>
                <?php /* Texte fuer die Fortschrittsanzeige (projekte.js): {n} Anzahl, {mb} Groesse, {p} Prozent, {a}/{b} MB, {max} je Fassung */ ?>
                <?= Html::beginForm($url('dateien'), 'post', [
                    'enctype' => 'multipart/form-data', 'class' => 'nx-pj-hochladen',
                    'data-nx-pj-max' => \nexus\modules\projekte\models\Datei::MAX_JE_FASSUNG,
                    'data-nx-pj-t-gewaehlt' => Texte::t('upload_gewaehlt'), 'data-nx-pj-t-zuviele' => Texte::t('upload_zuviele'),
                    'data-nx-pj-t-laeuft' => Texte::t('upload_laeuft'), 'data-nx-pj-t-verarbeite' => Texte::t('upload_verarbeite'),
                    'data-nx-pj-t-fehler' => Texte::t('upload_fehler'),
                ]) ?>
                    <label for="nx-pj-entwurf-datei"><?= Html::encode(Texte::t($fassungen === [] ? 'entwuerfe_hochladen' : 'neue_fassung')) ?></label>
                    <input id="nx-pj-entwurf-datei" type="file" name="dateien[]" multiple required class="form-control">
                    <div class="nx-pj-gewaehlt nx-pj-leise" hidden></div>
                    <input type="text" name="fassung_hinweis" maxlength="255" class="form-control" placeholder="<?= Html::encode(Texte::t('fassung_hinweis')) ?>" aria-label="<?= Html::encode(Texte::t('fassung_hinweis')) ?>">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-upload"></i> <?= Html::encode(Texte::t('hochladen')) ?></button>
                    <div class="nx-pj-fortschritt" role="status" aria-live="polite" hidden>
                        <div class="nx-pj-balken"><span></span></div>
                        <div class="nx-pj-fortschritt-text"></div>
                    </div>
                <?= Html::endForm() ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($karte->schritte !== []) : ?>
        <div class="nx-pj-label" style="margin-top:.6rem"><?= Html::encode(Texte::t('teilschritte')) ?> <span class="nx-pj-leise" style="font-weight:normal">– <?= Html::encode(Texte::t('teilschritte_hinweis')) ?></span></div>
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

    <?php /* Rueckmeldungen frueherer Runden -- bleiben sichtbar, auch wenn die Kachel wieder in Arbeit ist */ ?>
    <?php $frueher = $istAuswahl ? [] : $karte->fruehereStimmen(); ?>
    <?php if ($frueher !== []) : ?>
        <?php $anzahlFrueher = array_sum(array_map('count', $frueher)); ?>
        <details class="nx-pj-frueher"<?= $karte->spalte !== 'abstimmung' ? ' open' : '' ?>>
            <summary><i class="fa fa-comments-o"></i> <strong><?= Html::encode(Texte::t('frueher_titel')) ?></strong> (<?= (int)$anzahlFrueher ?>)</summary>
            <?php if ($karte->spalte !== 'abstimmung') : ?>
                <div class="nx-pj-leise"><?= Html::encode(Texte::t('frueher_hinweis')) ?></div>
            <?php endif; ?>
            <?php foreach ($frueher as $runde => $liste) : ?>
                <?php
                $nachArtFrueher = [];
                foreach ($liste as $s) {
                    $nachArtFrueher[$s->art][] = $s;
                }
                $erste = $liste[0]->created_at ?? null;
                ?>
                <div class="nx-pj-runde">
                    <div class="nx-pj-label"><?= Html::encode(sprintf(Texte::t('frueher_runde'), $runde)) ?><?= $erste ? ' · ' . Html::encode(Texte::datum($erste, 'php:d.m.Y')) : '' ?></div>
                    <?php foreach ($optionen as $art => [$text, $symbol, $farbe, $mitText]) : ?>
                        <?php if (empty($nachArtFrueher[$art])) { continue; } ?>
                        <div class="<?= $farbe ?>" style="margin-top:.25rem"><i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode(Texte::t($text)) ?>:
                            <?= Html::encode(implode(', ', array_map($name, $nachArtFrueher[$art]))) ?></div>
                        <?php foreach ($nachArtFrueher[$art] as $s) : ?>
                            <?php if ($s->text) : ?><blockquote><strong><?= Html::encode($name($s)) ?>:</strong> <?= Html::encode((string)$s->text) ?></blockquote>
                                <?= $this->render('_antworten', ['kreis' => $kreis, 'karte' => $karte, 'ich' => $ich, 's' => $s]) ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php /* Stimmen einer Art, die die heutige Regel nicht mehr kennt (Regel des Projekts geaendert): trotzdem zeigen */ ?>
                    <?php foreach ($nachArtFrueher as $art => $stimmenDerArt) : ?>
                        <?php if (isset($optionen[$art])) { continue; } ?>
                        <?php foreach ($stimmenDerArt as $s) : ?>
                            <div style="margin-top:.25rem"><?= Html::encode($name($s)) ?><?= $s->text ? ': ' . Html::encode((string)$s->text) : '' ?></div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </details>
    <?php endif; ?>

    <?php if ($karte->spalte === 'abstimmung' && !$istAuswahl) : ?>
        <div class="nx-pj-abstimmung">
            <?php if ($karte->abstimmung_bis) : ?>
                <div class="nx-pj-leise"><?= Html::encode(Texte::t('rueckmeldung_bis')) ?> <?= Html::encode(Texte::datum($karte->abstimmung_bis, 'php:D d.m.Y')) ?></div>
            <?php endif; ?>
            <?php if ($regel !== 'einfach') : ?>
                <div class="nx-pj-leise" style="margin:.2rem 0 .4rem"><?= Html::encode(Texte::t('erklaerung_' . $regel)) ?></div>
            <?php endif; ?>
            <?php foreach ($optionen as $art => [$text, $symbol, $farbe, $mitText]) : ?>
                <?php if ($nachArt[$art] === [] && $mitText) { continue; } ?>
                <div class="<?= $farbe ?>" style="margin-top:.3rem"><i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode(Texte::t($text)) ?>:
                    <?= Html::encode($nachArt[$art] === [] ? '–' : implode(', ', array_map($name, $nachArt[$art]))) ?></div>
                <?php foreach ($nachArt[$art] as $s) : ?>
                    <?php if ($s->text) : ?><blockquote><strong><?= Html::encode($name($s)) ?>:</strong> <?= Html::encode((string)$s->text) ?></blockquote>
                        <?= $this->render('_antworten', ['kreis' => $kreis, 'karte' => $karte, 'ich' => $ich, 's' => $s]) ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if ($fehlen !== []) : ?>
                <div class="nx-pj-leise" style="margin-top:.35rem"><i class="fa fa-clock-o"></i> <?= Html::encode(Texte::t('fehlt_noch')) ?>: <?= Html::encode(implode(', ', array_map(fn($u) => $u->displayName, $fehlen))) ?></div>
            <?php endif; ?>

            <?php if (Rechte::darfAbstimmen($karte, $ich)) : ?>
                <?php if ($meine !== null && isset($optionen[$meine->art])) : ?>
                    <div style="margin-top:.5rem"><strong><?= Html::encode(sprintf(Texte::t('deine_stimme'), Texte::t($optionen[$meine->art][0]))) ?></strong></div>
                <?php endif; ?>
                <div class="nx-pj-knoepfe">
                    <?php foreach ($optionen as $art => [$text, $symbol, $farbe, $mitText]) : ?>
                        <?php if ($mitText) : ?>
                            <button type="button" class="btn btn-sm btn-default" data-nx-pj-oeffne="nx-pj-stimme-<?= $art ?>"><i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode(Texte::t($text)) ?></button>
                        <?php else : ?>
                            <?= Html::beginForm($url('stimme'), 'post') ?>
                                <?= Html::hiddenInput('art', $art) ?>
                                <button type="submit" class="btn btn-sm <?= Entscheidung::istPositiv($art) ? 'btn-success' : 'btn-default' ?>"><i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode(Texte::t($text)) ?></button>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($optionen as $art => [$text, $symbol, $farbe, $mitText]) : ?>
                    <?php if (!$mitText) { continue; } ?>
                    <div id="nx-pj-stimme-<?= $art ?>" hidden style="margin-top:.6rem">
                        <?= Html::beginForm($url('stimme'), 'post') ?>
                            <?= Html::hiddenInput('art', $art) ?>
                            <label class="nx-pj-label" for="nx-pj-stimme-text-<?= $art ?>"><?= Html::encode(Texte::t($text)) ?> – <?= Html::encode(Texte::t($regel === 'einfach' ? 'aenderung_text' : 'stellung_text')) ?></label>
                            <textarea id="nx-pj-stimme-text-<?= $art ?>" class="form-control" name="text" rows="3" required minlength="5" maxlength="3000"><?= Html::encode($meine && $meine->art === $art ? (string)$meine->text : '') ?></textarea>
                            <div class="nx-pj-knoepfe"><button type="submit" class="btn btn-primary btn-sm"><?= Html::encode(Texte::t('absenden')) ?></button></div>
                        <?= Html::endForm() ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (Rechte::darfEntscheiden($karte, $ich)) : ?>
                <?php $auswertung = Entscheidung::auswertung($karte); ?>
                <div class="nx-pj-auswerten">
                    <div class="nx-pj-label"><?= Html::encode(Texte::t('runde_auswerten')) ?></div>
                    <div class="nx-pj-leise"><?= Html::encode(sprintf(Texte::t('stand_jetzt'), Texte::t('ergebnis_' . $auswertung['ergebnis']) . ' – ' . $auswertung['text'])) ?></div>
                    <?php if ($auswertung['ergebnis'] !== 'angenommen' && $regel === 'konsent') : ?>
                        <div class="nx-pj-leise nx-pj-gelb"><?= Html::encode(Texte::t('einwand_blockiert')) ?></div>
                    <?php endif; ?>
                    <div class="nx-pj-knoepfe">
                        <?php foreach (['annehmen' => ['check', 'btn-success'], 'ablehnen' => ['times', 'btn-default'], 'ueberarbeiten' => ['undo', 'btn-light']] as $wahl => [$symbol, $klasse]) : ?>
                            <?php if ($wahl === 'annehmen' && $regel !== 'einfach' && $auswertung['ergebnis'] !== 'angenommen') { continue; } ?>
                            <?= Html::beginForm($vUrl('auswerten'), 'post') ?>
                                <?= Html::hiddenInput('entscheidung', $wahl) ?>
                                <button type="submit" class="btn btn-sm <?= $klasse ?>"><i class="fa fa-<?= $symbol ?>"></i> <?= Html::encode(Texte::t($wahl)) ?></button>
                            <?= Html::endForm() ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="nx-pj-knoepfe">
        <?php if (Rechte::darfUebernehmen($karte, $ich) && !$istVorschlag) : ?>
            <?= Html::beginForm($url('uebernehmen'), 'post') ?>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-hand-paper-o"></i> <?= Html::encode(Texte::t('uebernehmen')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>
        <?php if ($karte->spalte === 'arbeit' || $karte->spalte === 'offen') : ?>
            <?php if (Rechte::darfVerschieben($karte, 'abstimmung', $ich)) : ?>
                <?php /* Rueckfrage: ein Klick stellt die Kachel zur Abstimmung UND schreibt einen Beitrag in den Kreis */ ?>
                <?= Html::beginForm($url('verschieben'), 'post', ['style' => 'display:inline-flex;gap:.4rem;align-items:center;flex-wrap:wrap',
                    'data-nx-pj-frage' => Texte::t($istAuswahl ? 'auswahl_abstimmung_frage' : ($fassungen === [] && !$istVorschlag ? 'zur_abstimmung_frage_ohne' : 'zur_abstimmung_frage'))]) ?>
                    <?= Html::hiddenInput('ziel', 'abstimmung') ?>
                    <label class="nx-pj-leise" for="nx-pj-bis" style="margin:0"><?= Html::encode(Texte::t('rueckmeldung_bis')) ?></label>
                    <input id="nx-pj-bis" type="date" name="abstimmung_bis" class="form-control input-sm" style="width:auto" value="<?= date('Y-m-d', strtotime('+' . Entscheidung::rundenTage($karte) . ' days')) ?>" min="<?= date('Y-m-d') ?>">
                    <button type="submit" class="btn btn-sm btn-default" title="<?= Html::encode(Texte::t('abstimmung_hinweis')) ?>"><i class="fa fa-thumbs-o-up"></i> <?= Html::encode(Texte::t('zur_abstimmung')) ?></button>
                <?= Html::endForm() ?>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($karte->spalte === 'abstimmung' && !$istVorschlag && !$istAuswahl) : ?>
            <?= $knopf('arbeit', 'in_arbeit', 'undo') ?>
        <?php endif; ?>
        <?php if ($karte->spalte !== 'fertig') : ?>
            <?= $knopf('fertig', 'als_fertig', 'check', 'btn-success') ?>
        <?php else : ?>
            <?= $knopf('arbeit', 'wieder_oeffnen', 'undo') ?>
        <?php endif; ?>
        <?php if ($istVorschlag ? Rechte::darfVorschlagBearbeiten($karte, $ich) : Rechte::darfKarteBearbeiten($karte, $ich)) : ?>
            <a class="btn btn-sm btn-light" href="<?= Html::encode($istVorschlag ? $kreis->createUrl('/nexus-projekte/vorschlag/formular', ['p' => $karte->projekt_id, 'k' => $karte->id]) : $url('karte', ['p' => $karte->projekt_id])) ?>"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t($istVorschlag ? 'vorschlag_bearbeiten' : 'bearbeiten')) ?></a>
        <?php endif; ?>
    </div>
</div>
