<?php

namespace nexus\modules\teilen\services;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\widgets\richtext\converter\RichTextToShortTextConverter;
use humhub\modules\nexusTranslate\SprachErkennung;
use humhub\modules\nexusTranslate\UebersetzungsSpeicher;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Throwable;
use Yii;

/**
 * Was laesst sich teilen, wer darf freigeben, und wie lautet der Text in einer Sprache?
 * Nur Beitraege und Wiki-Seiten (Josh, 02.10.2026) -- Umfragen, Termine usw. bewusst nicht.
 */
class Inhalt
{
    public const WIKI = 'humhub\modules\wiki\models\WikiPage';

    public static function istWiki($record): bool
    {
        return is_object($record) && is_a($record, self::WIKI);
    }

    public static function unterstuetzt($record): bool
    {
        if (self::istWiki($record)) {
            return true;
        }
        return $record instanceof Post && trim((string)$record->message) !== '';
    }

    /** Beitrag/Wiki-Seite zu einer Inhalts-Nummer; null, wenn es keinen gibt oder er nicht passt. */
    public static function ausContentId(int $contentId): ?ContentActiveRecord
    {
        $content = Content::findOne(['id' => $contentId]);
        if ($content === null) {
            return null;
        }
        try {
            $record = $content->getPolymorphicRelation();
        } catch (Throwable $e) {
            return null;
        }
        return self::unterstuetzt($record) ? $record : null;
    }

    /**
     * Freigeben duerfen der Verfasser und Admins (Josh: "beide") -- Admins heisst hier:
     * Portal-Admins und die Admins/Besitzer des Kreises, in dem der Inhalt steht.
     */
    public static function darfFreigeben(ContentActiveRecord $record, ?User $user): bool
    {
        if ($user === null || !$record->content->canView($user)) {
            return false;
        }
        if ((int)$record->content->created_by === (int)$user->id || $user->isSystemAdmin()) {
            return true;
        }
        $kreis = $record->content->container;
        return $kreis instanceof Space && $kreis->isAdmin($user->id);
    }

    /** Schaufenster: nur Portal-Admins (Josh, 03.10.2026: "admins eine einfache Moeglichkeit"). */
    public static function darfSchaufenster(?User $user): bool
    {
        return $user !== null && $user->isSystemAdmin();
    }

    /**
     * Erwaehnungen ("@Name", im Text als [Name](mention:guid "/u/...")) werden fuer die
     * Oeffentlichkeit zu "ein Mitglied" -- wer erwaehnt wurde, hat der Veroeffentlichung nicht
     * zugestimmt. Laeuft NACH der Uebersetzung, damit auch uebersetzte Texte erfasst sind.
     */
    public static function anonymisieren(string $markdown, string $einMitglied): string
    {
        return (string)preg_replace('/@?\[[^\]]*\]\(mention:[^)]*\)/u', $einMitglied, $markdown);
    }

    /**
     * Namen von Mitgliedern, die einfach so im Text stehen (ohne @). Die erkennt keine Maschine
     * sicher als "Person" -- deshalb nur ein Abgleich mit den Namen aller Mitglieder, und der
     * Admin entscheidet in der Vorschau. Vornamen erst ab 3 Buchstaben.
     *
     * @return string[]
     */
    public static function namenImText(string $text): array
    {
        $kandidaten = [];
        $zeilen = (new \yii\db\Query())
            ->select(['p.firstname', 'p.lastname'])
            ->from('user u')
            ->innerJoin('profile p', 'p.user_id = u.id')
            ->where(['u.status' => User::STATUS_ENABLED])
            ->all();
        foreach ($zeilen as $z) {
            foreach ([trim($z['firstname'] . ' ' . $z['lastname']), (string)$z['firstname'], (string)$z['lastname']] as $name) {
                $name = trim($name);
                if (mb_strlen($name) >= 3 && !preg_match('/^\(|^nexus/i', $name)) {
                    $kandidaten[$name] = true;
                }
            }
        }
        // Laengere zuerst, damit "Vorname Nachname" vor "Vorname" gefunden und gemeldet wird.
        $namen = array_keys($kandidaten);
        usort($namen, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $treffer = [];
        foreach ($namen as $name) {
            if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($name, '/') . '(?![\p{L}\p{N}])/u', $text)) {
                $schonDrin = false;
                foreach ($treffer as $t) {
                    if (mb_stripos($t, $name) !== false) {
                        $schonDrin = true;
                        break;
                    }
                }
                if (!$schonDrin) {
                    $treffer[] = $name;
                }
            }
        }
        return $treffer;
    }

    public static function titel(ContentActiveRecord $record): string
    {
        return self::istWiki($record) ? (string)$record->title : '';
    }

    /** Der Text als Markdown (Beitrag: Nachricht, Wiki: neueste Fassung). */
    public static function text(ContentActiveRecord $record): string
    {
        if (self::istWiki($record)) {
            return (string)($record->latestRevision->content ?? '');
        }
        return (string)$record->message;
    }

    public static function sprache(ContentActiveRecord $record): ?string
    {
        if (!class_exists(SprachErkennung::class)) {
            return null;
        }
        return SprachErkennung::erkenne(trim(self::titel($record) . "\n" . self::text($record)));
    }

    /**
     * Titel und Text in der Zielsprache. Nutzt denselben Speicher wie die automatische Uebersetzung
     * im Stream (gleiche Schluessel: post / wikis / wikit) -- wer den Beitrag schon uebersetzt
     * gelesen hat, loest hier keine neue Uebersetzung aus. Geht etwas schief, bleibt das Original.
     *
     * @return array{titel: string, text: string, sprache: ?string, uebersetzt: bool}
     */
    public static function fassung(ContentActiveRecord $record, ?string $ziel): array
    {
        $titel = self::titel($record);
        $text = self::text($record);
        $von = self::sprache($record);
        $original = ['titel' => $titel, 'text' => $text, 'sprache' => $von, 'uebersetzt' => false];
        if ($ziel === null || $von === null || $von === $ziel || trim($text) === ''
            || !class_exists(UebersetzungsSpeicher::class) || Yii::$app->getModule('nexus-translate') === null) {
            return $original;
        }
        try {
            if (self::istWiki($record)) {
                $text = UebersetzungsSpeicher::markdown('wikis', (int)$record->id, $text, $ziel);
                $titel = $titel === '' ? '' : UebersetzungsSpeicher::markdown('wikit', (int)$record->id, $titel, $ziel);
            } else {
                $text = UebersetzungsSpeicher::markdown('post', (int)$record->id, $text, $ziel);
            }
        } catch (Throwable $e) {
            Yii::warning('nexus-teilen: Uebersetzung nicht moeglich, Original wird gezeigt: ' . $e->getMessage(), 'nexus-teilen');
            return $original;
        }
        return ['titel' => $titel, 'text' => $text, 'sprache' => $ziel, 'uebersetzt' => true];
    }

    /** Reiner Text ohne Formatierung, gekuerzt -- fuer Messenger und Vorschaubilder. */
    public static function kurztext(string $markdown, int $max): string
    {
        $kurz = RichTextToShortTextConverter::process($markdown, [RichTextToShortTextConverter::OPTION_MAX_LENGTH => $max]);
        $kurz = html_entity_decode((string)$kurz, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Platzhalter fuer Bilder ("[Image]", "[Bild]" ...) sagen im Messenger nichts.
        return trim(preg_replace(['/\[(?:Image|Bild|Imagen|Imagem|File|Datei|Archivo)\]/iu', '/\s{2,}/u'], ['', ' '], $kurz));
    }

    /** Titel fuer Vorschau/Teilen: Wiki-Titel, sonst die erste Textzeile ohne Formatierung. */
    public static function ueberschrift(string $titel, string $markdown, int $max = 90): string
    {
        if ($titel !== '') {
            return $titel;
        }
        foreach (preg_split('/\R/u', $markdown) as $zeile) {
            $zeile = self::kurztext($zeile, $max);
            if ($zeile !== '') {
                return $zeile;
            }
        }
        return 'N.E.X.U.S.';
    }
}
