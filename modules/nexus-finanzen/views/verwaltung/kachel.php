<?php

/**
 * Kachel anlegen oder bearbeiten.
 *
 * @var $k nexus\modules\finanzen\models\Kachel
 * @var $fehler string|null
 * @var $verantwortliche humhub\modules\user\models\User[]
 */

use humhub\helpers\Html;
use humhub\modules\user\models\User;
use nexus\modules\finanzen\models\Kachel;
use nexus\modules\finanzen\services\Geld;
use nexus\modules\finanzen\services\Texte;
use yii\helpers\Url;

$neu = $k->isNewRecord;
$auswahl = static function (string $name, array $werte, ?string $aktiv): string {
    $h = '<select class="form-control" name="' . $name . '" id="nx-fin-' . $name . '">';
    foreach ($werte as $w => $t) {
        $h .= '<option value="' . Html::encode((string)$w) . '"' . ((string)$w === (string)$aktiv ? ' selected' : '') . '>' . Html::encode($t) . '</option>';
    }
    return $h . '</select>';
};
$einordnungen = [];
foreach (Kachel::EINORDNUNGEN as $e) {
    $einordnungen[$e] = Texte::t('einordnung_' . $e, 'de');
}
$zusaetze = ['' => '–'];
foreach (Kachel::ZUSAETZE as $z) {
    $zusaetze[$z] = Texte::t('zusatz_' . $z, 'de');
}
$personen = ['0' => '– niemand –'];
foreach (array_merge($verantwortliche, $k->beguenstigt_id ? [User::findOne($k->beguenstigt_id)] : []) as $u) {
    if ($u !== null) {
        $personen[(string)$u->id] = $u->displayName;
    }
}
?>
<div class="container">
<div class="panel panel-default nx-fin">
    <div class="panel-body">
        <h1><?= $neu ? 'Neue Kachel' : 'Kachel bearbeiten' ?></h1>
        <?php if ($fehler) : ?><div class="alert alert-danger"><?= Html::encode($fehler) ?></div><?php endif; ?>
        <?= Html::beginForm(Url::to(['kachel-speichern', 'id' => $k->id ?: 0]), 'post', ['class' => 'nx-fin-felder']) ?>
            <label class="nx-fin-ganz">Titel <input class="form-control" name="titel" maxlength="120" required value="<?= Html::encode($k->titel) ?>"></label>
            <label class="nx-fin-ganz">Beschreibung (für alle sichtbar) <textarea class="form-control" name="beschreibung" rows="2" maxlength="5000"><?= Html::encode((string)$k->beschreibung) ?></textarea></label>
            <label>Bereich <?= $auswahl('bereich', ['grund' => 'Monatlicher Grundbetrieb', 'optional' => 'Zusätzlicher Dienst (optional)', 'entwicklung' => 'Entwicklungsfonds (einmalig)', 'koordination' => 'Projektkoordination und Aufbauarbeit'], $k->bereich) ?></label>
            <label>Art <?= $auswahl('art', ['laufend' => 'laufend (je Monat)', 'einmalig' => 'einmalig'], $k->art) ?></label>
            <label>Betrag (€) <input class="form-control" name="betrag" inputmode="decimal" required value="<?= Html::encode($k->betrag ? number_format($k->betrag / 100, 2, ',', '') : '') ?>"></label>
            <label>Einordnung <?= $auswahl('einordnung', $einordnungen, $k->einordnung) ?></label>
            <label>Gruppe (nur Anzeige, z. B. „Dienste und Werkzeuge“) <input class="form-control" name="gruppe" maxlength="80" value="<?= Html::encode((string)$k->gruppe) ?>"></label>
            <label>Rang (klein = wird zuerst gedeckt) <input class="form-control" type="number" name="rang" min="0" value="<?= (int)$k->rang ?>"></label>
            <label>Zusatz <?= $auswahl('zusatz', $zusaetze, (string)$k->zusatz) ?></label>
            <label>Vergütung für (Vier-Augen-Prinzip) <?= $auswahl('beguenstigt', $personen, (string)(int)$k->beguenstigt_id) ?></label>
            <label class="nx-fin-haken"><input type="checkbox" name="freigegeben" value="1" <?= $k->freigegeben ? 'checked' : '' ?>> Freigegeben (sonst grau: „geplant / zu prüfen“ – bekommt kein Geld aus dem Topf)</label>
            <label class="nx-fin-haken"><input type="checkbox" name="oeffentlich" value="1" <?= $k->oeffentlich ? 'checked' : '' ?>> Auf der öffentlichen Webseite zeigen</label>
            <div class="nx-fin-ganz">
                <button type="submit" class="btn btn-primary"><?= $neu ? 'Anlegen' : 'Speichern' ?></button>
                <a class="btn btn-light" href="<?= Html::encode(Url::to(['index', '#' => 'kacheln'])) ?>">Abbrechen</a>
            </div>
        <?= Html::endForm() ?>
        <?php if (!$neu) : ?>
            <hr>
            <?= Html::beginForm(Url::to(['kachel-loeschen', 'id' => $k->id]), 'post', ['data-bestaetigen' => 'Diese Kachel wirklich löschen?']) ?>
                <button type="submit" class="btn btn-danger btn-sm">Kachel löschen</button>
                <span class="nx-fin-leise">Geht nur, solange kein Geld an ihr hängt.</span>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>
</div>
