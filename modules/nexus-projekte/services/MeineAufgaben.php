<?php

namespace nexus\modules\projekte\services;

use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Stimme;
use nexus\modules\projekte\Module;

/**
 * Alles, was einen Menschen betrifft -- aus allen Kreisen und Projekten:
 * Kacheln, fuer die er verantwortlich ist oder bei denen er mithilft (nicht fertig), und laufende
 * Abstimmungen in seinen Kreisen, zu denen er noch nichts gesagt hat.
 * Sortiert nach Datum: Ueberfaelliges zuerst, ohne Datum zuletzt.
 */
class MeineAufgaben
{
    /**
     * Ergebnis fuer die Dauer EINER Anfrage (obere Leiste + Startseite fragen beide). Bewusst an das
     * Anfrage-Objekt gebunden statt in einer statischen Liste: im Worker-Modus von FrankenPHP lebt
     * eine statische Variable ueber viele Anfragen weiter und wuerde veraltete Listen zeigen.
     */
    private static ?\WeakMap $gemerkt = null;

    /** @return array<int, array{typ: string, karte: Karte, datum: ?string, ueberfaellig: bool}> */
    public static function fuer(User $mensch, int $hoechstens = 100): array
    {
        self::$gemerkt ??= new \WeakMap();
        $anfrage = \Yii::$app->request;
        $schluessel = (int)$mensch->id . '|' . $hoechstens;
        $liste = self::$gemerkt[$anfrage] ?? [];
        $liste[$schluessel] ??= self::suchen($mensch, $hoechstens);
        self::$gemerkt[$anfrage] = $liste;
        return $liste[$schluessel];
    }

    private static function suchen(User $mensch, int $hoechstens): array
    {
        $id = (int)$mensch->id;
        $eintraege = [];

        $basis = fn() => Karte::find()->alias('k')->innerJoinWith('projekt p', true)
            ->with(['projekt.kreis', 'verantwortlich'])->where(['p.status' => 'aktiv']);

        foreach ($basis()->andWhere(['k.verantwortlich_id' => $id])->andWhere(['!=', 'k.spalte', 'fertig'])->limit($hoechstens)->all() as $k) {
            $eintraege[$k->id] = self::eintrag('verantwortlich', $k);
        }
        $helferBei = (new \yii\db\Query())->select('karte_id')->from(Karte::HELFER_TABELLE)->where(['user_id' => $id])->column();
        if ($helferBei !== []) {
            foreach ($basis()->andWhere(['k.id' => $helferBei])->andWhere(['!=', 'k.spalte', 'fertig'])->limit($hoechstens)->all() as $k) {
                $eintraege[$k->id] ??= self::eintrag('helfer', $k);
            }
        }
        $meineKreise = Membership::find()->select('space_id')->where(['user_id' => $id, 'status' => Membership::STATUS_MEMBER])->column();
        if ($meineKreise !== []) {
            $abgestimmt = Stimme::find()->alias('s')->innerJoin(Karte::tableName() . ' sk', 'sk.id = s.karte_id AND sk.runde = s.runde')
                ->select('s.karte_id')->where(['s.user_id' => $id])->column();
            $abfrage = $basis()->andWhere(['k.spalte' => 'abstimmung', 'p.space_id' => $meineKreise])
                ->andWhere(['or', ['k.verantwortlich_id' => null], ['!=', 'k.verantwortlich_id', $id]]);
            if ($abgestimmt !== []) {
                $abfrage->andWhere(['not in', 'k.id', $abgestimmt]);
            }
            foreach ($abfrage->limit($hoechstens)->all() as $k) {
                $eintraege[$k->id] = self::eintrag('rueckmeldung', $k);
            }
        }

        // Nur Kreise, in denen das Modul (noch) eingeschaltet ist
        $an = [];
        $eintraege = array_filter($eintraege, function ($e) use (&$an) {
            $kreis = $e['karte']->projekt->kreis;
            return $kreis instanceof Space && ($an[$kreis->id] ??= Module::istAn($kreis));
        });

        usort($eintraege, fn($a, $b) => [$a['datum'] === null, $a['datum']] <=> [$b['datum'] === null, $b['datum']]);
        return array_slice(array_values($eintraege), 0, $hoechstens);
    }

    public static function anzahl(User $mensch): int
    {
        return count(self::fuer($mensch));
    }

    private static function eintrag(string $typ, Karte $k): array
    {
        $datum = $typ === 'rueckmeldung' ? $k->abstimmung_bis : $k->frist;
        return ['typ' => $typ, 'karte' => $k, 'datum' => $datum, 'ueberfaellig' => $datum !== null && $datum < date('Y-m-d')];
    }
}
