<?php

/** @var yii\web\View $this */
/** @var nexus\modules\supporterBridge\models\forms\ManualSupporterForm $model */

use humhub\modules\user\widgets\UserPickerField;
use humhub\widgets\form\ActiveForm;
use nexus\modules\supporterBridge\models\Supporter;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Manuellen Unterstuetzer anlegen';
?>
<div class="panel panel-default">
    <div class="panel-heading"><h4>Manuellen Unterstuetzer anlegen</h4></div>
    <div class="panel-body">

        <p class="text-muted">
            Konto ueber die Namenssuche auswaehlen -- genau wie beim Einladen in einen Space.
            Beispiel: bestehende Unterstuetzer:innen, die bisher ausserhalb von Ko-fi erfasst wurden.
        </p>

        <?php $form = ActiveForm::begin(['id' => 'manual-supporter-form']); ?>

        <?= $form->field($model, 'invite')->widget(UserPickerField::class, [
            'maxSelection' => 1,
            'placeholder' => 'Name oder Nutzername eingeben ...',
        ])->label('HumHub-Konto') ?>

        <?= $form->field($model, 'status')->dropDownList([
            Supporter::STATUS_MANUAL => 'Freigeschaltet (ohne Ablauf, ueberpruefbar)',
            Supporter::STATUS_LIFETIME => 'Dauerhaft (nie automatisch pruefen)',
            Supporter::STATUS_ACTIVE => 'Zeitlich begrenzt (Ablaufdatum unten angeben)',
        ]) ?>

        <?= $form->field($model, 'expires_at')->input('date')->label('Ablaufdatum (nur bei "Zeitlich begrenzt")') ?>

        <?= $form->field($model, 'note')->textInput(['placeholder' => 'z. B. "Frühere Unterstützung, 15€, vor Ko-fi-Umstellung"']) ?>

        <button type="submit" class="btn btn-primary">Anlegen und freischalten</button>
        <a href="<?= Url::to(['index']) ?>" class="btn btn-default">Abbrechen</a>

        <?php ActiveForm::end(); ?>
    </div>
</div>
