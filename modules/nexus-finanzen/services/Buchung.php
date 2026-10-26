<?php

namespace nexus\modules\finanzen\services;

use humhub\modules\user\models\User;
use nexus\modules\finanzen\models\Ausgabe;
use nexus\modules\finanzen\models\Eingang;
use nexus\modules\finanzen\models\Kachel;
use nexus\modules\finanzen\models\Verteilung;
use nexus\modules\finanzen\models\Zuweisung;
use nexus\modules\finanzen\Module;
use RuntimeException;
use Throwable;
use Yii;

/**
 * Alles, was Geld bewegt. Jede Methode prueft selbst, ob die Person das darf -- die Steuerung prueft
 * zusaetzlich, aber eine Regel wie "niemand gibt die eigene Verguetung frei" steht genau hier.
 */
class Buchung
{
    private const VERSAND = 'nexus\modules\arbeitszeit\services\Benachrichtigung';
    private const TITEL = '💛 Finanzierung';

    // ── Eingaenge ───────────────────────────────────────────────────────────

    public static function eingang(User $ich, string $datum, int $betrag, string $quelle, ?int $zweck, string $referenz, string $notiz): Eingang
    {
        self::pruefe($ich);
        if ($betrag <= 0) {
            throw new RuntimeException('Bitte einen Betrag über 0 eingeben.');
        }
        if (!in_array($quelle, Eingang::QUELLEN, true) || $quelle === 'kofi') {
            throw new RuntimeException('Ko-fi-Zahlungen kommen automatisch – bitte eine andere Quelle wählen.');
        }
        $datum = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum) ? $datum : date('Y-m-d');
        $kachel = $zweck ? Kachel::findOne($zweck) : null;
        if ($zweck && $kachel === null) {
            throw new RuntimeException('Diese Kachel gibt es nicht.');
        }
        return self::speichereEingang([
            'datum' => $datum, 'betrag' => $betrag, 'waehrung' => 'EUR', 'quelle' => $quelle,
            'zweck_kachel_id' => $kachel?->id, 'referenz' => mb_substr(trim($referenz), 0, 190) ?: null,
            'notiz' => mb_substr(trim($notiz), 0, 255) ?: null, 'erfasst_von' => $ich->id,
        ], $kachel, (int)$ich->id);
    }

    /**
     * Aus dem Ko-fi-Webhook (nexus-supporter-bridge). Frei verfuegbar; dieselbe Ko-fi-Nachricht nur einmal.
     * Ko-fi-Zahlungen laufen ueber Stripe -- sie werden NUR hier gezaehlt, nie zusaetzlich als Stripe-Eingang.
     */
    public static function vonKofi(string $kennung, ?float $betrag, ?string $waehrung, string $art): ?Eingang
    {
        if ($kennung === '' || $betrag === null || $betrag <= 0 || Eingang::find()->where(['kofi_id' => $kennung])->exists()) {
            return null;
        }
        $waehrung = strtoupper(substr((string)$waehrung, 0, 3)) ?: 'EUR';
        $e = self::speichereEingang([
            'datum' => date('Y-m-d'), 'betrag' => (int)round($betrag * 100), 'waehrung' => $waehrung, 'quelle' => 'kofi',
            'kofi_id' => $kennung, 'notiz' => $art !== '' ? 'Ko-fi: ' . mb_substr($art, 0, 60) : 'Ko-fi',
        ], null, null);
        if ($waehrung !== 'EUR') {
            self::anVerantwortliche(null, sprintf("Ko-fi-Zahlung in %s eingegangen (%s). Sie zählt erst, wenn sie von Hand in Euro erfasst wird:\n%s",
                $waehrung, number_format($betrag, 2, ',', '.'), self::link()));
        }
        return $e;
    }

    public static function eingangLoeschen(User $ich, Eingang $e): void
    {
        self::pruefe($ich);
        if ($e->quelle === 'kofi') {
            throw new RuntimeException('Ko-fi-Eingänge kommen automatisch und werden nicht gelöscht.');
        }
        if ($e->zweck_kachel_id === null && (new Stand())->topf() - (int)$e->betrag < 0) {
            throw new RuntimeException('Dieses Geld ist schon verteilt – erst die Verteilung prüfen.');
        }
        $t = Yii::$app->db->beginTransaction();
        try {
            Zuweisung::deleteAll(['eingang_id' => $e->id]);
            $e->delete();
            $t->commit();
        } catch (Throwable $x) {
            $t->rollBack();
            throw $x;
        }
    }

    private static function speichereEingang(array $werte, ?Kachel $zweck, ?int $von): Eingang
    {
        $t = Yii::$app->db->beginTransaction();
        try {
            $e = new Eingang($werte + ['created_at' => date('Y-m-d H:i:s')]);
            $e->save(false);
            if ($zweck !== null) {
                (new Zuweisung([
                    'kachel_id' => $zweck->id, 'monat' => $zweck->istLaufend() ? substr($e->datum, 0, 7) : null,
                    'betrag' => $e->betrag, 'eingang_id' => $e->id, 'created_by' => $von, 'created_at' => date('Y-m-d H:i:s'),
                ]))->save(false);
            }
            $t->commit();
            return $e;
        } catch (Throwable $x) {
            $t->rollBack();
            throw $x;
        }
    }

    // ── Verteilung aus dem Topf ─────────────────────────────────────────────

    public static function vorschlagen(User $ich, string $monat): Verteilung
    {
        self::pruefe($ich);
        $posten = (new Stand())->vorschlag($monat);
        if ($posten === []) {
            throw new RuntimeException('Im Topf ist kein freies Geld, oder alle freigegebenen Kacheln sind gedeckt.');
        }
        Verteilung::updateAll(['status' => 'verworfen'], ['status' => ['vorgeschlagen', 'wartet_zweite']]);
        $v = new Verteilung(['monat' => $monat, 'vorschlag' => json_encode($posten), 'summe' => array_sum(array_column($posten, 'betrag')),
            'status' => 'vorgeschlagen', 'vorgeschlagen_von' => $ich->id]);
        $v->save(false);
        return $v;
    }

    /**
     * Bestaetigen. Geht Geld an eine Kachel, aus der die bestaetigende Person selbst verguetet wird, wartet die
     * Verteilung auf eine ZWEITE Person -- die darf weder die erste sein noch selbst aus der Verteilung verguetet werden.
     * @return string gebucht|wartet_zweite
     */
    public static function bestaetigen(User $ich, Verteilung $v): string
    {
        self::pruefe($ich);
        $beguenstigte = self::beguenstigte($v);
        if ($v->status === 'vorgeschlagen') {
            if (in_array((int)$ich->id, $beguenstigte, true)) {
                $v->updateAttributes(['status' => 'wartet_zweite', 'bestaetigt_von' => $ich->id, 'updated_at' => date('Y-m-d H:i:s')]);
                self::anVerantwortliche((int)$ich->id, sprintf("%s hat eine Verteilung über %s bestätigt. Darin ist Geld für eine Vergütung von %s – bitte als zweite Person prüfen und freigeben:\n%s",
                    $ich->displayName, Geld::zeigen((int)$v->summe), $ich->displayName, self::link()));
                return 'wartet_zweite';
            }
            $v->bestaetigt_von = $ich->id;
        } elseif ($v->status === 'wartet_zweite') {
            if ((int)$v->bestaetigt_von === (int)$ich->id || in_array((int)$ich->id, $beguenstigte, true)) {
                throw new RuntimeException('Die zweite Freigabe muss eine andere Person geben, die nicht selbst aus dieser Verteilung vergütet wird.');
            }
            $v->zweite_von = $ich->id;
        } else {
            throw new RuntimeException('Diese Verteilung ist schon erledigt.');
        }
        self::buchen($v);
        return 'gebucht';
    }

    public static function verwerfen(User $ich, Verteilung $v): void
    {
        self::pruefe($ich);
        if (in_array($v->status, ['vorgeschlagen', 'wartet_zweite'], true)) {
            $v->updateAttributes(['status' => 'verworfen', 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    private static function buchen(Verteilung $v): void
    {
        $t = Yii::$app->db->beginTransaction();
        try {
            if ((int)$v->summe > (new Stand())->topf()) {
                throw new RuntimeException('Im Topf ist inzwischen weniger Geld als vorgeschlagen – bitte einen neuen Vorschlag machen.');
            }
            foreach ($v->posten() as $p) {
                $k = Kachel::findOne((int)$p['kachel_id']);
                if ($k === null) {
                    continue;
                }
                (new Zuweisung([
                    'kachel_id' => $k->id, 'monat' => $k->istLaufend() ? $v->monat : null, 'betrag' => (int)$p['betrag'],
                    'verteilung_id' => $v->id, 'created_by' => $v->bestaetigt_von, 'created_at' => date('Y-m-d H:i:s'),
                ]))->save(false);
            }
            $v->status = 'gebucht';
            $v->save(false);
            $t->commit();
        } catch (Throwable $x) {
            $t->rollBack();
            throw $x;
        }
    }

    /** @return int[] user-ids, die aus Kacheln dieser Verteilung verguetet werden */
    private static function beguenstigte(Verteilung $v): array
    {
        $ids = array_map(fn($p) => (int)$p['kachel_id'], $v->posten());
        return array_map('intval', Kachel::find()->select('beguenstigt_id')->where(['id' => $ids])->andWhere(['not', ['beguenstigt_id' => null]])->column());
    }

    // ── Ausgaben ────────────────────────────────────────────────────────────

    /**
     * @param bool $vorgestreckt aus eigener Tasche der erfassenden Person bezahlt (zaehlt nicht als Ausgabe aus dem Topf)
     * @param bool $geschaetzt   Betrag ist eine Schaetzung
     */
    public static function ausgabe(User $ich, Kachel $k, string $datum, int $betrag, string $notiz, string $beleg, bool $vorgestreckt = false, bool $geschaetzt = false): Ausgabe
    {
        self::pruefe($ich);
        if ($betrag <= 0) {
            throw new RuntimeException('Bitte einen Betrag über 0 eingeben.');
        }
        $datum = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum) ? $datum : date('Y-m-d');
        $verguetung = $k->beguenstigt_id !== null;
        if ($vorgestreckt && $verguetung) {
            // Eine Verguetung laeuft immer ueber die Vier-Augen-Freigabe -- "vorgestreckt" waere ein Umweg daran vorbei.
            throw new RuntimeException('Bei einer Vergütungs-Kachel geht „vorgestreckt“ nicht – Vergütungen brauchen die Freigabe einer zweiten Person.');
        }
        $a = new Ausgabe([
            'kachel_id' => $k->id, 'monat' => $k->istLaufend() ? substr($datum, 0, 7) : null, 'datum' => $datum, 'betrag' => $betrag,
            'notiz' => mb_substr(trim($notiz), 0, 255) ?: null, 'beleg' => mb_substr(trim($beleg), 0, 190) ?: null,
            'status' => $verguetung ? 'offen' : 'freigegeben', 'erfasst_von' => $ich->id,
            'freigegeben_von' => $verguetung ? null : $ich->id, 'created_at' => date('Y-m-d H:i:s'),
            'vorgestreckt_von' => $vorgestreckt ? $ich->id : null, 'geschaetzt' => $geschaetzt ? 1 : 0,
        ]);
        $a->save(false);
        if ($verguetung) {
            self::anVerantwortliche((int)$ich->id, sprintf("%s hat eine Auszahlung über %s aus „%s“ eingetragen. Vergütungen zählen erst nach der Freigabe durch eine zweite Person:\n%s",
                $ich->displayName, Geld::zeigen($betrag, true), $k->titel, self::link()));
        }
        return $a;
    }

    /**
     * Betrag, Datum, Notiz und "geschaetzt" einer VORGESTRECKTEN Ausgabe korrigieren. Nur dort: Ausgaben aus dem
     * Topf und Verguetungen haben Folgen fuer die Verteilung bzw. die Freigabe und werden geloescht und neu erfasst.
     */
    public static function ausgabeAendern(User $ich, Ausgabe $a, string $datum, int $betrag, string $notiz, bool $geschaetzt): void
    {
        self::pruefe($ich);
        if ($a->vorgestreckt_von === null) {
            throw new RuntimeException('Ändern geht nur bei vorgestreckten Ausgaben. Andere bitte löschen und neu erfassen.');
        }
        if ($betrag <= 0) {
            throw new RuntimeException('Bitte einen Betrag über 0 eingeben.');
        }
        $datum = preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum) ? $datum : $a->datum;
        $a->updateAttributes([
            'datum' => $datum, 'betrag' => $betrag, 'geschaetzt' => $geschaetzt ? 1 : 0,
            'monat' => $a->kachel?->istLaufend() ? substr($datum, 0, 7) : null,
            'notiz' => mb_substr(trim($notiz), 0, 255) ?: null,
        ]);
    }

    public static function ausgabeFreigeben(User $ich, Ausgabe $a): void
    {
        self::pruefe($ich);
        $k = $a->kachel;
        if ($a->status !== 'offen') {
            return;
        }
        if ((int)$a->erfasst_von === (int)$ich->id || (int)$k?->beguenstigt_id === (int)$ich->id) {
            throw new RuntimeException('Diese Auszahlung muss eine andere Person freigeben – nicht wer sie eingetragen hat und nicht wer sie bekommt.');
        }
        $a->updateAttributes(['status' => 'freigegeben', 'freigegeben_von' => $ich->id]);
    }

    public static function ausgabeLoeschen(User $ich, Ausgabe $a): void
    {
        self::pruefe($ich);
        if ($a->status === 'freigegeben' && $a->kachel?->beguenstigt_id !== null && (int)$a->freigegeben_von !== (int)$ich->id && (int)$a->erfasst_von !== (int)$ich->id) {
            throw new RuntimeException('Eine freigegebene Auszahlung kann nur löschen, wer sie eingetragen oder freigegeben hat.');
        }
        $a->delete();
    }

    // ── Kacheln ─────────────────────────────────────────────────────────────

    /** Der Deckel des Entwicklungsfonds gilt fuer die Summe der FREIGEGEBENEN einmaligen Entwicklungskacheln */
    public static function kachelSpeichern(User $ich, Kachel $k): void
    {
        self::pruefe($ich);
        if ($k->bereich === 'entwicklung') {
            $k->art = 'einmalig';
        }
        $deckel = Module::instanz()->entwicklungsDeckel();
        if ($k->bereich === 'entwicklung' && $k->freigegeben && $deckel > 0
            && (new Stand())->entwicklungFreigegeben($k->isNewRecord ? null : (int)$k->id) + (int)$k->betrag > $deckel) {
            throw new RuntimeException(sprintf('Der Entwicklungsfonds ist auf %s gedeckelt – mit dieser Kachel wäre er überschritten.', Geld::zeigen($deckel)));
        }
        if (!$k->save()) {
            throw new RuntimeException(implode(' ', $k->getErrorSummary(true)));
        }
    }

    public static function kachelLoeschen(User $ich, Kachel $k): void
    {
        self::pruefe($ich);
        if (Zuweisung::find()->where(['kachel_id' => $k->id])->exists() || Ausgabe::find()->where(['kachel_id' => $k->id])->exists()) {
            throw new RuntimeException('An dieser Kachel hängt schon Geld. Statt sie zu löschen: auf „pausiert“ oder „abgeschlossen“ stellen.');
        }
        $k->delete();
    }

    // ── Hilfen ──────────────────────────────────────────────────────────────

    private static function pruefe(User $ich): void
    {
        if (!Module::instanz()->istVerantwortlich($ich)) {
            throw new RuntimeException('Das dürfen nur die Finanzverantwortlichen.');
        }
    }

    private static function link(): string
    {
        return \yii\helpers\Url::to(['/nexus-finanzen/verwaltung/index'], true);
    }

    private static function anVerantwortliche(?int $ausser, string $text): void
    {
        if (!class_exists(self::VERSAND)) {
            return;
        }
        $versand = self::VERSAND;
        foreach (Module::instanz()->verantwortlicheMenschen() as $u) {
            if ((int)$u->id === (int)$ausser) {
                continue;
            }
            try {
                $versand::senden((int)$u->id, self::TITEL, $text);
            } catch (Throwable $e) {
                Yii::error('nexus-finanzen: Nachricht nicht gesendet: ' . $e->getMessage(), 'nexus-finanzen');
            }
        }
    }
}
