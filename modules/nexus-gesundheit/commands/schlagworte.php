<?php
/**
 * Erzeugt mit der KI Schlagworte und Suchwoerter fuer Artikel, die noch keine haben.
 * Der Artikeltext selbst wird dabei NICHT veraendert.
 *
 * - Schlagworte (sichtbar, zum Stoebern): 4-8 Beschwerden, Pflanzen, Koerperbereiche, Zielgruppen
 * - Suchwoerter (unsichtbar, nur fuer die Suche): Alltagswoerter und andere Bezeichnungen,
 *   z. B. "Bauchweh" fuer Bauchschmerzen, "Sodbrennen" fuer Refluxbeschwerden
 *
 * Nutzt den KI-Zugang des Moduls nexus-translate (ueber nexus-community-assistant/AiService).
 *
 * Aufruf (im HumHub-Container, als www-data):
 *   php commands/schlagworte.php --probe=3     drei Artikel zeigen, nichts speichern
 *   php commands/schlagworte.php               alle Artikel ohne Schlagworte
 *   php commands/schlagworte.php --neu         alle Artikel neu (ersetzt vorhandene KI-Schlagworte)
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

use nexus\modules\gesundheit\models\Artikel;

const ANWEISUNG = <<<'TXT'
Du verschlagwortest Artikel eines deutschsprachigen Gesundheits-Nachschlagewerks, damit Menschen
mit einer Beschwerde den passenden Artikel finden. Antworte NUR mit JSON in genau dieser Form:
{"schlagworte": ["...", "..."], "suchwoerter": ["...", "..."]}

schlagworte: 4 bis 8 kurze, allgemein verstaendliche deutsche Begriffe (1-3 Woerter), Grossschreibung
wie im Deutschen: die wichtigsten Beschwerden oder Erkrankungen, Heilpflanzen oder Naehrstoffe,
Koerperbereiche (z. B. "Magen-Darm", "Haut", "Herz-Kreislauf", "Psyche") und Zielgruppen, falls der
Artikel sich ausdruecklich an sie richtet ("Kinder", "Schwangerschaft", "Ältere Menschen").
Verwende moeglichst gaengige, wiederkehrende Begriffe statt sehr spezieller.

suchwoerter: 5 bis 15 Woerter, mit denen Laien nach diesem Thema suchen wuerden und die NICHT schon
im Titel stehen: Alltagswoerter ("Bauchweh"), andere Bezeichnungen und Synonyme, deutsche und
gebraeuchliche lateinische Namen von Pflanzen, typische Beschwerden, bei denen der Artikel hilft.

Erfinde keine Wirkungen. Nur Begriffe, die zum Inhalt des Artikels passen.
TXT;

$opt = getopt('', ['probe::', 'neu']);
$probe = isset($opt['probe']) ? max(1, (int)($opt['probe'] ?: 3)) : 0;
$neu = isset($opt['neu']);
$ki = new \nexus\modules\communityAssistant\services\AiService();

$abfrage = Artikel::find()->orderBy('id');
if (!$neu) {
    $abfrage->where(['not exists', (new \yii\db\Query())->from('nexus_gesundheit_wort w')
        ->where('w.artikel_id = nexus_gesundheit_artikel.id')->andWhere(['w.art' => 'schlagwort'])]);
}
if ($probe) {
    $abfrage->limit($probe);
}
$alle = $abfrage->all();
$gesamt = count($alle);
echo "$gesamt Artikel", $probe ? ' (PROBE, nichts gespeichert)' : '', "\n";

$fertig = 0;
$fehler = 0;
foreach ($alle as $nr => $artikel) {
    $text = "Titel: {$artikel->titel}\n\n" . mb_substr($artikel->inhalt, 0, 6000);
    $daten = null;
    for ($versuch = 1; $versuch <= 3 && $daten === null; $versuch++) {
        try {
            $antwort = $ki->frage($text, ANWEISUNG, 0.2);
            if (preg_match('/\{.*\}/s', $antwort, $m)) {
                $kandidat = json_decode($m[0], true);
                if (is_array($kandidat) && !empty($kandidat['schlagworte']) && is_array($kandidat['schlagworte'])) {
                    $daten = $kandidat;
                }
            }
        } catch (Throwable $e) {
            echo "  Versuch $versuch fehlgeschlagen: " . mb_substr($e->getMessage(), 0, 120) . "\n";
            sleep(3 * $versuch);
        }
    }
    if ($daten === null) {
        $fehler++;
        echo "FEHLER  {$artikel->titel}\n";
        continue;
    }
    $schlag = array_slice(array_map('strval', $daten['schlagworte']), 0, 8);
    $such = array_slice(array_map('strval', (array)($daten['suchwoerter'] ?? [])), 0, 15);
    if ($probe) {
        echo "\n{$artikel->titel}\n  Schlagworte: " . implode(', ', $schlag) . "\n  Suchwoerter: " . implode(', ', $such) . "\n";
        continue;
    }
    $artikel->setzeWoerter('schlagwort', $schlag);
    $artikel->setzeWoerter('suchwort', $such);
    $fertig++;
    if ($fertig % 25 === 0) {
        echo "$fertig / $gesamt erledigt\n";
    }
}
Yii::$app->cache->flush();
echo "fertig: $fertig, fehlgeschlagen: $fehler\n";
