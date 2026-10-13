<?php

namespace nexus\modules\orientierung\services;

use humhub\modules\activity\models\Activity;
use humhub\modules\comment\models\Comment;
use humhub\modules\content\models\Content;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Throwable;
use Yii;
use yii\db\Query;

/**
 * Alle Beitraege und Kommentare eines Menschen, neueste zuerst -- gefiltert auf das, was der Lesende
 * sehen darf (dieselbe Pruefung wie ueberall im Portal: Content::canView). Nur veroeffentlichte Inhalte,
 * keine Entwuerfe, nichts aus dem Papierkorb, keine automatischen Aktivitaets-Meldungen.
 */
class Beitraege
{
    private const AUSZUG_ZEICHEN = 300;
    private const BEZUG_ZEICHEN = 80;

    /**
     * @return array{eintraege: array<int, array>, aeltere: bool}
     */
    public static function liste(User $person, User $leser, int $seite, int $proSeite): array
    {
        $seite = max(1, $seite);
        $beitraege = (new Query())->select(['id', 'created_at'])->from(Content::tableName())
            ->where(['created_by' => $person->id, 'state' => Content::STATE_PUBLISHED])
            ->andWhere(['!=', 'object_model', Activity::class])->all();
        $kommentare = (new Query())->select(['id', 'created_at'])->from(Comment::tableName())
            ->where(['created_by' => $person->id])->all();
        $kandidaten = array_merge(
            array_map(fn($z) => ['art' => 'beitrag'] + $z, $beitraege),
            array_map(fn($z) => ['art' => 'kommentar'] + $z, $kommentare)
        );
        usort($kandidaten, fn($a, $b) => strcmp((string)$b['created_at'], (string)$a['created_at']) ?: ((int)$b['id'] <=> (int)$a['id']));

        $bis = $seite * $proSeite;
        $sichtbar = [];
        foreach ($kandidaten as $k) {
            $eintrag = $k['art'] === 'beitrag' ? self::beitrag((int)$k['id'], $leser) : self::kommentar((int)$k['id'], $leser);
            if ($eintrag === null) {
                continue;
            }
            $sichtbar[] = $eintrag;
            if (count($sichtbar) > $bis) {
                break;
            }
        }
        return [
            'eintraege' => array_slice($sichtbar, ($seite - 1) * $proSeite, $proSeite),
            'aeltere' => count($sichtbar) > $bis,
        ];
    }

    private static function beitrag(int $id, User $leser): ?array
    {
        try {
            $content = Content::findOne(['id' => $id]);
            $model = $content?->getModel();
            if ($model === null || !$content->canView($leser)) {
                return null;
            }
            return [
                'art' => 'beitrag',
                'datum' => $content->created_at,
                'name' => (string)$model->getContentName(),
                'text' => self::kurz(self::klartext((string)$model->getContentDescription()), self::AUSZUG_ZEICHEN),
                'bezug' => '',
                'ort' => self::ort($content),
                'url' => $content->getUrl(),
            ];
        } catch (Throwable $e) {
            Yii::warning('nexus-orientierung: Beitrag ' . $id . ': ' . $e->getMessage(), 'nexus-orientierung');
            return null;
        }
    }

    private static function kommentar(int $id, User $leser): ?array
    {
        try {
            $kommentar = Comment::findOne(['id' => $id]);
            $content = $kommentar?->content;
            $model = $content?->getModel();
            if ($model === null || (int)$content->state !== Content::STATE_PUBLISHED || !$content->canView($leser)) {
                return null;
            }
            return [
                'art' => 'kommentar',
                'datum' => $kommentar->created_at,
                'name' => (string)$model->getContentName(),
                'text' => self::kurz(self::klartext((string)$kommentar->message), self::AUSZUG_ZEICHEN),
                'bezug' => self::kurz(self::klartext((string)$model->getContentDescription()), self::BEZUG_ZEICHEN),
                'ort' => self::ort($content),
                'url' => $kommentar->getUrl(),
            ];
        } catch (Throwable $e) {
            Yii::warning('nexus-orientierung: Kommentar ' . $id . ': ' . $e->getMessage(), 'nexus-orientierung');
            return null;
        }
    }

    private static function ort(Content $content): string
    {
        $behaelter = $content->container;
        if ($behaelter instanceof Space) {
            return (string)$behaelter->name;
        }
        if ($behaelter instanceof User) {
            return Texte::t('profil', ['{name}' => $behaelter->displayName]);
        }
        return '';
    }

    /** Markdown (Links, Videos, Erwaehnungen) als lesbarer Text -- sonst stuende "[...](oembed:...)" da */
    private static function klartext(string $text): string
    {
        try {
            return RichText::convert($text, RichText::FORMAT_PLAINTEXT);
        } catch (Throwable $e) {
            return $text;
        }
    }

    private static function kurz(string $text, int $max): string
    {
        $text = trim((string)preg_replace('/\s+/u', ' ', $text));
        return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)) . '…' : $text;
    }
}
