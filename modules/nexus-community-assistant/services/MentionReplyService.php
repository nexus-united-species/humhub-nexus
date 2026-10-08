<?php

namespace nexus\modules\communityAssistant\services;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentAddonActiveRecord;
use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;
use humhub\modules\user\models\Mentioning;
use Yii;

/**
 * Direktfrage-Funktion: erwaehnt jemand das Assistenten-Konto per @-Tag in
 * einem Beitrag oder Kommentar, antwortet der Assistent als Kommentar auf
 * genau dieses Objekt. Bewusst KEIN automatisches Reagieren auf
 * Stichwoerter -- nur echte @-Erwaehnung durch einen Menschen (Vorgabe
 * Josh 09.09.2026).
 */
class MentionReplyService
{
    public function verarbeiten(Mentioning $mentioning): void
    {
        $assistentId = (int)getenv('ASSISTANT_USER_ID');
        if ($assistentId <= 0 || (int)$mentioning->user_id !== $assistentId) {
            return;
        }

        if (!$this->alsErstmaligMarkieren((int)$mentioning->id)) {
            return;
        }

        $quelle = $mentioning->getPolymorphicRelation();

        if ($quelle instanceof ContentActiveRecord) {
            $zielModel = get_class($quelle);
            $zielId = (int)$quelle->getPrimaryKey();
            $rohtext = (string)($quelle->message ?? '');
            $urheberId = $quelle->content->created_by ?? null;
        } elseif ($quelle instanceof ContentAddonActiveRecord) {
            $zielModel = $quelle->object_model;
            $zielId = (int)$quelle->object_id;
            $rohtext = (string)($quelle->message ?? '');
            $urheberId = $quelle->user_id ?? null;
        } else {
            return;
        }

        // Sicherheitsnetz: der Assistent soll nie auf sich selbst antworten
        // (sollte durch die eigene Antwortlogik ohnehin nie vorkommen, da wir
        // in eigenen Antworten niemanden erwaehnen -- trotzdem geprueft).
        if ($urheberId !== null && (int)$urheberId === $assistentId) {
            return;
        }

        if ($rohtext === '') {
            return;
        }

        $frage = RichTextToPlainTextConverter::process($rohtext);
        if (trim($frage) === '') {
            return;
        }

        try {
            $antwort = (new AiService())->frage($frage, AssistentPersona::systemAnweisung($frage));
        } catch (\Throwable $e) {
            Yii::error('nexus-community-assistant: KI-Antwort auf Direktfrage fehlgeschlagen: ' . $e->getMessage());
            return;
        }

        [$antwort, $anMenschen] = Weitergabe::zerlegen($antwort);
        $poster = new PosterService($assistentId);
        $poster->kommentieren($zielModel, $zielId, $antwort);
        if ($anMenschen) {
            $link = $this->permalink($zielModel, $zielId);
            Weitergabe::anMenschen(
                $urheberId !== null ? \humhub\modules\user\models\User::findOne(['id' => (int)$urheberId]) : null,
                $frage,
                $antwort,
                'öffentlich per @-Erwähnung' . ($link !== null ? ': ' . $link : '')
            );
        }

        // Zusaetzlich als private Nachricht in den Posteingang -- loest
        // HumHubs eigene Benachrichtigung aus, damit die Antwort nicht erst
        // beim naechsten Neuladen der Seite auffaellt (Vorgabe Josh 09.09.2026).
        if ($urheberId !== null) {
            try {
                $link = $this->permalink($zielModel, $zielId);
                $text = $antwort . ($link !== null ? "\n\n" . $link : '');
                $poster->nachrichtSenden((int)$urheberId, 'Deine Frage an Nova', $text);
            } catch (\Throwable $e) {
                Yii::error('nexus-community-assistant: Nachricht in den Posteingang fehlgeschlagen: ' . $e->getMessage());
            }
        }
    }

    private function permalink(string $zielModel, int $zielId): ?string
    {
        $objekt = $zielModel::findOne($zielId);
        if ($objekt === null || !isset($objekt->content)) {
            return null;
        }

        return $objekt->content->getUrl(true);
    }

    private function alsErstmaligMarkieren(int $mentioningId): bool
    {
        try {
            Yii::$app->db->createCommand()->insert('nexus_mention_reply', [
                'mentioning_id' => $mentioningId,
                'created_at' => date('Y-m-d H:i:s'),
            ])->execute();
            return true;
        } catch (\yii\db\IntegrityException) {
            return false;
        }
    }
}
