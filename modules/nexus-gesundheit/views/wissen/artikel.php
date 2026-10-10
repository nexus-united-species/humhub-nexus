<?php

/**
 * Artikelseite: Inhaltsverzeichnis, Text mit Querverweisen, Infokasten, "Siehe auch",
 * Hinweis, Vorschlaege und "Im Kreis darueber sprechen".
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $artikel nexus\modules\gesundheit\models\Artikel
 * @var $darstellung array{html: string, inhalt: array, quellen: int}
 * @var $schlagworte string[]
 * @var $verwandt array
 * @var $istAdmin bool
 * @var $darfVorschlagen bool
 * @var $meldung string|null
 */

use humhub\helpers\Html;
use nexus\modules\gesundheit\services\Texte;

$url = fn(string $route, array $p = []) => $kreis->createUrl('/nexus-gesundheit/wissen/' . $route, $p);
$datum = fn(?string $d) => $d ? Yii::$app->formatter->asDate($d, 'long') : '–';
$this->title = $artikel->titel;
?>
<div class="panel panel-default nx-gw">
    <div class="panel-body">
        <div class="nx-gw-pfad">
            <a href="<?= Html::encode($url('index')) ?>"><?= Html::encode(Texte::t('zurueck')) ?></a> ›
            <a href="<?= Html::encode($url('index', ['thema' => $artikel->thema])) ?>"><?= Html::encode(Texte::thema($artikel->thema)) ?></a>
        </div>
        <h1 style="font-size:1.6rem;margin:0 0 1rem"><?= Html::encode($artikel->titel) ?></h1>

        <?php if ($meldung) : ?>
            <div class="nx-gw-meldung"><?= Html::encode($meldung) ?></div>
        <?php endif; ?>

        <?php if ($istAdmin) : ?>
            <div class="nx-gw-admin">
                <a href="<?= Html::encode($url('bearbeiten', ['a' => $artikel->slug])) ?>"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t('bearbeiten')) ?></a>
                <a href="<?= Html::encode($url('versionen', ['a' => $artikel->slug])) ?>"><i class="fa fa-history"></i> <?= Html::encode(Texte::t('versionen')) ?></a>
                <?php if ($artikel->gnummer) : ?><span class="nx-gw-leise"><?= Html::encode($artikel->gnummer) ?></span><?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="nx-gw-raster">
            <div>
                <?php if (count($darstellung['inhalt']) > 1) : ?>
                    <nav class="nx-gw-inhalt" aria-label="<?= Html::encode(Texte::t('inhalt')) ?>">
                        <strong><?= Html::encode(Texte::t('inhalt')) ?></strong>
                        <ol>
                            <?php foreach ($darstellung['inhalt'] as $eintrag) : ?>
                                <li><a href="#<?= Html::encode($eintrag['id']) ?>" data-pjax-prevent><?= Html::encode($eintrag['text']) ?></a></li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>

                <div class="nx-gw-text"><?= $darstellung['html'] /* mit HtmlPurifier gereinigt, siehe Darstellung */ ?></div>

                <?php if ($verwandt !== []) : ?>
                    <h2 style="font-size:1.2rem;margin-top:1.5rem"><?= Html::encode(Texte::t('siehe_auch')) ?></h2>
                    <ul>
                        <?php foreach ($verwandt as $v) : ?>
                            <li><a href="<?= Html::encode($url('artikel', ['a' => $v['slug']])) ?>"><?= Html::encode($v['titel']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="nx-gw-hinweis"><i class="fa fa-info-circle"></i> <?= Html::encode(Texte::t('hinweis')) ?></div>

                <?php if ($darfVorschlagen) : ?>
                    <div class="nx-gw-knoepfe">
                        <button type="button" class="btn btn-default" data-nx-gw-oeffne="nx-gw-sprechen"><i class="fa fa-comments-o"></i> <?= Html::encode(Texte::t('sprechen')) ?></button>
                        <button type="button" class="btn btn-default" data-nx-gw-oeffne="nx-gw-vorschlag" data-nx-gw-art="ergaenzen"><i class="fa fa-plus"></i> <?= Html::encode(Texte::t('ergaenzen')) ?></button>
                        <button type="button" class="btn btn-default" data-nx-gw-oeffne="nx-gw-vorschlag" data-nx-gw-art="aendern"><i class="fa fa-pencil"></i> <?= Html::encode(Texte::t('aendern')) ?></button>
                    </div>
                    <div class="nx-gw-formular" id="nx-gw-sprechen" hidden>
                        <?= Html::beginForm($url('sprechen', ['a' => $artikel->slug]), 'post') ?>
                            <label for="nx-gw-sprechen-text"><?= Html::encode(Texte::t('sprechen_text')) ?></label>
                            <textarea id="nx-gw-sprechen-text" name="text" class="form-control" maxlength="5000" required minlength="10"></textarea>
                            <div class="nx-gw-knoepfe">
                                <button type="submit" class="btn btn-primary"><?= Html::encode(Texte::t('absenden')) ?></button>
                                <button type="button" class="btn btn-light" data-nx-gw-schliesse><?= Html::encode(Texte::t('abbrechen')) ?></button>
                            </div>
                        <?= Html::endForm() ?>
                    </div>
                    <div class="nx-gw-formular" id="nx-gw-vorschlag" hidden>
                        <?= Html::beginForm($url('vorschlag', ['a' => $artikel->slug]), 'post') ?>
                            <?= Html::hiddenInput('art', 'ergaenzen') ?>
                            <label for="nx-gw-vorschlag-text"><?= Html::encode(Texte::t('vorschlag_text')) ?></label>
                            <textarea id="nx-gw-vorschlag-text" name="text" class="form-control" maxlength="5000" required minlength="10"></textarea>
                            <div class="nx-gw-knoepfe">
                                <button type="submit" class="btn btn-primary"><?= Html::encode(Texte::t('absenden')) ?></button>
                                <button type="button" class="btn btn-light" data-nx-gw-schliesse><?= Html::encode(Texte::t('abbrechen')) ?></button>
                            </div>
                        <?= Html::endForm() ?>
                    </div>
                <?php else : ?>
                    <p class="nx-gw-leise"><?= Html::encode(Texte::t('nur_mitglieder')) ?></p>
                <?php endif; ?>
            </div>

            <aside class="nx-gw-box">
                <strong><?= Html::encode(Texte::t('auf_einen_blick')) ?></strong>
                <dl style="margin:0">
                    <dt><?= Html::encode(Texte::t('thema')) ?></dt>
                    <dd><a href="<?= Html::encode($url('index', ['thema' => $artikel->thema])) ?>"><?= Html::encode(Texte::thema($artikel->thema)) ?></a></dd>
                    <?php if ($schlagworte !== []) : ?>
                        <dt><?= Html::encode(Texte::t('schlagworte')) ?></dt>
                        <dd class="nx-gw-worte" style="margin-top:.2rem">
                            <?php foreach ($schlagworte as $wort) : ?>
                                <a class="nx-gw-wort" href="<?= Html::encode($url('index', ['wort' => $wort])) ?>"><?= Html::encode($wort) ?></a>
                            <?php endforeach; ?>
                        </dd>
                    <?php endif; ?>
                    <dt><?= Html::encode(Texte::t('geprueft')) ?></dt>
                    <dd><?= Html::encode($datum($artikel->pruefdatum)) ?></dd>
                    <dt><?= Html::encode(Texte::t('naechste_pruefung')) ?></dt>
                    <dd><?= Html::encode($datum($artikel->wiedervorlage)) ?></dd>
                    <?php if ($darstellung['quellen'] > 0) : ?>
                        <dt><?= Html::encode(Texte::t('quellen')) ?></dt>
                        <dd><?= Html::encode(sprintf(Texte::t('belege'), $darstellung['quellen'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </aside>
        </div>
    </div>
</div>
