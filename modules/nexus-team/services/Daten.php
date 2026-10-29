<?php

namespace nexus\modules\team\services;

use humhub\modules\comment\models\Comment;
use humhub\modules\content\models\Content;
use humhub\modules\user\models\User;
use Throwable;
use Yii;

/**
 * Die Zahlen der Uebersichtsseite. Nur LESEN; nichts hier veraendert Inhalte. Bewusst keine Zeiten ausser
 * einer groben Einordnung des letzten Besuchs (heute / diese Woche / diesen Monat / laenger) -- keine Aktivitaets-
 * zeiten, kein Verlauf (Genesis-Pakt: niemand wird beobachtet).
 */
class Daten
{
    private const POST = 'humhub\modules\post\models\Post';
    private const SPACE = 'humhub\modules\space\models\Space';

    private static function botId(): int
    {
        return (int)getenv('ASSISTANT_USER_ID');
    }

    /**
     * Neue Mitglieder der letzten Tage, neueste zuerst.
     * @return array<int, array<string, mixed>>
     */
    public static function neueMitglieder(int $tage): array
    {
        $db = Yii::$app->db;
        $ab = date('Y-m-d H:i:s', time() - $tage * 86400);
        $zeilen = $db->createCommand(
            'SELECT u.id, u.guid, u.username, u.language, u.created_at, u.last_login, p.firstname, p.lastname
               FROM user u LEFT JOIN profile p ON p.user_id = u.id
              WHERE u.status = :s AND u.created_at >= :ab AND u.id <> :bot
              ORDER BY u.created_at DESC',
            [':s' => User::STATUS_ENABLED, ':ab' => $ab, ':bot' => self::botId()]
        )->queryAll();
        if ($zeilen === []) {
            return [];
        }
        $ids = array_map('intval', array_column($zeilen, 'id'));
        $vorgestellt = self::vorgestellte($ids);
        $nova = self::novaBegruesst($ids);
        $begruesst = self::teamBegruesst($ids);

        $ergebnis = [];
        foreach ($zeilen as $z) {
            $id = (int)$z['id'];
            $vorname = trim((string)$z['firstname']);
            $voll = trim($vorname . ' ' . (string)$z['lastname']);
            $ergebnis[] = [
                'id' => $id,
                'guid' => (string)$z['guid'],
                'username' => (string)$z['username'],
                'name' => $voll !== '' ? $voll : (string)$z['username'],
                'vorname' => $vorname !== '' ? $vorname : (string)$z['username'],
                'sprache' => (string)$z['language'],
                'seit' => (string)$z['created_at'],
                'besuch' => self::besuch($z['last_login']),
                'vorgestellt' => isset($vorgestellt[$id]),
                'nova' => $nova[$id] ?? null,
                'begruesst' => $begruesst[$id] ?? null,
            ];
        }
        return $ergebnis;
    }

    /** Gar nicht (null), heute, diese Woche, diesen Monat oder laenger her -- mehr zeigt die Seite nicht. */
    private static function besuch($letzter): string
    {
        $t = $letzter ? strtotime((string)$letzter) : false;
        if (!$t) {
            return 'nie';
        }
        $alter = time() - $t;
        return $alter < 86400 ? 'heute' : ($alter < 7 * 86400 ? 'woche' : ($alter < 31 * 86400 ? 'monat' : 'laenger'));
    }

    /** @return array<int, true> Wer hat im Vorstellungs-Kreis etwas geschrieben? */
    private static function vorgestellte(array $ids): array
    {
        $kreis = self::vorstellungenKreis();
        if ($kreis <= 0 || $ids === []) {
            return [];
        }
        $liste = implode(',', $ids);
        $spalte = Yii::$app->db->createCommand(
            "SELECT DISTINCT c.created_by FROM content c
               JOIN contentcontainer cc ON cc.id = c.contentcontainer_id AND cc.class = :space AND cc.pk = :kreis
              WHERE c.state = :st AND c.created_by IN ($liste)",
            [':space' => self::SPACE, ':kreis' => $kreis, ':st' => Content::STATE_PUBLISHED]
        )->queryColumn();
        return array_fill_keys(array_map('intval', $spalte), true);
    }

    private static function vorstellungenKreis(): int
    {
        $modul = Yii::$app->getModule('nexus-community-assistant');
        return $modul !== null && isset($modul->vorstellungenKreis) ? (int)$modul->vorstellungenKreis : 0;
    }

    /** @return array<int, string> user_id => Zeitpunkt von Novas Begruessung */
    private static function novaBegruesst(array $ids): array
    {
        try {
            $liste = implode(',', $ids);
            $zeilen = Yii::$app->db->createCommand("SELECT user_id, sent_at FROM nexus_willkommen WHERE user_id IN ($liste)")->queryAll();
            return array_column($zeilen, 'sent_at', 'user_id');
        } catch (Throwable $e) {
            return []; // ohne das Assistenten-Modul gibt es die Tabelle nicht
        }
    }

    /** @return array<int, array{von: string, am: string}> */
    private static function teamBegruesst(array $ids): array
    {
        $liste = implode(',', $ids);
        $zeilen = Yii::$app->db->createCommand(
            "SELECT b.user_id, b.am, p.firstname, u.username
               FROM nexus_team_begruesst b
               JOIN user u ON u.id = b.von_id LEFT JOIN profile p ON p.user_id = b.von_id
              WHERE b.user_id IN ($liste)"
        )->queryAll();
        $ergebnis = [];
        foreach ($zeilen as $z) {
            $name = trim((string)$z['firstname']) !== '' ? trim((string)$z['firstname']) : (string)$z['username'];
            $ergebnis[(int)$z['user_id']] = ['von' => $name, 'am' => (string)$z['am']];
        }
        return $ergebnis;
    }

    /** Offene Meldungen (Beitraege und Kommentare). Nur fuer Systemadmins auch die "nur Admins"-Meldungen. */
    public static function meldungen(bool $istAdmin, int $max = 20): array
    {
        $klasse = 'humhub\modules\reportcontent\models\ReportContent';
        if (!class_exists($klasse)) {
            return [];
        }
        $ergebnis = [];
        try {
            $abfrage = $klasse::find()->orderBy(['created_at' => SORT_DESC])->limit($max);
            if (!$istAdmin) {
                $abfrage->andWhere(['system_admin_only' => 0]);
            }
            foreach ($abfrage->all() as $r) {
                $inhalt = $r->content;
                if ($inhalt === null) {
                    continue;
                }
                $text = '';
                $art = 'beitrag';
                if ((int)$r->comment_id > 0) {
                    $kommentar = Comment::findOne((int)$r->comment_id);
                    $art = 'kommentar';
                    $text = $kommentar ? (string)$kommentar->message : '';
                } else {
                    $objekt = $inhalt->getPolymorphicRelation();
                    $text = $objekt !== null && isset($objekt->message) ? (string)$objekt->message : '';
                }
                $container = $inhalt->container;
                $ergebnis[] = [
                    'grund' => (int)$r->reason,
                    'art' => $art,
                    'zeit' => (string)$r->created_at,
                    'vorschau' => self::kurz($text, 160),
                    'kreis' => $container !== null ? (string)$container->getDisplayName() : '',
                    'content_id' => (int)$inhalt->id,
                ];
            }
        } catch (Throwable $e) {
            Yii::error('nexus-team: Meldungen nicht lesbar: ' . $e->getMessage(), 'nexus-team');
        }
        return $ergebnis;
    }

    /**
     * Beitraege ohne Kommentar. $ohneBot = true schliesst Novas eigene Beitraege (Einladungen, Aufrufe) aus;
     * $kreisId begrenzt auf einen Kreis (Support). Nur Kreise, die nicht privat sind (visibility > 0).
     */
    public static function beitraegeOhneAntwort(int $vonTage, int $bisTage, bool $ohneBot = true, ?int $kreisId = null, int $max = 15): array
    {
        $von = date('Y-m-d H:i:s', time() - $vonTage * 86400);
        $bis = date('Y-m-d H:i:s', time() - $bisTage * 86400);
        $bedingung = $kreisId !== null ? ' AND s.id = :kreis' : '';
        $bot = $ohneBot ? ' AND c.created_by <> :bot' : '';
        $params = [':post' => self::POST, ':space' => self::SPACE, ':von' => $von, ':bis' => $bis, ':st' => Content::STATE_PUBLISHED];
        if ($kreisId !== null) {
            $params[':kreis'] = $kreisId;
        }
        if ($ohneBot) {
            $params[':bot'] = self::botId();
        }
        $zeilen = Yii::$app->db->createCommand(
            "SELECT c.id AS content_id, c.created_at, p.message, s.name AS kreis
               FROM content c
               JOIN post p ON p.id = c.object_id AND c.object_model = :post
               JOIN contentcontainer cc ON cc.id = c.contentcontainer_id AND cc.class = :space
               JOIN space s ON s.id = cc.pk AND s.status = 1 AND s.visibility > 0
              WHERE c.state = :st AND c.created_at BETWEEN :von AND :bis$bot$bedingung
                AND NOT EXISTS (SELECT 1 FROM comment k WHERE k.object_model = c.object_model AND k.object_id = c.object_id)
              ORDER BY c.created_at DESC LIMIT " . (int)$max,
            $params
        )->queryAll();
        return array_map(fn($z) => [
            'content_id' => (int)$z['content_id'],
            'zeit' => (string)$z['created_at'],
            'vorschau' => self::kurz((string)$z['message'], 160),
            'kreis' => (string)$z['kreis'],
        ], $zeilen);
    }

    /** Offene Support-Anfragen: Beitraege im Support-Kreis ohne Kommentar (auch Novas, sie legt sie an). */
    public static function supportAnfragen(): array
    {
        $hilfe = Yii::$app->getModule('nexus-hilfe');
        $kreis = $hilfe !== null && method_exists($hilfe, 'supportKreis') ? $hilfe->supportKreis() : null;
        return $kreis === null ? [] : self::beitraegeOhneAntwort(30, 0, false, (int)$kreis->id);
    }

    /** Kreise, in denen seit mehr als $tage Tagen nichts Neues stand (ausser von Nova). */
    public static function ruhigeKreise(int $tage, int $max = 15): array
    {
        $grenze = date('Y-m-d H:i:s', time() - $tage * 86400);
        $zeilen = Yii::$app->db->createCommand(
            "SELECT s.id, s.name, s.url, MAX(c.created_at) AS letzter
               FROM space s
               LEFT JOIN contentcontainer cc ON cc.pk = s.id AND cc.class = :space
               LEFT JOIN content c ON c.contentcontainer_id = cc.id AND c.state = :st AND c.created_by <> :bot
              WHERE s.status = 1
              GROUP BY s.id, s.name, s.url
             HAVING MAX(c.created_at) IS NULL OR MAX(c.created_at) < :grenze
              ORDER BY (MAX(c.created_at) IS NULL) DESC, MAX(c.created_at) ASC LIMIT " . (int)$max,
            [':space' => self::SPACE, ':st' => Content::STATE_PUBLISHED, ':bot' => self::botId(), ':grenze' => $grenze]
        )->queryAll();
        return array_map(fn($z) => [
            'name' => (string)$z['name'],
            'url' => (string)$z['url'],
            'tage' => $z['letzter'] ? (int)floor((time() - strtotime((string)$z['letzter'])) / 86400) : null,
        ], $zeilen);
    }

    /** Reiner Anfang eines Markdown-Textes, ohne Zeilenumbrueche. */
    private static function kurz(string $text, int $max): string
    {
        $text = preg_replace('/\s+/u', ' ', strip_tags($text));
        $text = preg_replace('/!?\[([^\]]*)\]\([^)]*\)/u', '$1', (string)$text);
        $text = trim((string)$text);
        return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max)) . ' …' : $text;
    }

    public static function markiereBegruesst(int $userId, int $vonId): void
    {
        Yii::$app->db->createCommand(
            'INSERT IGNORE INTO nexus_team_begruesst (user_id, von_id, am) VALUES (:u, :v, :t)',
            [':u' => $userId, ':v' => $vonId, ':t' => date('Y-m-d H:i:s')]
        )->execute();
    }

    public static function nimmBegruessungZurueck(int $userId): void
    {
        Yii::$app->db->createCommand()->delete('nexus_team_begruesst', ['user_id' => $userId])->execute();
    }
}
