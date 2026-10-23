<?php

/**
 * Welche Dokumente Nova in den Gespraechen durchsucht (Liste kommt aus dem Cockpit). Ausgelassenes
 * steht mit Grund daneben -- keine stillen Ausschluesse.
 *
 * @var yii\web\View $this
 * @var array{freigegeben: array, ausgelassen: array} $quellen
 * @var string|null $fehler
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Nova – durchsuchte Dokumente';
?>
<div class="panel panel-default">
    <div class="panel-heading"><h4>Welche Dokumente Nova in den Gesprächen durchsucht</h4></div>
    <div class="panel-body">
        <p><a href="<?= Url::to(['index', '#' => 'nova-gespraeche']) ?>">← zurück</a></p>
        <?php if ($fehler) : ?>
            <div class="alert alert-danger">Das Cockpit hat nicht geantwortet: <?= Html::encode($fehler) ?></div>
        <?php endif; ?>
        <p class="text-muted">Dazu kommen die Wissensbereiche des Portals (nur, was das Mitglied selbst lesen darf) und das Portal-Handbuch.
            Gesprächsprotokolle, Telegram, KI-Exporte, Presse und Kreis 0 sind nie dabei.</p>
        <h5>Freigegeben (<?= count($quellen['freigegeben']) ?>)</h5>
        <table class="table table-condensed table-sm">
            <?php foreach ($quellen['freigegeben'] as $q) : ?>
                <tr><td><?= Html::encode($q['art'] === 'wiki' ? 'Wiki' : 'Dokument') ?></td><td><?= Html::encode($q['datei']) ?></td><td class="text-muted"><?= (int)$q['abschnitte'] ?> Abschnitte</td></tr>
            <?php endforeach; ?>
        </table>
        <h5>Bewusst ausgelassen (<?= count($quellen['ausgelassen']) ?>)</h5>
        <table class="table table-condensed table-sm">
            <?php foreach ($quellen['ausgelassen'] as $q) : ?>
                <tr><td><?= Html::encode($q['datei']) ?></td><td class="text-muted"><?= Html::encode($q['grund']) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
