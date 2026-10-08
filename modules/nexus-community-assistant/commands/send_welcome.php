<?php
/**
 * Novas Begruessung (siehe services/WillkommensService.php). Alle 15 Minuten ueber den Timer
 * nexus-assistant-welcome: begruesst neue Mitglieder 3 Stunden nach dem ersten Betreten.
 *
 * Von Hand:
 *   --probe              nichts senden, nur die Texte zeigen (fuer die Faelligen bzw. --nachholen)
 *   --nachholen          die einmalige Runde fuer bisher Stille (vor dem Stichtag angemeldet)
 *   --nur=<benutzername> nur diesen Menschen (zusammen mit --probe oder --nachholen)
 *   --limit=<n>          hoechstens n Menschen in diesem Lauf
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
// PosterService setzt den Absender ueber Yii::$app->user -- auf der Konsole sonst nicht vorhanden.
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

use humhub\modules\user\models\User;
use nexus\modules\communityAssistant\services\WillkommensService;

$assistentId = (int)getenv('ASSISTANT_USER_ID');
if ($assistentId <= 0) {
    fwrite(STDERR, "ASSISTANT_USER_ID nicht konfiguriert.\n");
    exit(1);
}

$optionen = getopt('', ['probe', 'nachholen', 'nur:', 'limit:']);
$probe = isset($optionen['probe']);
$nachholen = isset($optionen['nachholen']);
$limit = isset($optionen['limit']) ? max(1, (int)$optionen['limit']) : PHP_INT_MAX;

$dienst = new WillkommensService($assistentId);
if (isset($optionen['nur'])) {
    $mensch = User::findOne(['username' => $optionen['nur']]);
    $liste = $mensch ? [$mensch] : [];
} else {
    $liste = $nachholen ? $dienst->nachzuholende() : $dienst->faelligeNeue();
}
$liste = array_slice($liste, 0, $limit);

echo count($liste) . ($nachholen ? ' nachzuholen' : ' faellig') . ($probe ? ' (PROBE, nichts gesendet)' : '') . "\n";
foreach ($liste as $mensch) {
    if ($probe) {
        $n = $dienst->nachricht($mensch, $nachholen);
        echo "\n===== {$mensch->displayName} (@{$mensch->username}, {$mensch->language}) | Kreis-ID: " . ($n['kreis_id'] ?? '-') . " =====\n";
        echo "Betreff: {$n['titel']}\n\n{$n['text']}\n";
        continue;
    }
    try {
        echo ($dienst->senden($mensch, $nachholen) ? 'gesendet: ' : 'schon begruesst: ') . "{$mensch->displayName} (@{$mensch->username})\n";
    } catch (Throwable $e) {
        echo "FEHLER bei {$mensch->username}: {$e->getMessage()}\n";
    }
}
