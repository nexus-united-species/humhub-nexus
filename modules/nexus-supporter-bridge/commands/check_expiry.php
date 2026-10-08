<?php
/**
 * Taegliche Ablaufpruefung fuer Ko-fi-Unterstuetzer (Vorgabe Abschnitt 6).
 *
 * Bewusst ein eigenstaendiges Skript statt ein registrierter Yii-Modul-
 * Konsolenbefehl -- HumHubs Modul-System hat dafuer keinen so einfachen,
 * bereits erprobten Weg wie fuer Web-Controller. Dasselbe Bootstrap-Muster
 * wie /data/export_humhub_knowledge.php (laeuft dort schon seit dem
 * 07.09.2026 zuverlaessig alle 30 Minuten) -- hier per neuem systemd-Timer
 * einmal taeglich.
 *
 * Wichtig: entfernt NIEMALS selbst den Space-Zugang. ACTIVE -> GRACE_PERIOD
 * -> nach Karenzzeit -> EXPIRED_PENDING. Ab EXPIRED_PENDING entscheidet ein
 * Mensch im Adminbereich (Freischalten oder Entziehen).
 */
require('/opt/humhub/protected/vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable('/app', '.env');
$dotenv->safeLoad();

defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');

require('/opt/humhub/protected/vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@humhub', $_ENV['HUMHUB_ALIASES__HUMHUB'] ?? '/opt/humhub/protected/humhub');

$bootstrap = new \humhub\services\BootstrapService();
$bootstrap->setPaths(config: '/data/config');
$config = $bootstrap->getConfig('console');

new \humhub\components\console\Application($config);

use nexus\modules\supporterBridge\models\Supporter;
use nexus\modules\supporterBridge\models\SupporterEvent;
use nexus\modules\supporterBridge\services\NotificationService;

$karenzTage = (int)(getenv('GRACE_PERIOD_DAYS') ?: 7);
$jetzt = new DateTimeImmutable();

$aktiveMitAblauf = Supporter::find()
    ->where(['status' => Supporter::STATUS_ACTIVE])
    ->andWhere(['not', ['expires_at' => null]])
    ->andWhere(['<', 'expires_at', $jetzt->format('Y-m-d H:i:s')])
    ->all();

$inKarenzzeit = 0;
foreach ($aktiveMitAblauf as $supporter) {
    $supporter->status = Supporter::STATUS_GRACE_PERIOD;
    $supporter->save(false);
    SupporterEvent::protokollieren(SupporterEvent::GRACE_PERIOD_STARTED, $supporter->humhub_user_id, null);
    $inKarenzzeit++;
    echo "GRACE_PERIOD gestartet: {$supporter->ko_fi_email} (Ablauf war {$supporter->expires_at})\n";
}

$karenzAbgelaufen = Supporter::find()
    ->where(['status' => Supporter::STATUS_GRACE_PERIOD])
    ->andWhere(['not', ['expires_at' => null]])
    ->all();

$neuUeberfaellig = 0;
foreach ($karenzAbgelaufen as $supporter) {
    $ablauf = new DateTimeImmutable($supporter->expires_at);
    $karenzEnde = $ablauf->modify("+{$karenzTage} days");

    if ($jetzt > $karenzEnde) {
        $supporter->status = Supporter::STATUS_EXPIRED_PENDING;
        $supporter->save(false);
        (new NotificationService())->entscheidungNoetig(
            'Karenzzeit abgelaufen',
            $supporter->ko_fi_email,
            $supporter->amount,
            $supporter->currency
        );
        $neuUeberfaellig++;
        echo "EXPIRED_PENDING (wartet auf Admin-Entscheidung): {$supporter->ko_fi_email}\n";
    }
}

echo "\nFertig. Neu in Karenzzeit: $inKarenzzeit. Neu ueberfaellig (Admin-Entscheidung noetig): $neuUeberfaellig.\n";
echo "Space-Zugang wurde durch dieses Skript bei niemandem veraendert.\n";
