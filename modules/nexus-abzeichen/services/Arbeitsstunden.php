<?php

namespace nexus\modules\abzeichen\services;

use nexus\modules\abzeichen\Module;
use Throwable;
use Yii;

/**
 * Stufen fuer freigegebene Arbeitsstunden (Modul nexus-arbeitszeit, Tabelle nexus_arbeitszeit).
 *
 * Gezaehlt werden NUR von einem Admin freigegebene Stunden -- nichts, was man sich selbst gutschreibt.
 * Geprueft wird, wenn sich ein Eintrag aendert (Freigabe, Bearbeitung), und einmal taeglich fuer alle.
 * Gefeiert wird nur eine neue Stufe; springt jemand ueber mehrere Stufen, gibt es eine Feier fuer die
 * hoechste. Der Wert sinkt nie, auch wenn eine Freigabe zurueckgenommen wird.
 * Fehlt das Modul nexus-arbeitszeit, passiert nichts.
 */
class Arbeitsstunden
{
    private const TABELLE = 'nexus_arbeitszeit';
    private const STATUS_FREIGEGEBEN = 'freigegeben';

    /** Nach Aenderung eines Arbeitszeit-Eintrags (Ereignis von nexus-arbeitszeit) */
    public static function nachAenderung($event): void
    {
        try {
            $userId = (int)($event->sender->user_id ?? 0);
            if ($userId > 0) {
                self::pruefen($userId);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-abzeichen: Arbeitsstunden: ' . $e->getMessage(), 'nexus-abzeichen');
        }
    }

    public static function pruefen(int $userId): void
    {
        $momente = self::momente();
        if ($momente === [] || !self::tabelleDa()) {
            return;
        }
        $summe = (float)Yii::$app->db->createCommand(
            'SELECT COALESCE(SUM(stunden), 0) FROM ' . self::TABELLE . ' WHERE user_id = :u AND status = :s',
            [':u' => $userId, ':s' => self::STATUS_FREIGEGEBEN]
        )->queryScalar();
        self::stufenSetzen($userId, $summe, $momente);
    }

    /** Taeglich und zum ersten Einrichten: alle Menschen mit freigegebenen Stunden. @return int Zahl neuer Stufen */
    public static function allePruefen(): int
    {
        $momente = self::momente();
        if ($momente === [] || !self::tabelleDa()) {
            return 0;
        }
        $modul = Module::instanz();
        $neu = 0;
        $zeilen = Yii::$app->db->createCommand(
            'SELECT user_id, SUM(stunden) AS summe FROM ' . self::TABELLE . ' WHERE status = :s AND user_id IS NOT NULL GROUP BY user_id',
            [':s' => self::STATUS_FREIGEGEBEN]
        )->queryAll();
        foreach ($zeilen as $z) {
            if ($modul === null || !$modul->istAusnahme((int)$z['user_id'])) {
                $neu += self::stufenSetzen((int)$z['user_id'], (float)$z['summe'], $momente);
            }
        }
        return $neu;
    }

    /** @param Moment[] $momente */
    private static function stufenSetzen(int $userId, float $summe, array $momente): int
    {
        $neu = 0;
        foreach ($momente as $moment) {
            $stufe = $moment->stufeBei((int)floor($summe));
            if ($stufe !== null && Aktivzeit::wertAnheben($userId, $moment->kennung, (int)$stufe[0])) {
                $neu++;
            }
        }
        return $neu;
    }

    /** @return Moment[] */
    private static function momente(): array
    {
        $modul = Module::instanz();
        return $modul === null ? [] : array_values(array_filter($modul->alleMomente(), fn(Moment $m) => $m->art() === Moment::ART_ARBEITSSTUNDEN));
    }

    private static function tabelleDa(): bool
    {
        return Yii::$app->db->getTableSchema(self::TABELLE) !== null;
    }
}
