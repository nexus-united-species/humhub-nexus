<?php

namespace nexus\modules\reaktionen\services;

use humhub\modules\user\models\User;
use nexus\modules\reaktionen\Module;
use Yii;
use yii\db\Expression;
use yii\db\IntegrityException;
use yii\db\Query;

/** Speichern und Zusammenfassen der Emoji-Reaktionen. Kein HTTP, damit es sich ohne Anfrage testen laesst. */
class Reaktionen
{
    public const TABELLE = 'nexus_reaktion';

    public static function modul(): Module
    {
        /** @var Module $modul */
        $modul = Yii::$app->getModule('nexus-reaktionen');
        return $modul;
    }

    public static function erlaubt(string $emoji): bool
    {
        return in_array($emoji, self::modul()->emojis, true);
    }

    /**
     * Setzt, aendert oder entfernt die Reaktion eines Menschen. Dasselbe Emoji nochmal = zurueckgenommen.
     *
     * @return string 'gesetzt' | 'geaendert' | 'entfernt'
     */
    public static function setzen(int $userId, string $model, int $id, string $emoji): string
    {
        $db = Yii::$app->db;
        $where = ['object_model' => $model, 'object_id' => $id, 'user_id' => $userId];
        $alt = (new Query())->select('emoji')->from(self::TABELLE)->where($where)->scalar($db);

        if ($alt === $emoji) {
            $db->createCommand()->delete(self::TABELLE, $where)->execute();
            return 'entfernt';
        }
        if ($alt !== false && $alt !== null) {
            $db->createCommand()->update(self::TABELLE, ['emoji' => $emoji, 'created_at' => new Expression('NOW()')], $where)->execute();
            return 'geaendert';
        }
        try {
            $db->createCommand()->insert(self::TABELLE, $where + ['emoji' => $emoji, 'created_at' => new Expression('NOW()')])->execute();
        } catch (IntegrityException $e) {
            // Zwei Klicks gleichzeitig: der andere war schneller, dann gilt dieser als Aenderung
            $db->createCommand()->update(self::TABELLE, ['emoji' => $emoji], $where)->execute();
            return 'geaendert';
        }
        return 'gesetzt';
    }

    /**
     * Fasst die Reaktionen mehrerer Objekte zusammen.
     *
     * @param array<int, array{0:string,1:int}> $paare [[object_model, object_id], ...]
     * @return array<string, array<int, array{emoji:string, anzahl:int, ich:bool, namen:string[], mehr:int}>>
     *         Schluessel "<model>|<id>"; Objekte ohne Reaktion fehlen. In `namen` steht "" fuer die eigene Person
     *         (die Oberflaeche setzt dafuer "Du" in der Sprache des Menschen).
     */
    public static function zusammenfassung(array $paare, int $meinId): array
    {
        $nachModell = [];
        foreach ($paare as [$model, $id]) {
            $nachModell[$model][] = (int)$id;
        }
        if ($nachModell === []) {
            return [];
        }
        $bedingung = ['or'];
        foreach ($nachModell as $model => $ids) {
            $bedingung[] = ['object_model' => $model, 'object_id' => array_values(array_unique($ids))];
        }
        $zeilen = (new Query())->from(self::TABELLE)->where($bedingung)->orderBy('created_at')->all();
        if ($zeilen === []) {
            return [];
        }

        $namen = [];
        $nutzer = User::find()->where(['id' => array_values(array_unique(array_column($zeilen, 'user_id')))])->all();
        foreach ($nutzer as $u) {
            $namen[(int)$u->id] = $u->displayName;
        }

        $reihenfolge = array_flip(self::modul()->emojis);
        $maxNamen = max(1, (int)self::modul()->maxNamen);
        $roh = [];
        foreach ($zeilen as $z) {
            $key = $z['object_model'] . '|' . $z['object_id'];
            $roh[$key][$z['emoji']][] = (int)$z['user_id'];
        }

        $ergebnis = [];
        foreach ($roh as $key => $nachEmoji) {
            $liste = [];
            foreach ($nachEmoji as $emoji => $userIds) {
                $ich = in_array($meinId, $userIds, true);
                $anzeige = $ich ? [''] : [];
                foreach ($userIds as $u) {
                    if ($u !== $meinId) {
                        $anzeige[] = $namen[$u] ?? '?';
                    }
                }
                $liste[] = [
                    'emoji' => (string)$emoji,
                    'anzahl' => count($userIds),
                    'ich' => $ich,
                    'namen' => array_slice($anzeige, 0, $maxNamen),
                    'mehr' => max(0, count($anzeige) - $maxNamen),
                ];
            }
            // Meistgenutzte zuerst, bei Gleichstand die Reihenfolge der Auswahl
            usort($liste, fn($a, $b) => [$b['anzahl'], $reihenfolge[$a['emoji']] ?? 99] <=> [$a['anzahl'], $reihenfolge[$b['emoji']] ?? 99]);
            $ergebnis[$key] = $liste;
        }
        return $ergebnis;
    }
}
