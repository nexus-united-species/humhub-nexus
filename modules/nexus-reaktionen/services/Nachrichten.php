<?php

namespace nexus\modules\reaktionen\services;

use humhub\modules\mail\models\AbstractMessageEntry;
use humhub\modules\mail\models\MessageEntry;
use Yii;
use yii\db\Expression;
use yii\db\Query;

/**
 * Reaktionen und "Antwort auf diese Nachricht" in privaten Unterhaltungen.
 *
 * Nachrichten sind bei HumHub kein normaler Inhalt (kein Like, keine Sichtbarkeit ueber Kreise), sondern gehoeren
 * einer Unterhaltung mit Teilnehmern. Wer Teilnehmer ist, darf lesen, reagieren und antworten -- sonst niemand.
 * Reaktionen liegen in derselben Tabelle wie bei Beitraegen (object_model = MessageEntry), "Antwort auf" in
 * `nexus_nachricht_antwort`. Keine Benachrichtigung, keine E-Mail.
 */
class Nachrichten
{
    public const TABELLE_ANTWORT = 'nexus_nachricht_antwort';
    public const AUSZUG_LAENGE = 140;

    /** Schluessel im Austausch mit dem Browser: "MSG|<Eintrag-Id>" */
    public static function key(int $entryId): string
    {
        return 'MSG|' . $entryId;
    }

    /** @return string Modellname, unter dem die Reaktionen gespeichert werden */
    public static function modell(): string
    {
        return MessageEntry::class;
    }

    /** Ist der Eintrag eine echte Nachricht (kein Hinweis "X hat die Unterhaltung verlassen") und darf $userId sie lesen? */
    public static function darfSehen(?AbstractMessageEntry $entry, int $userId): bool
    {
        if ($entry === null || (int)$entry->type !== MessageEntry::type()) {
            return false;
        }
        $message = $entry->message;
        return $message !== null && $message->isParticipant($userId);
    }

    /**
     * Setzt/aendert/entfernt die Reaktion. Gesperrte Teilnehmer (die Unterhaltung ist fuer sie blockiert) duerfen nicht.
     *
     * @return array|null null = nicht erlaubt; sonst ['was' => ..., 'reaktionen' => [...]]
     */
    public static function reagieren(int $userId, int $entryId, string $emoji): ?array
    {
        $entry = MessageEntry::findOne(['id' => $entryId]);
        if (!self::darfSehen($entry, $userId) || $entry->message->isBlocked() || !Reaktionen::erlaubt($emoji)) {
            return null;
        }
        $was = Reaktionen::setzen($userId, self::modell(), $entryId, $emoji);
        $alle = Reaktionen::zusammenfassung([[self::modell(), $entryId]], $userId);
        return ['was' => $was, 'reaktionen' => $alle[self::modell() . '|' . $entryId] ?? []];
    }

    /**
     * Reaktionen und Antwort-Bezug fuer viele Eintraege auf einmal. Nur Eintraege, die der Mensch lesen darf.
     *
     * @param int[] $entryIds
     * @return array<string, array{reaktionen: array, antwort: array|null}> Schluessel "MSG|<id>"; nur Eintraege mit Inhalt
     */
    public static function liste(array $entryIds, int $userId): array
    {
        $entryIds = array_values(array_unique(array_filter(array_map('intval', $entryIds), fn($i) => $i > 0)));
        if ($entryIds === []) {
            return [];
        }
        $eintraege = MessageEntry::find()->where(['id' => $entryIds])->with('message')->indexBy('id')->all();

        // Teilnehmerpruefung einmal je Unterhaltung
        $darf = [];
        $erlaubt = [];
        foreach ($eintraege as $id => $e) {
            $mid = (int)$e->message_id;
            if (!array_key_exists($mid, $darf)) {
                $darf[$mid] = self::darfSehen($e, $userId);
            }
            if ($darf[$mid] && (int)$e->type === MessageEntry::type()) {
                $erlaubt[(int)$id] = $e;
            }
        }
        if ($erlaubt === []) {
            return [];
        }

        $reaktionen = Reaktionen::zusammenfassung(
            array_map(fn($id) => [self::modell(), $id], array_keys($erlaubt)),
            $userId
        );
        $bezuege = (new Query())->from(self::TABELLE_ANTWORT)->where(['entry_id' => array_keys($erlaubt)])->indexBy('entry_id')->all();

        $originale = [];
        if ($bezuege !== []) {
            $originale = MessageEntry::find()->where(['id' => array_column($bezuege, 'antwort_auf_id')])->with('user')->indexBy('id')->all();
        }

        $ergebnis = [];
        foreach ($erlaubt as $id => $e) {
            $r = $reaktionen[self::modell() . '|' . $id] ?? [];
            $antwort = null;
            $bezug = $bezuege[$id] ?? null;
            if ($bezug !== null) {
                $orig = $originale[(int)$bezug['antwort_auf_id']] ?? null;
                // Das Original muss in DERSELBEN Unterhaltung liegen (sonst wuerde ein Zitat aus einer fremden Unterhaltung lesbar)
                if ($orig !== null && (int)$orig->message_id === (int)$e->message_id) {
                    $antwort = [
                        'id' => (int)$orig->id,
                        'name' => $orig->user ? $orig->user->displayName : '?',
                        'auszug' => self::auszug((string)$orig->content),
                    ];
                } else {
                    $antwort = ['id' => 0, 'name' => '', 'auszug' => ''];   // Original gibt es nicht mehr
                }
            }
            if ($r !== [] || $antwort !== null) {
                $ergebnis[self::key((int)$id)] = ['reaktionen' => $r, 'antwort' => $antwort];
            }
        }
        return $ergebnis;
    }

    /**
     * Merkt, dass ein neuer Eintrag die Antwort auf einen anderen ist. Nur wenn beide in derselben Unterhaltung liegen
     * und beide echte Nachrichten sind; sonst wird still nichts gemerkt (die Nachricht selbst bleibt unberuehrt).
     */
    public static function antwortMerken(AbstractMessageEntry $neu, int $antwortAufId): bool
    {
        if ($antwortAufId <= 0 || $antwortAufId === (int)$neu->id || (int)$neu->type !== MessageEntry::type()) {
            return false;
        }
        $orig = MessageEntry::findOne(['id' => $antwortAufId]);
        if ($orig === null || (int)$orig->type !== MessageEntry::type() || (int)$orig->message_id !== (int)$neu->message_id) {
            return false;
        }
        Yii::$app->db->createCommand()->upsert(self::TABELLE_ANTWORT, [
            'entry_id' => (int)$neu->id,
            'antwort_auf_id' => $antwortAufId,
            'created_at' => new Expression('NOW()'),
        ], ['antwort_auf_id' => $antwortAufId])->execute();
        return true;
    }

    /** Kurzer, lesbarer Auszug aus dem Markdown-Text einer Nachricht (ohne Formatierung, Erwaehnungen als Name). */
    public static function auszug(string $markdown): string
    {
        $t = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $markdown);   // [Name](mention:...) / Links -> Text
        $t = strip_tags((string)$t);
        $t = preg_replace('/[*_`~#>]+/u', '', (string)$t);
        $t = trim((string)preg_replace('/\s+/u', ' ', (string)$t));
        if ($t === '') {
            return '📎';
        }
        return mb_strlen($t) > self::AUSZUG_LAENGE ? rtrim(mb_substr($t, 0, self::AUSZUG_LAENGE)) . '…' : $t;
    }
}
