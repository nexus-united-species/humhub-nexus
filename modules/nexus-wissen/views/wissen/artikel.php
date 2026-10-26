<?php

/**
 * Artikelseite: Inhaltsverzeichnis, Text mit Querverweisen, Infokasten, "Siehe auch",
 * Hinweis der Sammlung, Vorschlaege und "Im Kreis darueber sprechen".
 *
 * @var $sammlung nexus\modules\wissen\services\Sammlung
 * @var $artikel nexus\modules\wissen\models\Artikel
 * @var $darstellung array{html: string, inhalt: array, quellen: int}
 * @var $schlagworte string[]
 * @var $verwandt array
 * @var $darfBearbeiten bool
 * @var $darfVorschlagen bool
 * @var $teilenKreise array<int, array{id: int, name: string}>  Kreise, in die die Person teilen kann
 * @var $oeffentlichMoeglich bool
 * @var $meldung string|null
 */

use humhub\helpers\Html;
use nexus\modules\wissen\services\Texte;

$datum = fn(?string $d) => $d ? Yii::$app->formatter->asDate($d, 'long') : '';
$this->title = $artikel->titel;
?>
<div class="panel panel-default nx-ws">
    <div class="panel-body">
        <div class="nx-ws-pfad">
            <a href="<?= Html::encode($sammlung->url()) ?>"><?= Html::encode($sammlung->name()) ?></a>
            <?php if ($artikel->thema !== '') : ?>
                › <a href="<?= Html::encode($sammlung->url('index', ['thema' => $artikel->thema])) ?>"><?= Html::encode($sammlung->themaName($artikel->thema)) ?></a>
            <?php endif; ?>
        </div>
        <h1 style="font-size:1.6rem;margin:0 0 1rem"><?= Html::encode($artikel->titel) ?></h1>

        <?php if ($meldung) : ?>
            <div class="nx-ws-meldung"><?= Html::encode($meldung) ?></div>
        <?php endif; ?>

        <?php if ($darfBearbeiten && $artikel->entwurf) : ?>
            <div class="nx-ws-entwurf">
                <span><i class="fa fa-pencil-square-o"></i> <?= Html::encode(Texte::t('entwurf_hinweis')) ?></span>
                <?= Html::beginForm($sammlung->url('redaktion/veroeffentlichen', ['a' => $artikel->slug]), 'post', ['style' => 'display:inline']) ?>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-check"></i> <?= Html::encode(Texte::t('veroeffentlichen')) ?></button>
                <?= Html::endForm() ?>
                <?= Html::beginForm($sammlung->url('redaktion/verwerfen', ['a' => $artikel->slug]), 'post', ['style' => 'display:inline']) ?>
                    <button type="submit" class="btn btn-sm btn-light" data-nx-ws-bestaetigen="<?= Html::encode(Texte::t('verwerfen_frage')) ?>"><i class="fa fa-trash"></i> <?= Html::encode(Texte::t('verwerfen')) ?></button>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>

        <?php if ($darfBearbeiten) : ?>
            <div class="nx-ws-admin">
                <a href="<?= Html::encode($sammlung->url('bearbeiten', ['a' => $artikel->slug])) ?>"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t('bearbeiten')) ?></a>
                <a href="<?= Html::encode($sammlung->url('versionen', ['a' => $artikel->slug])) ?>"><i class="fa fa-history"></i> <?= Html::encode(Texte::t('versionen')) ?></a>
                <?php if (!$artikel->entwurf) : ?>
                    <?= Html::beginForm($sammlung->url('redaktion/zum-entwurf', ['a' => $artikel->slug]), 'post', ['style' => 'display:inline']) ?>
                        <button type="submit" class="btn btn-sm btn-light" data-nx-ws-bestaetigen="<?= Html::encode(Texte::t('zum_entwurf_frage')) ?>"><i class="fa fa-eye-slash"></i> <?= Html::encode(Texte::t('zum_entwurf')) ?></button>
                    <?= Html::endForm() ?>
                    <?php /* "Löschen" soll man finden (Rueckmeldung aus dem Team): Schritt 1 macht ihn zum Entwurf, Schritt 2 im blauen Kasten */ ?>
                    <?= Html::beginForm($sammlung->url('redaktion/zum-entwurf', ['a' => $artikel->slug, 'loeschen' => 1]), 'post', ['style' => 'display:inline']) ?>
                        <button type="submit" class="btn btn-sm btn-light" data-nx-ws-bestaetigen="<?= Html::encode(Texte::t('loeschen_artikel_frage')) ?>"><i class="fa fa-trash"></i> <?= Html::encode(Texte::t('loeschen')) ?></button>
                    <?= Html::endForm() ?>
                <?php endif; ?>
                <?php if ($artikel->kennung) : ?><span class="nx-ws-leise"><?= Html::encode($artikel->kennung) ?></span><?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$artikel->entwurf) : ?>
            <?php /* Teilen: alle Mitglieder -- Link weitergeben, in einen anderen Kreis; die Redaktion zusaetzlich oeffentlich lesbar machen */ ?>
            <div class="nx-ws-teilen">
                <button type="button" class="btn btn-default btn-sm" data-nx-ws-kopieren="<?= Html::encode($artikel->url($sammlung, true)) ?>"
                        data-nx-ws-titel="<?= Html::encode($artikel->titel) ?>" data-nx-ws-kopiert="<?= Html::encode(Texte::t('link_kopiert')) ?>"
                        data-nx-ws-feld="nx-ws-linkfeld">
                    <i class="fa fa-share-alt"></i> <span><?= Html::encode(Texte::t('weitergeben')) ?></span>
                </button>
                <?php if ($teilenKreise !== []) : ?>
                    <button type="button" class="btn btn-default btn-sm" data-nx-ws-oeffne="nx-ws-teilen-kreis"><i class="fa fa-comments"></i> <?= Html::encode(Texte::t('teilen_kreis')) ?></button>
                <?php endif; ?>
                <?php if ($darfBearbeiten && $oeffentlichMoeglich && !$artikel->istOeffentlich()) : ?>
                    <?= Html::beginForm($sammlung->url('oeffentlich', ['a' => $artikel->slug]), 'post', ['style' => 'display:inline']) ?>
                        <?= Html::hiddenInput('an', '1') ?>
                        <button type="submit" class="btn btn-default btn-sm" data-nx-ws-bestaetigen="<?= Html::encode(Texte::t('oeffentlich_frage')) ?>"><i class="fa fa-globe"></i> <?= Html::encode(Texte::t('oeffentlich_machen')) ?></button>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
            <div class="nx-ws-linkzeile" id="nx-ws-linkfeld" hidden>
                <label><?= Html::encode(Texte::t('link_manuell')) ?> <input type="text" class="form-control" readonly value="<?= Html::encode($artikel->url($sammlung, true)) ?>"></label>
            </div>
            <?php if ($teilenKreise !== []) : ?>
                <div class="nx-ws-formular" id="nx-ws-teilen-kreis" hidden>
                    <?= Html::beginForm($sammlung->url('teilen', ['a' => $artikel->slug]), 'post') ?>
                        <label for="nx-ws-teilen-ziel"><?= Html::encode(Texte::t('teilen_kreis_waehlen')) ?></label>
                        <select id="nx-ws-teilen-ziel" name="kreis" class="form-control" required>
                            <?php foreach ($teilenKreise as $k) : ?>
                                <option value="<?= (int)$k['id'] ?>"><?= Html::encode($k['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="nx-ws-teilen-text"><?= Html::encode(Texte::t('teilen_satz')) ?></label>
                        <textarea id="nx-ws-teilen-text" name="text" class="form-control" maxlength="1000"></textarea>
                        <p class="nx-ws-leise"><?= Html::encode(Texte::t('teilen_hinweis')) ?></p>
                        <div class="nx-ws-knoepfe">
                            <button type="submit" class="btn btn-primary"><?= Html::encode(Texte::t('teilen_senden')) ?></button>
                            <button type="button" class="btn btn-light" data-nx-ws-schliesse><?= Html::encode(Texte::t('abbrechen')) ?></button>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            <?php endif; ?>
            <?php if ($artikel->istOeffentlich() && $darfBearbeiten) : ?>
                <?php /* Nur die Redaktion sieht den geheimen Link; alle anderen sehen nichts davon */ ?>
                <div class="nx-ws-oeffentlich">
                    <span><i class="fa fa-globe"></i> <?= Html::encode(Texte::t('oeffentlich_ist')) ?></span>
                    <label class="nx-ws-linkzeile"><?= Html::encode(Texte::t('oeffentlich_link')) ?>
                        <input type="text" class="form-control" readonly value="<?= Html::encode($artikel->oeffentlicheAdresse()) ?>">
                    </label>
                    <button type="button" class="btn btn-default btn-sm" data-nx-ws-kopieren="<?= Html::encode($artikel->oeffentlicheAdresse()) ?>"
                            data-nx-ws-titel="<?= Html::encode($artikel->titel) ?>" data-nx-ws-kopiert="<?= Html::encode(Texte::t('link_kopiert')) ?>">
                        <i class="fa fa-share-alt"></i> <span><?= Html::encode(Texte::t('weitergeben')) ?></span>
                    </button>
                    <?= Html::beginForm($sammlung->url('oeffentlich', ['a' => $artikel->slug]), 'post', ['style' => 'display:inline']) ?>
                        <?= Html::hiddenInput('an', '0') ?>
                        <button type="submit" class="btn btn-light btn-sm" data-nx-ws-bestaetigen="<?= Html::encode(Texte::t('oeffentlich_beenden_frage')) ?>"><i class="fa fa-lock"></i> <?= Html::encode(Texte::t('oeffentlich_beenden')) ?></button>
                    <?= Html::endForm() ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="nx-ws-raster">
            <div>
                <?php if (count($darstellung['inhalt']) > 1) : ?>
                    <nav class="nx-ws-inhalt" aria-label="<?= Html::encode(Texte::t('inhalt')) ?>">
                        <strong><?= Html::encode(Texte::t('inhalt')) ?></strong>
                        <ol>
                            <?php foreach ($darstellung['inhalt'] as $eintrag) : ?>
                                <li><a href="#<?= Html::encode($eintrag['id']) ?>" data-pjax-prevent><?= Html::encode($eintrag['text']) ?></a></li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>

                <div class="nx-ws-text"><?= $darstellung['html'] /* mit HtmlPurifier gereinigt, siehe Darstellung */ ?></div>

                <?php if ($verwandt !== []) : ?>
                    <h2 style="font-size:1.2rem;margin-top:1.5rem"><?= Html::encode(Texte::t('siehe_auch')) ?></h2>
                    <ul>
                        <?php foreach ($verwandt as $v) : ?>
                            <li><a href="<?= Html::encode($sammlung->url('artikel', ['a' => $v['slug']])) ?>"><?= Html::encode($v['titel']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($sammlung->hinweis() !== '') : ?>
                    <div class="nx-ws-hinweis"><i class="fa fa-info-circle"></i> <?= Html::encode($sammlung->hinweis()) ?></div>
                <?php endif; ?>

                <?php if ($darfVorschlagen) : ?>
                    <div class="nx-ws-knoepfe">
                        <button type="button" class="btn btn-default" data-nx-ws-oeffne="nx-ws-sprechen"><i class="fa fa-comments-o"></i> <?= Html::encode(Texte::t('sprechen')) ?></button>
                        <button type="button" class="btn btn-default" data-nx-ws-oeffne="nx-ws-vorschlag" data-nx-ws-art="ergaenzen"><i class="fa fa-plus"></i> <?= Html::encode(Texte::t('ergaenzen')) ?></button>
                        <button type="button" class="btn btn-default" data-nx-ws-oeffne="nx-ws-vorschlag" data-nx-ws-art="aendern"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t('aendern')) ?></button>
                    </div>
                    <div class="nx-ws-formular" id="nx-ws-sprechen" hidden>
                        <?= Html::beginForm($sammlung->url('sprechen', ['a' => $artikel->slug]), 'post') ?>
                            <label for="nx-ws-sprechen-text"><?= Html::encode(Texte::t('sprechen_text')) ?></label>
                            <textarea id="nx-ws-sprechen-text" name="text" class="form-control" maxlength="5000" required minlength="10"></textarea>
                            <div class="nx-ws-knoepfe">
                                <button type="submit" class="btn btn-primary"><?= Html::encode(Texte::t('absenden')) ?></button>
                                <button type="button" class="btn btn-light" data-nx-ws-schliesse><?= Html::encode(Texte::t('abbrechen')) ?></button>
                            </div>
                        <?= Html::endForm() ?>
                    </div>
                    <div class="nx-ws-formular" id="nx-ws-vorschlag" hidden>
                        <?= Html::beginForm($sammlung->url('vorschlag', ['a' => $artikel->slug]), 'post') ?>
                            <?= Html::hiddenInput('art', 'ergaenzen') ?>
                            <label for="nx-ws-vorschlag-text"><?= Html::encode(Texte::t('vorschlag_text')) ?></label>
                            <textarea id="nx-ws-vorschlag-text" name="text" class="form-control" maxlength="5000" required minlength="10"></textarea>
                            <div class="nx-ws-knoepfe">
                                <button type="submit" class="btn btn-primary"><?= Html::encode(Texte::t('absenden')) ?></button>
                                <button type="button" class="btn btn-light" data-nx-ws-schliesse><?= Html::encode(Texte::t('abbrechen')) ?></button>
                            </div>
                        <?= Html::endForm() ?>
                    </div>
                <?php else : ?>
                    <p class="nx-ws-leise"><?= Html::encode(Texte::t('nur_mitglieder')) ?></p>
                <?php endif; ?>
            </div>

            <aside class="nx-ws-box">
                <strong><?= Html::encode(Texte::t('auf_einen_blick')) ?></strong>
                <dl style="margin:0">
                    <?php if ($artikel->thema !== '') : ?>
                        <dt><?= Html::encode(Texte::t('thema')) ?></dt>
                        <dd><a href="<?= Html::encode($sammlung->url('index', ['thema' => $artikel->thema])) ?>"><?= Html::encode($sammlung->themaName($artikel->thema)) ?></a></dd>
                    <?php endif; ?>
                    <?php if ($schlagworte !== []) : ?>
                        <dt><?= Html::encode(Texte::t('schlagworte_artikel')) ?></dt>
                        <dd class="nx-ws-worte" style="margin-top:.2rem">
                            <?php foreach ($schlagworte as $wort) : ?>
                                <a class="nx-ws-wort" href="<?= Html::encode($sammlung->url('index', ['wort' => $wort])) ?>"><?= Html::encode($wort) ?></a>
                            <?php endforeach; ?>
                        </dd>
                    <?php endif; ?>
                    <?php if ($artikel->pruefdatum) : ?>
                        <dt><?= Html::encode(Texte::t('geprueft')) ?></dt>
                        <dd><?= Html::encode($datum($artikel->pruefdatum)) ?></dd>
                    <?php endif; ?>
                    <?php if ($artikel->wiedervorlage) : ?>
                        <dt><?= Html::encode(Texte::t('naechste_pruefung')) ?></dt>
                        <dd><?= Html::encode($datum($artikel->wiedervorlage)) ?></dd>
                    <?php endif; ?>
                    <?php if ($darstellung['quellen'] > 0) : ?>
                        <dt><?= Html::encode(Texte::t('quellen')) ?></dt>
                        <dd><?= Html::encode(sprintf(Texte::t('belege'), $darstellung['quellen'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </aside>
        </div>
    </div>
</div>
