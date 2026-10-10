<?php

/**
 * Vorschlaege der Kreis-Mitglieder (nur Admins): uebernehmen oder ablehnen, mit kurzer Antwort.
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $status string
 * @var $vorschlaege nexus\modules\gesundheit\models\Vorschlag[]
 */

use humhub\helpers\Html;
use nexus\modules\gesundheit\services\Texte;

$url = fn(string $route, array $p = []) => $kreis->createUrl('/nexus-gesundheit/wissen/' . $route, $p);
$namen = ['offen' => 'Offen', 'uebernommen' => 'Übernommen', 'abgelehnt' => 'Abgelehnt'];
?>
<div class="panel panel-default nx-gw">
    <div class="panel-body">
        <div class="nx-gw-pfad"><a href="<?= Html::encode($url('index')) ?>"><?= Html::encode(Texte::t('zurueck')) ?></a></div>
        <h1 style="font-size:1.4rem"><?= Html::encode(Texte::t('vorschlaege')) ?></h1>
        <div class="nx-gw-worte" style="margin-bottom:1rem">
            <?php foreach ($namen as $s => $name) : ?>
                <a class="nx-gw-wort" href="<?= Html::encode($url('vorschlaege', ['status' => $s])) ?>" <?= $s === $status ? 'style="font-weight:600"' : '' ?>><?= Html::encode($name) ?></a>
            <?php endforeach; ?>
        </div>
        <?php if ($vorschlaege === []) : ?>
            <p class="nx-gw-leise">Keine Vorschläge.</p>
        <?php endif; ?>
        <ul class="nx-gw-liste">
            <?php foreach ($vorschlaege as $v) : ?>
                <li>
                    <strong><?= Html::encode($v->autor ? $v->autor->displayName : '?') ?></strong>
                    <?= $v->art === 'ergaenzen' ? 'möchte etwas ergänzen' : 'möchte etwas ändern' ?> –
                    <a href="<?= Html::encode($v->artikel->url($kreis)) ?>"><?= Html::encode($v->artikel->titel) ?></a>
                    <span class="nx-gw-leise">· <?= Html::encode(Yii::$app->formatter->asDatetime($v->created_at, 'short')) ?></span>
                    <blockquote style="margin:.4rem 0;white-space:pre-wrap"><?= Html::encode($v->text) ?></blockquote>
                    <?php if ($v->status === 'offen') : ?>
                        <?= Html::beginForm($url('erledigen', ['id' => $v->id]), 'post') ?>
                            <input class="form-control" name="antwort" maxlength="1000" placeholder="Kurze Antwort an <?= Html::encode($v->autor ? $v->autor->displayName : '') ?> (optional)" style="margin-bottom:.4rem">
                            <a class="btn btn-default btn-sm" href="<?= Html::encode($url('bearbeiten', ['a' => $v->artikel->slug])) ?>"><i class="fa fa-pencil"></i> Artikel bearbeiten</a>
                            <button type="submit" name="ergebnis" value="uebernommen" class="btn btn-primary btn-sm">Übernommen</button>
                            <button type="submit" name="ergebnis" value="abgelehnt" class="btn btn-light btn-sm">Nicht übernehmen</button>
                        <?= Html::endForm() ?>
                    <?php elseif ($v->antwort) : ?>
                        <div class="nx-gw-leise">Antwort: <?= Html::encode($v->antwort) ?></div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
