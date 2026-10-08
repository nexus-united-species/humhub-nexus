<?php
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
$config['components']['user'] = [
    'class' => \humhub\modules\user\components\User::class,
    'identityClass' => \humhub\modules\user\models\User::class,
    'enableSession' => false,
];
$config['components']['request'] = [
    'class' => \humhub\components\Request::class,
    'cookieValidationKey' => 'konsole-nicht-relevant',
];

class KonsoleMitBenutzer extends \humhub\components\console\Application
{
    public function getUser()
    {
        return $this->get('user');
    }
}
new KonsoleMitBenutzer($config);
use humhub\modules\nexusTranslate\TerminUebersetzung;
use humhub\modules\nexusTranslate\UebersetzungsSpeicher;
use humhub\modules\nexusTranslate\SprachErkennung;
use humhub\modules\nexusTranslate\KreisUebersetzung;
Yii::$app->user->setIdentity(humhub\modules\user\models\User::findOne(['id' => 1]));
foreach (humhub\modules\calendar\models\CalendarEntry::find()->where(['parent_event_id' => null])->all() as $t) {
    TerminUebersetzung::aktualisiere($t);
    $von = TerminUebersetzung::quelle($t);
    $n = 0;
    if (trim((string)$t->description) !== '') {
        foreach (array_diff(SprachErkennung::SPRACHEN, [$von]) as $ziel) { UebersetzungsSpeicher::markdown('cal', (int)$t->id, (string)$t->description, $ziel); $n++; }
    }
    echo "Termin {$t->id} ({$von}): {$t->title} | Beschreibungen: $n\n";
}
KreisUebersetzung::cacheLeeren();
foreach (TerminUebersetzung::tabelle('es') as $a => $b) { if (strpos($a, '&') === false) echo "ES: $a => $b\n"; }
foreach (TerminUebersetzung::tabelle('en') as $a => $b) { if (strpos($a, '&') === false) echo "EN: $a => $b\n"; }
