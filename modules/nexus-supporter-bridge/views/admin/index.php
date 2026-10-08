<?php

/** @var yii\web\View $this */
/** @var nexus\modules\supporterBridge\models\Supporter[] $supporters */
/** @var nexus\modules\supporterBridge\models\SupporterEvent[] $events */
/** @var int $spaceId */

use humhub\modules\user\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'N.E.X.U.S. Unterstuetzer-Verwaltung';
?>
<div class="panel panel-default">
    <div class="panel-heading">
        <h4>Unterstuetzer-Verwaltung</h4>
        <p class="text-muted">Unterstuetzer-Space-ID: <?= (int)$spaceId ?> -- <?= count($supporters) ?> Datensaetze</p>
    </div>
    <div class="panel-body">

        <a href="<?= Url::to(['manual-create']) ?>" class="btn btn-primary" style="margin-bottom: 16px;">
            Manuellen Unterstuetzer anlegen
        </a>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>HumHub-Konto</th>
                <th>Ko-fi-E-Mail</th>
                <th>Art</th>
                <th>Betrag</th>
                <th>Stufe</th>
                <th>Letzte Zahlung</th>
                <th>Ablauf</th>
                <th>Status</th>
                <th>Aktionen</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($supporters as $supporter): ?>
                <?php $user = $supporter->humhub_user_id ? User::findOne($supporter->humhub_user_id) : null; ?>
                <tr>
                    <td><?= $user ? Html::encode($user->displayName) : '<span class="text-muted">nicht zugeordnet</span>' ?></td>
                    <td><?= Html::encode($supporter->ko_fi_email) ?></td>
                    <td><?= Html::encode($supporter->support_type) ?></td>
                    <td><?= $supporter->amount !== null ? Html::encode($supporter->amount . ' ' . $supporter->currency) : '-' ?></td>
                    <td><?= Html::encode($supporter->membership_tier ?? '-') ?></td>
                    <td><?= Html::encode($supporter->last_payment_at ?? '-') ?></td>
                    <td><?= Html::encode($supporter->expires_at ?? '-') ?></td>
                    <td><strong><?= Html::encode($supporter->status) ?></strong></td>
                    <td style="white-space: nowrap;">
                        <?php if ($supporter->humhub_user_id): ?>
                            <?= Html::beginForm(['grant', 'id' => $supporter->id], 'post', ['style' => 'display:inline']) ?>
                            <button type="submit" class="btn btn-xs btn-success">Freischalten</button>
                            <?= Html::endForm() ?>

                            <?= Html::beginForm(['grant-lifetime', 'id' => $supporter->id], 'post', ['style' => 'display:inline']) ?>
                            <button type="submit" class="btn btn-xs btn-info">Dauerhaft</button>
                            <?= Html::endForm() ?>

                            <?= Html::beginForm(['set-expiry', 'id' => $supporter->id], 'post', ['style' => 'display:inline']) ?>
                            <input type="date" name="expires_at" style="width: 130px; display:inline-block;">
                            <button type="submit" class="btn btn-xs btn-default">Ablauf setzen</button>
                            <?= Html::endForm() ?>

                            <?= Html::beginForm(['revoke', 'id' => $supporter->id], 'post', ['style' => 'display:inline']) ?>
                            <button type="submit" class="btn btn-xs btn-danger"
                                    onclick="return confirm('Zugang wirklich entziehen?');">Entziehen</button>
                            <?= Html::endForm() ?>
                        <?php else: ?>
                            <span class="text-muted">E-Mail stimmt mit keinem HumHub-Konto ueberein</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($supporters)): ?>
                <tr><td colspan="9" class="text-muted">Noch keine Unterstuetzer-Datensaetze.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <h4 style="margin-top: 32px;">Letzte Ereignisse</h4>
        <table class="table table-condensed">
            <thead><tr><th>Zeit</th><th>Ereignis</th><th>Nutzer</th><th>Referenz</th></tr></thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= Html::encode($event->created_at) ?></td>
                    <td><?= Html::encode($event->event_type) ?></td>
                    <td><?= Html::encode($event->humhub_user_id ?? '-') ?></td>
                    <td><?= Html::encode($event->reference ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
