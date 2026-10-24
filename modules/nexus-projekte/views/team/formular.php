<?php

/**
 * Team ausschreiben oder bearbeiten: Name, Auftrag, Aufgaben (regelmaessig/einmalig, Aufwand).
 *
 * @var $kreis humhub\modules\space\models\Space
 * @var $team nexus\modules\projekte\models\Team
 * @var $fehler string|null
 */

use humhub\helpers\Html;
use nexus\modules\projekte\models\Team;
use nexus\modules\projekte\models\TeamAufgabe;
use nexus\modules\projekte\services\Teams;
use nexus\modules\projekte\services\Texte;

$neu = $team->isNewRecord;
$zurueck = $neu ? $kreis->createUrl('/nexus-projekte/team/index') : $team->url();
$this->title = Texte::t($neu ? 'team_neu' : 'team_bearbeiten');

$zeilen = [];
foreach ($neu ? [] : $team->aufgaben as $a) {
    $zeilen[] = ['id' => $a->id, 'titel' => $a->titel, 'art' => $a->art, 'aufwand' => (string)$a->aufwand];
}
// Nach einem Fehler die eingegebenen Zeilen behalten
$gepostet = Yii::$app->request->isPost ? (array)Yii::$app->request->post('aufgaben', []) : null;
if ($gepostet !== null) {
    $zeilen = array_values($gepostet);
}
$leer = max(3, 5 - count($zeilen));
for ($i = 0; $i < $leer && count($zeilen) < TeamAufgabe::MAX; $i++) {
    $zeilen[] = ['id' => 0, 'titel' => '', 'art' => 'regelmaessig', 'aufwand' => ''];
}
?>
<div class="panel panel-default nx-pj">
    <div class="panel-body">
        <h1 style="font-size:1.4rem"><?= Html::encode($this->title) ?></h1>
        <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>
        <?php if ($neu) : ?><div class="nx-pj-hinweis"><?= Html::encode(Texte::t('team_formular_hinweis')) ?></div><?php endif; ?>

        <?= Html::beginForm($kreis->createUrl('/nexus-projekte/team/speichern', $neu ? [] : ['t' => $team->id]), 'post') ?>
            <div class="nx-pj-felder">
                <div class="nx-pj-ganz"><label for="nx-tm-titel"><?= Html::encode(Texte::t('team_name')) ?></label>
                    <input id="nx-tm-titel" class="form-control" name="titel" value="<?= Html::encode($team->titel) ?>" required maxlength="120"
                        placeholder="<?= Html::encode(Texte::t('team_name_beispiel')) ?>"></div>
                <div class="nx-pj-ganz"><label for="nx-tm-auftrag"><?= Html::encode(Texte::t('team_auftrag')) ?></label>
                    <textarea id="nx-tm-auftrag" class="form-control" name="auftrag" rows="4" maxlength="5000"
                        placeholder="<?= Html::encode(Texte::t('team_auftrag_beispiel')) ?>"><?= Html::encode((string)$team->auftrag) ?></textarea></div>
                <?php if (!$neu) : ?>
                    <div><label for="nx-tm-status"><?= Html::encode(Texte::t('status')) ?></label>
                        <select id="nx-tm-status" class="form-control" name="status">
                            <?php foreach (Team::STATUS as $s) : ?>
                                <option value="<?= $s ?>" <?= $team->status === $s ? 'selected' : '' ?>><?= Html::encode(Texte::t('team_status_' . $s)) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                <?php endif; ?>
            </div>

            <h2 class="nx-pj-abschnitt"><?= Html::encode(Texte::t('team_aufgaben')) ?></h2>
            <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_aufgaben_hilfe')) ?></p>
            <?php foreach ($zeilen as $i => $z) : ?>
                <div class="nx-pj-team-zeile">
                    <input type="hidden" name="aufgaben[<?= $i ?>][id]" value="<?= (int)($z['id'] ?? 0) ?>">
                    <input class="form-control nx-pj-team-titel" name="aufgaben[<?= $i ?>][titel]" maxlength="190" value="<?= Html::encode((string)($z['titel'] ?? '')) ?>"
                        aria-label="<?= Html::encode(Texte::t('team_aufgabe')) ?> <?= $i + 1 ?>" placeholder="<?= Html::encode(Texte::t($i === 0 ? 'team_aufgabe_beispiel' : 'team_aufgabe')) ?>">
                    <select class="form-control" name="aufgaben[<?= $i ?>][art]" aria-label="<?= Html::encode(Texte::t('team_art')) ?>">
                        <?php foreach (TeamAufgabe::ARTEN as $art) : ?>
                            <option value="<?= $art ?>" <?= ($z['art'] ?? '') === $art ? 'selected' : '' ?>><?= Html::encode(Texte::t('team_art_' . $art)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control" name="aufgaben[<?= $i ?>][aufwand]" maxlength="100" value="<?= Html::encode((string)($z['aufwand'] ?? '')) ?>"
                        aria-label="<?= Html::encode(Texte::t('team_aufwand')) ?>" placeholder="<?= Html::encode(Texte::t('team_aufwand_beispiel')) ?>">
                </div>
            <?php endforeach; ?>
            <p class="nx-pj-leise"><?= Html::encode(Texte::t('team_mehr_zeilen')) ?></p>

            <div class="nx-pj-knoepfe">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= Html::encode(Texte::t($neu ? 'team_ausschreiben' : 'speichern')) ?></button>
                <a class="btn btn-light" href="<?= Html::encode($zurueck) ?>"><?= Html::encode(Texte::t('abbrechen')) ?></a>
            </div>
        <?= Html::endForm() ?>

        <?php if (!$neu && Teams::darfAusschreiben($kreis, Yii::$app->user->getIdentity())) : ?>
            <hr>
            <?= Html::beginForm($kreis->createUrl('/nexus-projekte/team/loeschen', ['t' => $team->id]), 'post', ['data-nx-pj-frage' => Texte::t('team_loeschen_frage')]) ?>
                <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> <?= Html::encode(Texte::t('team_loeschen')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>
