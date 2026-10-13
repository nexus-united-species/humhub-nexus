<?php

namespace nexus\modules\abzeichen\services;

use nexus\modules\abzeichen\Module;
use Yii;

/**
 * Aktive Minuten im Portal je Kalenderwoche -- und die Momente, die daraus entstehen.
 *
 * Gezaehlt wird bei jedem echten Seitenaufruf (auch Seitenwechsel ohne Neuladen) und jedem Absenden:
 * - weniger als 1 Minute seit dem letzten Zaehlen: nichts (spart Schreibzugriffe)
 * - 1 bis 10 Minuten: die verstrichene Zeit zaehlt (man hat gelesen oder geschrieben)
 * - mehr als 10 Minuten: nur 1 Minute (neuer Besuch -- die Pause dazwischen war keine aktive Zeit)
 * Ein offenes, unbenutztes Fenster erzeugt keine Seitenaufrufe und zaehlt daher nicht.
 */
class Aktivzeit
{
    public const TABELLE = 'nexus_abzeichen_aktiv';
    public const MOMENTE = 'nexus_abzeichen_moment';
    private const PAUSE_SEKUNDEN = 600;
    private const SCHRITT_SEKUNDEN = 60;

    public static function woche(?int $zeit = null): string
    {
        return date('o-\WW', $zeit ?? time());
    }

    public static function zaehlen(int $userId): void
    {
        $woche = self::woche();
        $db = Yii::$app->db;
        // Reihenfolge im UPDATE-Teil ist wichtig: erst "minuten" mit dem ALTEN "zuletzt" berechnen
        $schritt = self::SCHRITT_SEKUNDEN;
        $pause = self::PAUSE_SEKUNDEN;
        $db->createCommand(
            'INSERT INTO ' . self::TABELLE . " (user_id, woche, minuten, zuletzt, erreicht) VALUES (:u, :w, 1, NOW(), '')
             ON DUPLICATE KEY UPDATE
               minuten = minuten + IF(TIMESTAMPDIFF(SECOND, zuletzt, NOW()) < $schritt, 0,
                                      IF(TIMESTAMPDIFF(SECOND, zuletzt, NOW()) > $pause, 1,
                                         CEIL(TIMESTAMPDIFF(SECOND, zuletzt, NOW()) / 60))),
               zuletzt = IF(TIMESTAMPDIFF(SECOND, zuletzt, NOW()) < $schritt, zuletzt, NOW())",
            [':u' => $userId, ':w' => $woche]
        )->execute();
        self::momentePruefen($userId, $woche);
    }

    private static function momentePruefen(int $userId, string $woche): void
    {
        $modul = Module::instanz();
        $zeile = Yii::$app->db->createCommand('SELECT minuten, erreicht FROM ' . self::TABELLE . ' WHERE user_id = :u AND woche = :w',
            [':u' => $userId, ':w' => $woche])->queryOne();
        if ($modul === null || $zeile === false) {
            return;
        }
        $erreicht = array_filter(explode(',', (string)$zeile['erreicht']));
        foreach ($modul->alleMomente() as $moment) {
            if ($moment->art() !== Moment::ART_AKTIVZEIT || in_array($moment->kennung, $erreicht, true)
                || (int)$zeile['minuten'] < $moment->minutenProWoche()) {
                continue;
            }
            $erreicht[] = $moment->kennung;
            Yii::$app->db->createCommand()->update(self::TABELLE, ['erreicht' => implode(',', $erreicht)], ['user_id' => $userId, 'woche' => $woche])->execute();
            self::momentErreicht($userId, $moment->kennung);
        }
    }

    /** Zaehler um 1 erhoehen und die Feier vormerken -- fuer jede Art von Moment nutzbar */
    public static function momentErreicht(int $userId, string $moment): void
    {
        Yii::$app->db->createCommand(
            'INSERT INTO ' . self::MOMENTE . ' (user_id, moment, anzahl, feier_offen) VALUES (:u, :m, 1, 1)
             ON DUPLICATE KEY UPDATE anzahl = anzahl + 1, feier_offen = 1',
            [':u' => $userId, ':m' => $moment]
        )->execute();
    }

    /**
     * Wert auf eine hoehere Zahl setzen (z. B. erreichte Stunden-Stufe) und die Feier vormerken.
     * Sinkt nie. @return bool true, wenn sich etwas geaendert hat
     */
    public static function wertAnheben(int $userId, string $moment, int $wert): bool
    {
        return Yii::$app->db->createCommand(
            'INSERT INTO ' . self::MOMENTE . ' (user_id, moment, anzahl, feier_offen) VALUES (:u, :m, :w, 1)
             ON DUPLICATE KEY UPDATE feier_offen = IF(anzahl < VALUES(anzahl), 1, feier_offen), anzahl = GREATEST(anzahl, VALUES(anzahl))',
            [':u' => $userId, ':m' => $moment, ':w' => $wert]
        )->execute() > 0;
    }

    /** Als geteilt merken -- derselbe Stand laesst sich nicht zweimal teilen */
    public static function geteiltMerken(int $userId, string $moment, int $wert): void
    {
        Yii::$app->db->createCommand()->update(self::MOMENTE, ['geteilt' => $wert], ['user_id' => $userId, 'moment' => $moment])->execute();
    }

    /** @return array<string, int> Moment-Kennung => zuletzt geteilter Wert */
    public static function geteilt(int $userId): array
    {
        $zeilen = Yii::$app->db->createCommand('SELECT moment, geteilt FROM ' . self::MOMENTE . ' WHERE user_id = :u', [':u' => $userId])->queryAll();
        return array_map('intval', array_column($zeilen, 'geteilt', 'moment'));
    }

    /**
     * Faellige Feiern -- werden dabei als gezeigt markiert.
     * @return array<int, array{moment: Moment, anzahl: int}>
     */
    public static function feiernAbholen(int $userId): array
    {
        $modul = Module::instanz();
        $offen = Yii::$app->db->createCommand('SELECT moment, anzahl FROM ' . self::MOMENTE . ' WHERE user_id = :u AND feier_offen = 1',
            [':u' => $userId])->queryAll();
        if ($modul === null || $offen === []) {
            return [];
        }
        Yii::$app->db->createCommand()->update(self::MOMENTE, ['feier_offen' => 0], ['user_id' => $userId])->execute();
        $momente = [];
        foreach ($modul->alleMomente() as $m) {
            $momente[$m->kennung] = $m;
        }
        $liste = [];
        foreach ($offen as $z) {
            if (isset($momente[$z['moment']])) {
                $liste[] = ['moment' => $momente[$z['moment']], 'anzahl' => (int)$z['anzahl']];
            }
        }
        return $liste;
    }

    /** @return array<string, int> Moment-Kennung => wie oft erreicht (nur fuer den Menschen selbst) */
    public static function zaehler(int $userId): array
    {
        $zeilen = Yii::$app->db->createCommand('SELECT moment, anzahl FROM ' . self::MOMENTE . ' WHERE user_id = :u', [':u' => $userId])->queryAll();
        return array_map('intval', array_column($zeilen, 'anzahl', 'moment'));
    }

    public static function aufraeumen(int $wochen): int
    {
        return Yii::$app->db->createCommand()->delete(self::TABELLE, ['<', 'woche', self::woche(strtotime("-$wochen weeks"))])->execute();
    }
}
