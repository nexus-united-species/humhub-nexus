<?php

/**
 * Bestaetigung nach dem Absenden. Nummer 0 = Lockfeld ausgefuellt (Spam) -- dann ohne Nummer.
 *
 * @var $t array
 * @var $nummer int
 * @var $gast bool
 */

use humhub\helpers\Html;
use yii\helpers\Url;

$this->pageTitle = $t['titel'];
?>
<div class="container" style="max-width:860px;">
    <div class="panel panel-default">
        <div class="panel-body" style="font-size:15px;">
            <h1 style="font-size:24px;font-weight:700;">✅ <?= Html::encode($t['danke_titel']) ?></h1>
            <?php if ($nummer > 0) : ?>
                <p><?= Html::encode(sprintf($t['danke_text'], $nummer)) ?></p>
            <?php endif; ?>
            <p><?= Html::encode($gast ? $t['danke_gast'] : $t['danke_nachricht']) ?></p>
            <p><a href="<?= Html::encode(Url::to(['/nexus-hilfe/hilfe/index'])) ?>">← <?= Html::encode($t['zurueck']) ?></a></p>
        </div>
    </div>
</div>
