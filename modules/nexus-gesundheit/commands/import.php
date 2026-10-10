<?php
/**
 * Uebernimmt die Artikel aus dem Redaktionsordner (Moduleinstellung "importPfad").
 *
 * Aufruf (im HumHub-Container, als www-data):
 *   php commands/import.php --probe   zeigt nur, was passieren wuerde
 *   php commands/import.php           uebernimmt
 *
 * Wiederholbar: unveraenderte Artikel werden uebersprungen, neue Fassungen der Redaktion
 * aktualisiert -- ausser der Artikel wurde im Portal bearbeitet (dann nur gemeldet).
 */
require '/opt/humhub/protected/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('/app', '.env');
$dotenv->safeLoad();
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');
require '/opt/humhub/protected/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@humhub', $_ENV['HUMHUB_ALIASES__HUMHUB'] ?? '/opt/humhub/protected/humhub');
$bootstrap = new \humhub\services\BootstrapService();
$bootstrap->setPaths(config: '/data/config');
new \humhub\components\console\Application($bootstrap->getConfig('console'));

use nexus\modules\gesundheit\Module;
use nexus\modules\gesundheit\services\Importer;

$probe = in_array('--probe', $argv, true);
$modul = Module::instanz();
if ($modul === null) {
    fwrite(STDERR, "Modul nexus-gesundheit ist nicht aktiviert.\n");
    exit(1);
}
$bericht = Importer::lauf($modul->importPfad, $probe);
Yii::$app->cache->flush();

echo ($probe ? 'PROBE -- nichts gespeichert' : 'Import abgeschlossen'), "\n";
printf("neu: %d, aktualisiert: %d, unveraendert: %d, uebersprungen: %d, nicht ueberschrieben: %d\n",
    $bericht['neu'], $bericht['aktualisiert'], $bericht['unveraendert'], count($bericht['uebersprungen']), count($bericht['konflikte']));
foreach ($bericht['uebersprungen'] as $z) {
    echo "  uebersprungen: $z\n";
}
foreach ($bericht['konflikte'] as $z) {
    echo "  nicht ueberschrieben: $z\n";
}
