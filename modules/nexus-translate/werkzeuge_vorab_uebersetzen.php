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
// Vorab-Uebersetzung aller Beitraege, Kommentare und Wiki-Vorschauen in die jeweils anderen
// Sprachen (25.09.2026). Nutzt denselben Speicher wie die Anzeige -- schon Vorhandenes wird
// uebersprungen. Aufruf: php vorab.php [zaehlen]
use humhub\modules\nexusTranslate\WikiVorschau;
use humhub\modules\nexusTranslate\SprachErkennung;
use humhub\modules\nexusTranslate\controllers\TranslateController;
use humhub\modules\nexusTranslate\models\TranslationCache;
use humhub\modules\nexusTranslate\TerminUebersetzung;
use humhub\modules\nexusTranslate\UebersetzungsSpeicher;
Yii::$app->user->setIdentity(humhub\modules\user\models\User::findOne(['id' => 1]));
$c = new TranslateController('translate', Yii::$app->getModule('nexus-translate'));
$m = new ReflectionMethod($c, 'markdownFassung');
$auftraege = [];
foreach (humhub\modules\post\models\Post::find()->all() as $p) { $auftraege[] = ['post', $p->id, (string)$p->message]; }
foreach (humhub\modules\comment\models\Comment::find()->all() as $k) { $auftraege[] = ['comment', $k->id, (string)$k->message]; }
foreach (humhub\modules\wiki\models\WikiPage::find()->all() as $w) {
    $t = WikiVorschau::text($w);
    $auftraege[] = ['wiki', $w->id, $t];
    // ganze Seite beim Oeffnen (seit 28.09.2026)
    $ganz = (string)($w->latestRevision->content ?? '');
    if (trim($ganz) !== '') {
        $auftraege[] = ['wikis', $w->id, $ganz, SprachErkennung::erkenne($ganz)];
    }
    if (SprachErkennung::erkenne($t) !== null) { $auftraege[] = ['wikit', $w->id, (string)$w->title, SprachErkennung::erkenne($t)]; }
}
// Kalender (seit 26.09.2026): Beschreibung je Serie; Titel laufen ueber TerminUebersetzung (mit Titel-Hinweis).
$termine = class_exists(TerminUebersetzung::KLASSE) ? (TerminUebersetzung::KLASSE)::find()->where(['parent_event_id' => null])->all() : [];
foreach ($termine as $termin) {
    if (trim((string)$termin->description) !== '') {
        $auftraege[] = ['cal', $termin->id, (string)$termin->description, TerminUebersetzung::quelle($termin)];
    }
}
$offen = [];
$zeichen = 0;
foreach ($auftraege as $a) {
    $von = $a[3] ?? SprachErkennung::erkenne($a[2]);
    if ($von === null) { continue; }
    foreach (array_diff(SprachErkennung::SPRACHEN, [$von]) as $ziel) {
        $e = TranslationCache::findOne(['content_type' => $a[0] . '_md', 'content_id' => $a[1], 'language' => $ziel]);
        if ($e && $e->quelle_hash === UebersetzungsSpeicher::pruefsumme($a[2])) { continue; }
        $offen[] = [$a[0], $a[1], $a[2], $ziel];
        $zeichen += mb_strlen($a[2]);
    }
}
echo "Offen: " . count($offen) . " Uebersetzungen, " . $zeichen . " Zeichen\n";
if (($argv[1] ?? '') === 'zaehlen') { exit(0); }
$ok = 0; $fehler = 0;
foreach ($offen as $i => [$typ, $id, $text, $ziel]) {
    try { $m->invoke($c, $typ, $id, $text, $ziel); $ok++; }
    catch (Throwable $e) { $fehler++; echo "FEHLER $typ $id $ziel: " . mb_substr($e->getMessage(), 0, 150) . "\n"; }
    if (($i + 1) % 20 === 0) { echo date('H:i:s') . " " . ($i + 1) . "/" . count($offen) . " (ok $ok, Fehler $fehler)\n"; }
}
foreach ($termine as $termin) {
    try { TerminUebersetzung::aktualisiere($termin); } catch (Throwable $e) { $fehler++; echo "FEHLER Termin-Titel {$termin->id}: " . mb_substr($e->getMessage(), 0, 150) . "\n"; }
}
echo date('H:i:s') . " FERTIG: ok $ok, Fehler $fehler (dazu " . count($termine) . " Termin-Titel)\n";
