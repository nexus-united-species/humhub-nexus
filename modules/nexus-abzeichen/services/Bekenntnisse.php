<?php

namespace nexus\modules\abzeichen\services;

use Yii;

/**
 * Wer sich zu welchem Abzeichen bekennt.
 *
 * Eine Seite zeigt oft Dutzende Profilbilder -- deshalb wird die (kleine) Tabelle einmal je Anfrage
 * gelesen. Bewusst an das Anfrage-Objekt gebunden, nicht statisch: im Worker-Modus von FrankenPHP
 * lebt eine statische Variable ueber viele Anfragen weiter und zeigte sonst veraltete Rahmen.
 */
class Bekenntnisse
{
    public const TABELLE = 'nexus_abzeichen_bekenntnis';

    private static ?\WeakMap $gemerkt = null;

    /** @return string[] Kennungen der Abzeichen, die dieser Mensch zeigt */
    public static function von(int $userId): array
    {
        return self::alle()[$userId] ?? [];
    }

    public static function hat(int $userId, string $abzeichen): bool
    {
        return in_array($abzeichen, self::von($userId), true);
    }

    public static function setzen(int $userId, string $abzeichen, bool $an): void
    {
        $db = Yii::$app->db;
        $db->createCommand()->delete(self::TABELLE, ['user_id' => $userId, 'abzeichen' => $abzeichen])->execute();
        if ($an) {
            $db->createCommand()->insert(self::TABELLE, ['user_id' => $userId, 'abzeichen' => $abzeichen])->execute();
        }
        if (self::$gemerkt !== null) {
            unset(self::$gemerkt[Yii::$app->request]);
        }
    }

    /** @return array<int, string[]> */
    private static function alle(): array
    {
        self::$gemerkt ??= new \WeakMap();
        $anfrage = Yii::$app->request;
        if (!isset(self::$gemerkt[$anfrage])) {
            $liste = [];
            foreach (Yii::$app->db->createCommand('SELECT user_id, abzeichen FROM ' . self::TABELLE)->queryAll() as $z) {
                $liste[(int)$z['user_id']][] = (string)$z['abzeichen'];
            }
            self::$gemerkt[$anfrage] = $liste;
        }
        return self::$gemerkt[$anfrage];
    }
}
