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

        <?php if ($darfBearbeiten) : ?>
            <div class="nx-ws-admin">
                <a href="<?= Html::encode($sammlung->url('bearbeiten', ['a' => $artikel->slug])) ?>"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t('bearbeiten')) ?></a>
                <a href="<?= Html::encode($sammlung->url('versionen', ['a' => $artikel->slug])) ?>"><i class="fa fa-history"></i> <?= Html::encode(Texte::t('versionen')) ?></a>
                <?php if ($artikel->kennung) : ?><span class="nx-ws-leise"><?= Html::encode($artikel->kennung) ?></span><?php endif; ?>
            </div>
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
