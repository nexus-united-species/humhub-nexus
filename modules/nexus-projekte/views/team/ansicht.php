<?php

/**
 * Teamseite: Auftrag, Mitmachen, Anfragen, Aufgaben mit Zustaendigkeit und Vertretung, Arbeitsweise,
 * Abwesenheit, Team-Postfach.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $team nexus\modules\projekte\models\Team
 * @var $ich humhub\modules\user\models\User
 * @var $meine nexus\modules\projekte\models\TeamMitglied|null
 * @var $mitglieder nexus\modules\projekte\models\TeamMitglied[]
 * @var $anfragen nexus\modules\projekte\models\TeamMitglied[]
 * @var $posts nexus\modules\projekte\models\TeamPost[]
 * @var $meldung string|null
 */

use humhub\helpers\Html;
use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\services\Teams;
use nexus\modules\projekte\services\Texte;

$this->title = $team->titel;
$darfLeiten = Teams::darfLeiten($team, $ich);
$istMitglied = Teams::istMitglied($team, $ich);
$darfPostfach = Teams::darfPostfach($team, $ich);
$aufgaben = $team->aufgaben;
$projekt = $team->projekt_id ? Projekt::findOne($team->projekt_id) : null;
$leitung = Teams::leitung($team);

$person = static function (?User $u): string {
    if ($u === null) {
        return '';
    }
    return Html::a(Html::img($u->getProfileImage()->getUrl(), ['class' => 'nx-pj-avatar', 'alt' => '']) . Html::encode($u->displayName), $u->getUrl());
};
$knopf = static function (string $url, string $text, array $felder = [], string $klasse = 'btn btn-light btn-sm', ?string $frage = null): string {
    $html = Html::beginForm($url, 'post', $frage ? ['data-nx-pj-frage' => $frage] : []);
    foreach ($felder as $name => $wert) {
        $html .= Html::hiddenInput($name, $wert);
    }
    return $html . Html::submitButton(Html::encode($text), ['class' => $klasse]) . Html::endForm();
};
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <?= $this->render('_reiter', ['kreis' => $kreis, 'aktiv' => 'teams']) ?>
        <div class="nx-pj-kopf">
            <div>
                <span class="nx-pj-team-status nx-pj-team-<?= Html::encode($team->status) ?>"><?= Html::encode(Texte::t('team_status_' . $team->status)) ?></span>
                <h1><i class="fa fa-users"></i> <?= Html::encode($team->titel) ?></h1>
                <span class="nx-pj-leise">
                    <?= Html::encode(Texte::t('leitung')) ?>: <?= implode(', ', array_map($person, $leitung)) ?: '–' ?>
                    <?php if ($projekt !== null) : ?> · <a href="<?= Html::encode($projekt->url()) ?>"><i class="fa fa-columns"></i> <?= Html::encode(Texte::t('team_tafel')) ?></a><?php endif; ?>
                </span>
            </div>
            <div class="nx-pj-knoepfe" style="margin:0">
                <?php if ($darfLeiten) : ?>
                    <a class="btn btn-light btn-sm" href="<?= Html::encode($team->url('formular')) ?>"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t('bearbeiten')) ?></a>
                <?php endif; ?>
                <a class="btn btn-light btn-sm" href="<?= Html::encode($kreis->createUrl('/nexus-projekte/team/index')) ?>"><?= Html::encode(Texte::t('zurueck')) ?></a>
            </div>
        </div>
        <?php if ($meldung) : ?><div class="nx-pj-meldung"><?= Html::encode($meldung) ?></div><?php endif; ?>

        <?php if ($team->auftrag) : ?>
            <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t('team_auftrag')) ?></h2>
            <div class="nx-pj-roh"><?= Html::encode($team->auftrag) ?></div>
        <?php endif; ?>

        <?php /* ── Mitmachen ── */ ?>
        <?php if ($meine !== null && $meine->status === 'angefragt') : ?>
            <div class="nx-pj-hinweis"><?= Html::encode(Texte::t('team_anfrage_laeuft')) ?></div>
        <?php elseif (Teams::darfMitmachen($team, $ich)) : ?>
            <details class="nx-pj-detail nx-pj-team-mitmachen" <?= $team->status === 'suchend' ? 'open' : '' ?>>
                <summary><strong><i class="fa fa-hand-paper-o"></i> <?= Html::encode(Texte::t('team_helfe_mit')) ?></strong></summary>
                <?= Html::beginForm($team->url('mitmachen'), 'post') ?>
                    <?php if ($aufgaben !== []) : ?>
                        <p class="nx-pj-label" style="margin-top:.6rem"><?= Html::encode(Texte::t('team_welche_aufgaben')) ?></p>
                        <?php foreach ($aufgaben as $a) : ?>
                            <?php if ($a->erledigt) { continue; } ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="aufgaben[]" value="<?= (int)$a->id ?>" id="nx-tm-a<?= (int)$a->id ?>">
                                <label class="form-check-label" for="nx-tm-a<?= (int)$a->id ?>"><?= Html::encode($a->titel) ?>
                                    <span class="nx-pj-leise">(<?= Html::encode(Texte::t('team_art_' . $a->art)) ?><?= $a->aufwand ? ', ' . Html::encode($a->aufwand) : '' ?><?= $a->zustaendig_id ? ', ' . Html::encode(Texte::t('team_schon_vergeben')) : '' ?>)</span></label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="nx-pj-felder" style="margin-top:.6rem">
                        <div><label for="nx-tm-zeit"><?= Html::encode(Texte::t('team_meine_zeit')) ?></label>
                            <input id="nx-tm-zeit" class="form-control" name="zeit" maxlength="190" placeholder="<?= Html::encode(Texte::t('team_zeit_beispiel')) ?>"></div>
                        <div class="nx-pj-ganz"><label for="nx-tm-satz"><?= Html::encode(Texte::t('team_ein_satz')) ?></label>
                            <textarea id="nx-tm-satz" class="form-control" name="nachricht" rows="2" maxlength="3000"></textarea></div>
                    </div>
                    <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_mitmachen_hinweis')) ?></p>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> <?= Html::encode(Texte::t('team_anfrage_senden')) ?></button>
                <?= Html::endForm() ?>
            </details>
        <?php elseif ($meine === null && $team->status !== 'pausiert' && !nexus\modules\projekte\services\Rechte::istMitglied($kreis, $ich)) : ?>
            <div class="nx-pj-hinweis"><?= Html::encode(Texte::t('team_kreis_beitreten')) ?></div>
        <?php endif; ?>

        <?php /* ── Anfragen (nur Leitung) ── */ ?>
        <?php if ($anfragen !== []) : ?>
            <h2 class="nx-pj-abschnitt"><i class="fa fa-user-plus"></i> <?= Html::encode(sprintf(Texte::t('team_anfragen_n'), count($anfragen))) ?></h2>
            <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_anfragen_hilfe')) ?></p>
            <?php foreach ($anfragen as $m) : ?>
                <?php $wunsch = array_filter($aufgaben, static fn($a) => in_array((int)$a->id, $m->aufgabenIds(), true)); ?>
                <div class="nx-pj-abstimmung">
                    <strong><?= $person($m->user) ?></strong> <span class="nx-pj-leise">· <?= Html::encode(Texte::datum($m->created_at)) ?></span>
                    <div><?= Html::encode(Texte::t('team_aufgaben')) ?>: <?= Html::encode($wunsch === [] ? '–' : implode(', ', array_map(static fn($a) => $a->titel, $wunsch))) ?></div>
                    <div><?= Html::encode(Texte::t('team_meine_zeit')) ?>: <?= Html::encode($m->zeit ?: '–') ?></div>
                    <?php if ($m->nachricht) : ?><blockquote><?= Html::encode($m->nachricht) ?></blockquote><?php endif; ?>
                    <div class="nx-pj-knoepfe">
                        <?= $knopf($team->url('entscheiden', ['m' => $m->id]), '✓ ' . Texte::t('team_aufnehmen'), ['aufnehmen' => '1'], 'btn btn-primary btn-sm') ?>
                        <?= $knopf($team->url('entscheiden', ['m' => $m->id]), Texte::t('team_absagen'), ['aufnehmen' => '0'], 'btn btn-default btn-sm', Texte::t('team_absagen_frage')) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php /* ── Aufgaben ── */ ?>
        <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t('team_wer_macht_was')) ?></h2>
        <?php if ($aufgaben === []) : ?>
            <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_keine_aufgaben')) ?></p>
        <?php else : ?>
            <ul class="nx-pj-liste nx-pj-team-aufgaben">
                <?php foreach ($aufgaben as $a) : ?>
                    <li class="<?= $a->erledigt ? 'nx-pj-durch' : '' ?>">
                        <div>
                            <strong><?= Html::encode($a->titel) ?></strong>
                            <span class="nx-pj-leise">· <?= Html::encode(Texte::t('team_art_' . $a->art)) ?><?= $a->aufwand ? ' · ' . Html::encode($a->aufwand) : '' ?><?= $a->erledigt ? ' · ' . Html::encode(Texte::t('team_erledigt')) : '' ?></span>
                            <div class="nx-pj-team-rollen">
                                <?php foreach (['zustaendig', 'vertretung'] as $rolle) : ?>
                                    <?php $wer = $rolle === 'vertretung' ? $a->vertretung : $a->zustaendig; ?>
                                    <span><?= Html::encode(Texte::t('team_' . $rolle)) ?>:
                                        <?php if ($wer !== null) : ?>
                                            <?= $person($wer) ?>
                                            <?php if ((int)$wer->id === (int)$ich->id || $darfLeiten) : ?>
                                                <?= $knopf($team->url('aufgabe', ['a' => $a->id]), '×', ['rolle' => $rolle, 'an' => '0'], 'btn btn-link btn-sm nx-pj-team-weg', Texte::t('team_austragen_frage')) ?>
                                            <?php endif; ?>
                                        <?php elseif ($istMitglied && !$a->erledigt) : ?>
                                            <?= $knopf($team->url('aufgabe', ['a' => $a->id]), Texte::t($rolle === 'vertretung' ? 'team_ich_vertrete' : 'team_ich_uebernehme'), ['rolle' => $rolle, 'an' => '1']) ?>
                                        <?php else : ?>
                                            <span class="nx-pj-gelb"><?= Html::encode(Texte::t('team_frei')) ?></span>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php if ($a->art === 'einmalig' && $darfPostfach) : ?>
                            <?= $knopf($team->url('erledigt', ['a' => $a->id]), $a->erledigt ? Texte::t('wieder_oeffnen') : '✓ ' . Texte::t('team_erledigt')) ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php /* ── Mitglieder ── */ ?>
        <h2 class="nx-pj-abschnitt"><?= Html::encode(sprintf(Texte::t('team_mitglieder_n'), count($mitglieder))) ?></h2>
        <?php if ($mitglieder === []) : ?>
            <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_noch_niemand')) ?></p>
        <?php else : ?>
            <ul class="nx-pj-team-mitglieder">
                <?php foreach ($mitglieder as $m) : ?>
                    <li><?= $person($m->user) ?>
                        <?php if ((int)$team->koordination_id === (int)$m->user_id) : ?><span class="nx-pj-team-marke"><?= Html::encode(Texte::t('team_koordination')) ?></span><?php endif; ?>
                        <?php if ($m->istWeg()) : ?><span class="nx-pj-leise nx-pj-gelb">· <?= Html::encode(sprintf(Texte::t('team_weg_bis'), Texte::datum($m->weg_bis))) ?></span><?php endif; ?>
                        <?php if ($m->zeit) : ?><span class="nx-pj-leise">· <?= Html::encode($m->zeit) ?></span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($darfLeiten) : ?>
            <div class="nx-pj-zeile">
                <?= Html::beginForm($team->url('koordination'), 'post', ['class' => 'nx-pj-zeile', 'style' => 'margin:0']) ?>
                    <label class="nx-pj-label" for="nx-tm-koord" style="margin:0"><?= Html::encode(Texte::t('team_koordination')) ?>:</label>
                    <select id="nx-tm-koord" class="form-control" name="user">
                        <option value="0"><?= Html::encode(Texte::t('team_keine_koordination')) ?></option>
                        <?php foreach ($mitglieder as $m) : ?>
                            <?php if ($m->user === null) { continue; } ?>
                            <option value="<?= (int)$m->user_id ?>" <?= (int)$team->koordination_id === (int)$m->user_id ? 'selected' : '' ?>><?= Html::encode($m->user->displayName) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-light btn-sm"><?= Html::encode(Texte::t('speichern')) ?></button>
                <?= Html::endForm() ?>
            </div>
            <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_koordination_hilfe')) ?></p>
        <?php endif; ?>

        <?php /* ── Arbeitsweise ── */ ?>
        <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t('team_arbeitsweise')) ?></h2>
        <?php if ($darfPostfach) : ?>
            <?= Html::beginForm($team->url('arbeitsweise'), 'post') ?>
                <textarea class="form-control" name="arbeitsweise" rows="7" maxlength="10000" aria-label="<?= Html::encode(Texte::t('team_arbeitsweise')) ?>"><?= Html::encode($team->arbeitsweise ?: Texte::t('team_arbeitsweise_vorlage')) ?></textarea>
                <div class="nx-pj-knoepfe"><button type="submit" class="btn btn-light btn-sm"><i class="fa fa-save"></i> <?= Html::encode(Texte::t('speichern')) ?></button>
                    <span class="nx-pj-leise"><?= Html::encode(Texte::t('team_arbeitsweise_hilfe')) ?></span></div>
            <?= Html::endForm() ?>
        <?php elseif ($team->arbeitsweise) : ?>
            <div class="nx-pj-roh"><?= Html::encode($team->arbeitsweise) ?></div>
        <?php else : ?>
            <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_arbeitsweise_leer')) ?></p>
        <?php endif; ?>

        <?php /* ── Fuer mich als Mitglied ── */ ?>
        <?php if ($meine !== null && $meine->status === 'dabei') : ?>
            <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t('team_fuer_mich')) ?></h2>
            <div class="nx-pj-zeile">
                <?php if ($meine->istWeg()) : ?>
                    <span><?= Html::encode(sprintf(Texte::t('team_du_bist_weg'), Texte::datum($meine->weg_bis))) ?></span>
                    <?= $knopf($team->url('weg'), Texte::t('team_wieder_da'), ['bis' => '']) ?>
                <?php else : ?>
                    <?= Html::beginForm($team->url('weg'), 'post', ['class' => 'nx-pj-zeile', 'style' => 'margin:0']) ?>
                        <label class="nx-pj-label" for="nx-tm-weg" style="margin:0"><?= Html::encode(Texte::t('team_ich_bin_weg')) ?></label>
                        <input id="nx-tm-weg" class="form-control" type="date" name="bis" min="<?= date('Y-m-d') ?>" required>
                        <button type="submit" class="btn btn-light btn-sm"><?= Html::encode(Texte::t('speichern')) ?></button>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
            <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_weg_hilfe')) ?></p>
            <div class="nx-pj-knoepfe">
                <?= $knopf($team->url('aufhoeren'), Texte::t('team_ich_hoere_auf'), [], 'btn btn-default btn-sm', Texte::t('team_aufhoeren_frage')) ?>
            </div>
        <?php endif; ?>

        <?php /* ── Team-Postfach ── */ ?>
        <?php if (Teams::mailDa() && (Teams::darfAnschreiben($team, $ich) || $darfPostfach)) : ?>
            <h2 class="nx-pj-abschnitt"><i class="fa fa-envelope-o"></i> <?= Html::encode(Texte::t('team_postfach')) ?></h2>
            <?php if (Teams::darfAnschreiben($team, $ich)) : ?>
                <details class="nx-pj-detail" style="margin-top:.4rem">
                    <summary><strong><?= Html::encode(Texte::t('team_nachricht_an')) ?></strong></summary>
                    <?= Html::beginForm($team->url('schreiben'), 'post') ?>
                        <div class="nx-pj-felder" style="margin-top:.6rem">
                            <div class="nx-pj-ganz"><label for="nx-tm-betreff"><?= Html::encode(Texte::t('team_betreff')) ?></label>
                                <input id="nx-tm-betreff" class="form-control" name="betreff" maxlength="200" required></div>
                            <div class="nx-pj-ganz"><label for="nx-tm-text"><?= Html::encode(Texte::t('team_text')) ?></label>
                                <textarea id="nx-tm-text" class="form-control" name="text" rows="4" required minlength="5"></textarea></div>
                        </div>
                        <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_nachricht_hilfe')) ?></p>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-paper-plane"></i> <?= Html::encode(Texte::t('absenden')) ?></button>
                    <?= Html::endForm() ?>
                </details>
            <?php endif; ?>

            <?php if ($darfPostfach) : ?>
                <div class="nx-pj-knoepfe">
                    <a class="btn btn-light btn-sm" href="<?= Html::encode($team->url('uebergeben')) ?>"><i class="fa fa-share"></i> <?= Html::encode(Texte::t('team_uebergeben')) ?></a>
                </div>
                <?php if ($posts === []) : ?>
                    <p class="nx-pj-leise" style="margin-top:.6rem"><?= Html::encode(Texte::t('team_postfach_leer')) ?></p>
                <?php else : ?>
                    <ul class="nx-pj-liste nx-pj-team-post">
                        <?php foreach ($posts as $p) : ?>
                            <li class="<?= $p->status === 'erledigt' ? 'nx-pj-durch' : '' ?>">
                                <div>
                                    <a href="<?= Html::encode($p->adresse()) ?>"><strong><?= Html::encode($p->betreff) ?></strong></a>
                                    <div class="nx-pj-leise">
                                        <span class="nx-pj-team-post-<?= Html::encode($p->status) ?>"><?= Html::encode(Texte::t('team_post_' . $p->status)) ?></span>
                                        <?php if ($p->status === 'uebernommen' && $p->bearbeiter !== null) : ?> <?= Html::encode($p->bearbeiter->displayName) ?><?php endif; ?>
                                        · <?= Html::encode(Texte::t($p->quelle === 'uebergabe' ? 'team_quelle_uebergabe' : 'team_quelle_team')) ?> <?= Html::encode($p->absender?->displayName ?? '?') ?>
                                        · <?= Html::encode(Texte::datum($p->created_at)) ?>
                                    </div>
                                </div>
                                <div class="nx-pj-knoepfe" style="margin:0;justify-content:flex-end">
                                    <?php if (in_array($p->status, ['offen', 'kreisleitung'], true)) : ?>
                                        <?= $knopf($team->url('post', ['p' => $p->id]), Texte::t('team_ich_uebernehme_post'), ['aktion' => 'uebernehmen'], 'btn btn-primary btn-sm') ?>
                                    <?php endif; ?>
                                    <?php if ($p->status !== 'erledigt') : ?>
                                        <?= $knopf($team->url('nova', ['p' => $p->id]), '✨ ' . Texte::t('team_nova'), [], 'btn btn-light btn-sm') ?>
                                        <?= $knopf($team->url('post', ['p' => $p->id]), '✓ ' . Texte::t('team_erledigt'), ['aktion' => 'erledigt']) ?>
                                    <?php endif; ?>
                                    <?php if (!in_array($p->status, ['kreisleitung', 'erledigt'], true)) : ?>
                                        <?= $knopf($team->url('post', ['p' => $p->id]), Texte::t('team_braucht_leitung'), ['aktion' => 'kreisleitung'], 'btn btn-default btn-sm', Texte::t('team_braucht_leitung_frage')) ?>
                                    <?php endif; ?>
                                    <?php if (in_array($p->status, ['uebernommen', 'erledigt'], true)) : ?>
                                        <?= $knopf($team->url('post', ['p' => $p->id]), Texte::t('team_wieder_offen'), ['aktion' => 'oeffnen'], 'btn btn-link btn-sm') ?>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
