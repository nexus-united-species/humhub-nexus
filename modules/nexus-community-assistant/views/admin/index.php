<?php

/** @var yii\web\View $this */
/** @var nexus\modules\communityAssistant\models\Meeting[] $meetings */
/** @var humhub\modules\space\models\Space[] $spaces */
/** @var array $spaceNamenJeMeeting */
/** @var \DateTimeImmutable|null $letzterWochenbericht */
/** @var \DateTimeImmutable|null $letzterSpendenaufruf */
/** @var \DateTimeImmutable|null $letzteSelbstvorstellung */

use nexus\modules\communityAssistant\services\Gespraeche;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Nova – N.E.X.U.S. KI-Assistent';

$format = fn(?\DateTimeImmutable $d) => $d ? $d->format('d.m.Y H:i') : 'noch nie gelaufen';

// Sicherheitsabfrage vor dem Loeschen. Nicht als onclick="..." im HTML: die Sicherheitsregel
// des Portals (CSP mit nonce) blockiert solchen Code still -- die Abfrage wurde dann
// uebersprungen und sofort geloescht. registerJs bekommt den nonce von HumHub.
$this->registerJs(file_get_contents(dirname(__DIR__, 2) . '/resources/bestaetigen.js'));
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

        <h4 style="margin-top: 24px;" id="nova-gespraeche">Nova-Gespräche</h4>
        <?php if (!Gespraeche::eingerichtet()) : ?>
            <div class="alert alert-warning">Noch nicht eingerichtet: Es fehlt die Cockpit-Adresse (common.php, <code>cockpitUrl</code>) oder der Schlüssel.</div>
        <?php endif; ?>
        <p class="text-muted">
            Kosten in diesem Monat: <?= Html::encode(number_format(Gespraeche::monatsKosten(), 2, ',', '.')) ?> € von
            <?= Html::encode(number_format(Gespraeche::monatsgrenze(), 2, ',', '.')) ?> € –
            <a href="<?= Url::to(['quellen']) ?>">Welche Dokumente durchsucht Nova?</a>
            <br>Die Gespräche selbst sieht hier niemand – nur das Mitglied, das sie führt.
        </p>
        <?= Html::beginForm(['gespraeche'], 'post') ?>
            <div class="mb-3">
                <label for="nova-zugang" class="form-label"><strong>Zugang</strong> – Benutzernamen, einer je Zeile (Systemadmins haben immer Zugang)</label>
                <textarea id="nova-zugang" name="zugang" class="form-control" rows="6"><?= Html::encode(implode("\n", Gespraeche::zugang())) ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="nova-tageslimit" class="form-label">Nachrichten je Mensch und Tag</label>
                    <input id="nova-tageslimit" type="number" min="0" max="500" name="tageslimit" class="form-control" value="<?= Gespraeche::tageslimit() ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="nova-monatsgrenze" class="form-label">Monatsgrenze aller Gespräche (€, 0 = keine)</label>
                    <input id="nova-monatsgrenze" type="number" min="0" step="1" name="monatsgrenze" class="form-control" value="<?= Html::encode(Gespraeche::monatsgrenze()) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nova-modell" class="form-label">KI-Modell</label>
                    <?= Html::dropDownList('modell', Gespraeche::modell(), Gespraeche::MODELLE, ['id' => 'nova-modell', 'class' => 'form-control']) ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="nova-token" class="form-label">Schlüssel für das Cockpit (<?= Gespraeche::token() !== '' ? 'ist hinterlegt – nur ausfüllen, um ihn zu ersetzen' : 'fehlt noch' ?>)</label>
                <input id="nova-token" type="password" name="token" class="form-control" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
        <?= Html::endForm() ?>

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
                                data-bestaetigen="Meeting wirklich loeschen?">Loeschen</button>
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
