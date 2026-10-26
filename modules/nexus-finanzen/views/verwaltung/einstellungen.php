<?php

/**
 * @var $verantwortliche string
 * @var $webseiten string
 * @var $deckel int
 * @var $mithelfenUrl string
 * @var $fuerAlle bool
 */

use humhub\helpers\Html;
use yii\helpers\Url;
?>
<div class="container">
<div class="panel panel-default nx-fin">
    <div class="panel-body">
        <h1>Einstellungen Finanzierung</h1>
        <p class="nx-fin-leise">Nur für Systemadmins.</p>
        <?= Html::beginForm(Url::to(['einstellungen-speichern']), 'post', ['class' => 'nx-fin-felder']) ?>
            <label class="nx-fin-ganz">Finanzverantwortliche (Benutzernamen, einer je Zeile)
                <textarea class="form-control" name="verantwortliche" rows="3"><?= Html::encode($verantwortliche) ?></textarea></label>
            <label class="nx-fin-ganz">Webseiten, die die öffentlichen Zahlen abrufen dürfen (je Zeile, z. B. https://example.org)
                <textarea class="form-control" name="webseiten" rows="3"><?= Html::encode($webseiten) ?></textarea></label>
            <label>Deckel Entwicklungsfonds (€, 0 = ohne) <input class="form-control" name="deckel" value="<?= Html::encode(number_format($deckel / 100, 2, ',', '')) ?>"></label>
            <label class="nx-fin-haken nx-fin-ganz"><input type="checkbox" name="fuerAlle" value="1" <?= $fuerAlle ? 'checked' : '' ?>> Für alle sichtbar (Menüpunkt, Ampel-Seite und öffentliche Zahlen für die Webseite). Aus = nur die Finanzverantwortlichen sehen alles.</label>
            <label>Link „Mithelfen“ (https://…) <input class="form-control" name="mithelfenUrl" value="<?= Html::encode($mithelfenUrl) ?>"></label>
            <div class="nx-fin-ganz"><button type="submit" class="btn btn-primary">Speichern</button>
                <a class="btn btn-light" href="<?= Html::encode(Url::to(['index'])) ?>">Abbrechen</a></div>
        <?= Html::endForm() ?>
        <p class="nx-fin-leise">Öffentliche Zahlen: <code><?= Html::encode(Url::to(['/nexus-finanzen/oeffentlich/stand'], true)) ?></code></p>
    </div>
</div>
</div>
