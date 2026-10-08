<?php
/**
 * Stuendliche Pruefung faelliger Meeting-Erinnerungen -- postet automatisch
 * in die konfigurierten Ziel-Spaces (siehe services/MeetingService.php).
 *
 * Eigenstaendiges Skript statt registrierter Yii-Modul-Konsolenbefehl,
 * gleiches, bereits bewaehrtes Muster wie
 * nexus-supporter-bridge/commands/check_expiry.php und
 * /data/export_humhub_knowledge.php.
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

use nexus\modules\communityAssistant\services\MeetingService;

$assistentId = (int)getenv('ASSISTANT_USER_ID');
if ($assistentId <= 0) {
    fwrite(STDERR, "ASSISTANT_USER_ID nicht konfiguriert.\n");
    exit(1);
}

$gepostet = (new MeetingService($assistentId))->faelligePosten();

if (empty($gepostet)) {
    echo "Kein faelliges Meeting in diesem Lauf.\n";
} else {
    echo "Gepostet: " . implode(', ', $gepostet) . "\n";
}
