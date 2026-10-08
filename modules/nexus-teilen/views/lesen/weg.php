<?php

/**
 * Freigabe zurueckgenommen, Inhalt geloescht oder Link falsch -- freundlich statt Fehlerseite.
 *
 * @var $t array
 * @var $sprache string
 */

use humhub\helpers\Html;

$this->params['nexusTeilen'] = ['sprache' => $sprache, 'titel' => $t['weg_titel']];
?>
<article class="karte">
    <h1 class="titel"><?= Html::encode($t['weg_titel']) ?></h1>
    <p><?= Html::encode($t['weg_text']) ?></p>
</article>
<?= $this->render('_fuss', ['t' => $t, 'imPortal' => null]) ?>
