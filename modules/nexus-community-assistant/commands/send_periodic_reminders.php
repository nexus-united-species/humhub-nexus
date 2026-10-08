<?php
/**
 * Stuendlicher Aufruf -- prueft, ob Spendenaufruf (alle 10 Tage) oder
 * Selbstvorstellung (alle 30 Tage) faellig sind, und postet sie dann in die
 * konfigurierten Spaces (PERIODIC_REMINDER_SPACE_IDS). Postet nichts, wenn
 * noch nicht faellig -- siehe services/PeriodicReminderService.php.
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

use nexus\modules\communityAssistant\services\PeriodicReminderService;

$assistentId = (int)getenv('ASSISTANT_USER_ID');
if ($assistentId <= 0) {
    fwrite(STDERR, "ASSISTANT_USER_ID nicht konfiguriert.\n");
    exit(1);
}

$service = new PeriodicReminderService($assistentId);

$spendenaufrufGepostet = $service->spendenaufrufFallsFaellig();
$selbstvorstellungGepostet = $service->selbstvorstellungFallsFaellig();

echo 'Spendenaufruf gepostet: ' . ($spendenaufrufGepostet ? 'ja' : 'nein (noch nicht faellig)') . "\n";
echo 'Selbstvorstellung gepostet: ' . ($selbstvorstellungGepostet ? 'ja' : 'nein (noch nicht faellig)') . "\n";
