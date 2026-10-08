<?php
/**
 * Woechentlicher Aufruf (z.B. sonntags 18 Uhr) -- erstellt den KI-Wochenbericht
 * und schickt ihn per E-Mail an alle Systemadministratoren (siehe
 * services/WeeklySummaryService.php). Kein Zeitfenster-Schutz noetig, da der
 * systemd-Timer selbst nur einmal woechentlich feuert.
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
// Seit 05.10.2026 geht der Bericht auch als Portal-Nachricht raus (Absender Nova) -- dafuer braucht
// die Konsole einen "angemeldeten" Benutzer, wie in send_welcome.php.
$config['components']['user'] = [
    'class' => \humhub\modules\user\components\User::class,
    'identityClass' => \humhub\modules\user\models\User::class,
    'enableSession' => false,
];

class KonsoleMitBenutzer extends \humhub\components\console\Application
{
    public function getUser()
    {
        return $this->get('user');
    }
}

new KonsoleMitBenutzer($config);

use nexus\modules\communityAssistant\models\ReminderState;
use nexus\modules\communityAssistant\services\WeeklySummaryService;

(new WeeklySummaryService())->erstellenUndVersenden();
ReminderState::laufMarkieren('weekly_summary');

echo "Wochenbericht-Lauf abgeschlossen.\n";
