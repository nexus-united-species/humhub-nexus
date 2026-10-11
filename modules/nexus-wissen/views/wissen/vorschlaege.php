<?php

/**
 * Vorschlaege der Kreis-Mitglieder (nur Admins): uebernehmen oder ablehnen, mit kurzer Antwort.
 *
 * @var $sammlung nexus\modules\wissen\services\Sammlung
 * @var $status string
 * @var $vorschlaege nexus\modules\wissen\models\Vorschlag[]
 */

use humhub\helpers\Html;
use nexus\modules\wissen\services\Texte;

$namen = ['offen' => 'Offen', 'uebernommen' => 'Übernommen', 'abgelehnt' => 'Abgelehnt'];
?>
<div class="panel panel-default nx-ws">
    <div class="panel-body">
        <div class="nx-ws-pfad"><a href="<?= Html::encode($sammlung->url('index')) ?>"><?= Html::encode($sammlung->name()) ?></a></div>
        <h1 style="font-size:1.4rem"><?= Html::encode(Texte::t('vorschlaege')) ?></h1>
        <div class="nx-ws-worte" style="margin-bottom:1rem">
            <?php foreach ($namen as $s => $name) : ?>
                <a class="nx-ws-wort" href="<?= Html::encode($sammlung->url('vorschlaege', ['status' => $s])) ?>" <?= $s === $status ? 'style="font-weight:600"' : '' ?>><?= Html::encode($name) ?></a>
            <?php endforeach; ?>
        </div>
        <?php if ($vorschlaege === []) : ?>
            <p class="nx-ws-leise">Keine Vorschläge.</p>
        <?php endif; ?>
        <ul class="nx-ws-liste">
            <?php foreach ($vorschlaege as $v) : ?>
                <li>
                    <strong><?= Html::encode($v->autor ? $v->autor->displayName : '?') ?></strong>
                    <?= $v->art === 'ergaenzen' ? 'möchte etwas ergänzen' : 'möchte etwas ändern' ?> –
                    <a href="<?= Html::encode($v->artikel->url($sammlung)) ?>"><?= Html::encode($v->artikel->titel) ?></a>
                    <span class="nx-ws-leise">· <?= Html::encode(Yii::$app->formatter->asDatetime($v->created_at, 'short')) ?></span>
                    <blockquote style="margin:.4rem 0;white-space:pre-wrap"><?= Html::encode($v->text) ?></blockquote>
                    <?php if ($v->status === 'offen') : ?>
                        <?= Html::beginForm($sammlung->url('erledigen', ['id' => $v->id]), 'post') ?>
                            <input class="form-control" name="antwort" maxlength="1000" placeholder="Kurze Antwort an <?= Html::encode($v->autor ? $v->autor->displayName : '') ?> (optional)" style="margin-bottom:.4rem">
                            <a class="btn btn-default btn-sm" href="<?= Html::encode($sammlung->url('bearbeiten', ['a' => $v->artikel->slug])) ?>"><i class="fa fa-pencil"></i> Artikel bearbeiten</a>
                            <button type="submit" name="ergebnis" value="uebernommen" class="btn btn-primary btn-sm">Übernommen</button>
                            <button type="submit" name="ergebnis" value="abgelehnt" class="btn btn-light btn-sm">Nicht übernehmen</button>
                        <?= Html::endForm() ?>
                    <?php elseif ($v->antwort) : ?>
                        <div class="nx-ws-leise">Antwort: <?= Html::encode($v->antwort) ?></div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
