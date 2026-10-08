<?php

/** @var yii\web\View $this */
/** @var nexus\modules\communityAssistant\models\Meeting[] $meetings */
/** @var humhub\modules\space\models\Space[] $spaces */
/** @var array $spaceNamenJeMeeting */
/** @var \DateTimeImmutable|null $letzterWochenbericht */
/** @var \DateTimeImmutable|null $letzterSpendenaufruf */
/** @var \DateTimeImmutable|null $letzteSelbstvorstellung */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Nova – N.E.X.U.S. KI-Assistent';

$format = fn(?\DateTimeImmutable $d) => $d ? $d->format('d.m.Y H:i') : 'noch nie gelaufen';
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4>Nova – KI-Assistent</h4>
    </div>
    <div class="panel-body">
        <p class="text-muted">
            Wochenbericht zuletzt: <?= Html::encode($format($letzterWochenbericht)) ?> --
            Spendenaufruf zuletzt: <?= Html::encode($format($letzterSpendenaufruf)) ?> --
            Selbstvorstellung zuletzt: <?= Html::encode($format($letzteSelbstvorstellung)) ?>
        </p>

        <h4 style="margin-top: 24px;">Meeting-Erinnerungen</h4>

        <a href="<?= Url::to(['create']) ?>" class="btn btn-primary" style="margin-bottom: 16px;">
            Neues Meeting anlegen
        </a>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>Titel</th>
                <th>Wiederholung</th>
                <th>Ziel-Spaces</th>
                <th>Zuletzt gepostet</th>
                <th>Aktiv</th>
                <th>Aktionen</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($meetings as $meeting): ?>
                <tr>
                    <td><?= Html::encode($meeting->title) ?></td>
                    <td>
                        <?php if ($meeting->istWiederkehrend()): ?>
                            <?php
                            $wochentage = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
                            ?>
                            jeden <?= Html::encode($wochentage[$meeting->recurrence_weekday] ?? '?') ?>, <?= Html::encode($meeting->recurrence_time) ?> Uhr
                        <?php elseif ($meeting->istEinmalig()): ?>
                            einmalig, <?= Html::encode(date('d.m.Y H:i', strtotime($meeting->event_date))) ?> Uhr
                        <?php else: ?>
                            <span class="text-muted">nicht konfiguriert</span>
                        <?php endif; ?>
                    </td>
                    <td><?= Html::encode(implode(', ', $spaceNamenJeMeeting[$meeting->id] ?? [])) ?: '<span class="text-muted">keine</span>' ?></td>
                    <td><?= $meeting->last_posted_at ? Html::encode(date('d.m.Y H:i', strtotime($meeting->last_posted_at))) : '<span class="text-muted">noch nie</span>' ?></td>
                    <td><?= $meeting->active ? '✅' : '⏸️' ?></td>
                    <td style="white-space: nowrap;">
                        <a href="<?= Url::to(['edit', 'id' => $meeting->id]) ?>" class="btn btn-xs btn-default">Bearbeiten</a>
                        <?= Html::beginForm(['delete', 'id' => $meeting->id], 'post', ['style' => 'display:inline']) ?>
                        <button type="submit" class="btn btn-xs btn-danger"
                                onclick="return confirm('Meeting wirklich loeschen?');">Loeschen</button>
                        <?= Html::endForm() ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($meetings)): ?>
                <tr><td colspan="6" class="text-muted">Noch keine Meeting-Eintraege.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
