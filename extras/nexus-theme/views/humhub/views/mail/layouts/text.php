<?php
// "Bitte nicht antworten" in der Sprache des Empfaengers (Josh, 30.09.2026) -- Textfassung,
// gleicher Wortlaut wie im HTML-Layout.
$nexusNichtAntworten = [
    'de' => "!!! BITTE NICHT AUF DIESE E-MAIL ANTWORTEN !!!\nDiese E-Mail wurde automatisch verschickt. Eine Antwort per E-Mail\nkommt bei niemandem an. Bitte antworte direkt in deinem N.E.X.U.S. Portal:",
    'en' => "!!! PLEASE DO NOT REPLY TO THIS E-MAIL !!!\nThis e-mail was sent automatically. A reply by e-mail will not\nreach anyone. Please answer directly in your N.E.X.U.S. portal:",
    'es' => "!!! POR FAVOR, NO RESPONDAS A ESTE CORREO !!!\nEste correo se ha enviado automáticamente. Una respuesta por correo\nno llega a nadie. Contesta directamente en tu portal N.E.X.U.S.:",
];
$nexusSprache = strtolower(explode('-', (string)Yii::$app->language)[0]);
?>
=====================================================
<?= $nexusNichtAntworten[$nexusSprache] ?? $nexusNichtAntworten['en'] ?>

<?= \yii\helpers\Url::to(['/'], true) ?>

=====================================================

<?= $content; ?>

---

<?php if (isset(Yii::$app->view->params['showUnsubscribe']) && Yii::$app->view->params['showUnsubscribe'] === true) : ?>
<?php $url = (isset(Yii::$app->view->params['unsubscribeUrl'])) ? Yii::$app->view->params['unsubscribeUrl'] : \yii\helpers\Url::to(['/notification/user'], true) ?>
<?= Yii::t('base', 'Unsubscribe') ?>: <?= $url ?>
<?php endif; ?>

<?= \humhub\widgets\PoweredBy::widget(['textOnly' => true]); ?>
