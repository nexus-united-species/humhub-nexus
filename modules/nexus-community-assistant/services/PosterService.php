<?php

namespace nexus\modules\communityAssistant\services;

use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use RuntimeException;
use Yii;

/**
 * Alles, was der Assistent selbst als Beitrag/Kommentar veroeffentlicht,
 * laeuft ueber diese Stelle -- ein einziger Codepfad statt verstreuter
 * Post-Erzeugung in mehreren Skripten.
 *
 * Wichtig: Content::created_by fuellt sich automatisch aus
 * Yii::$app->user->id (siehe humhub Content::beforeSave). Wir setzen die
 * Identitaet deshalb hier zentral auf das Assistenten-Konto, statt es
 * jedem Aufrufer zu ueberlassen.
 */
class PosterService
{
    public function __construct(
        private readonly int $assistantUserId,
    ) {
    }

    /**
     * Postet eine Nachricht als der Assistent in den angegebenen Space.
     * Gibt die neue Post-ID zurueck.
     */
    public function postInSpace(int $spaceId, string $message): int
    {
        $bot = $this->assistant();
        $space = Space::findOne(['id' => $spaceId]);
        if ($space === null) {
            throw new RuntimeException("Space {$spaceId} nicht gefunden.");
        }

        Yii::$app->user->setIdentity($bot);

        $post = new Post($space);
        $post->message = $message;
        if (!$post->save()) {
            throw new RuntimeException('Beitrag konnte nicht gespeichert werden: ' . json_encode($post->errors));
        }

        return (int)$post->id;
    }

    /**
     * Postet einen Kommentar als der Assistent auf ein bestehendes
     * ContentActiveRecord-Objekt (z.B. Post, Wiki-Seite).
     */
    public function kommentieren(string $objectModel, int $objectId, string $message): void
    {
        $bot = $this->assistant();
        Yii::$app->user->setIdentity($bot);

        $kommentar = new \humhub\modules\comment\models\Comment([
            'message' => $message,
            'object_model' => $objectModel,
            'object_id' => $objectId,
        ]);
        if (!$kommentar->save()) {
            throw new RuntimeException('Kommentar konnte nicht gespeichert werden: ' . json_encode($kommentar->errors));
        }
    }

    /**
     * Schickt zusaetzlich zum oeffentlichen Kommentar dieselbe Antwort als
     * private Nachricht in den Posteingang -- loest HumHubs eigene
     * Benachrichtigung aus (Glocke/E-Mail je nach den Einstellungen des
     * Empfaengers), damit man nicht auf das naechste Neuladen der Seite
     * warten muss, um die Antwort zu bemerken (Vorgabe Josh 09.09.2026).
     * Umgeht bewusst das Web-Formular (CreateMessage) und dessen
     * Berechtigungspruefung fuer den ABSENDER -- der Assistent ist kein
     * Systemadmin, soll aber trotzdem antworten duerfen. Gleiches Prinzip
     * wie beim direkten Post/Comment-Aufruf oben.
     */
    public function nachrichtSenden(int $empfaengerUserId, string $titel, string $text): void
    {
        $bot = $this->assistant();
        $empfaenger = User::findOne(['id' => $empfaengerUserId]);
        if ($empfaenger === null) {
            throw new RuntimeException("Empfaenger (id={$empfaengerUserId}) nicht gefunden.");
        }

        Yii::$app->user->setIdentity($bot);

        $nachricht = new Message(['title' => $titel]);
        if (!$nachricht->save()) {
            throw new RuntimeException('Nachricht konnte nicht angelegt werden: ' . json_encode($nachricht->errors));
        }

        $nachricht->addRecepient($empfaenger, false, true);
        $nachricht->addRecepient($bot, true, false);

        $eintrag = MessageEntry::createForMessage($nachricht, $bot, $text);
        if (!$eintrag->save()) {
            throw new RuntimeException('Nachrichtentext konnte nicht gespeichert werden: ' . json_encode($eintrag->errors));
        }
        $eintrag->notify(true);
    }

    /**
     * Antwortet in einer BESTEHENDEN Konversation (Direktnachricht an den
     * Assistenten, siehe DirectMessageReplyService) -- im Unterschied zu
     * nachrichtSenden() wird hier keine neue Konversation angelegt.
     */
    public function antwortenInKonversation(int $konversationId, string $text): void
    {
        $bot = $this->assistant();
        $nachricht = Message::findOne($konversationId);
        if ($nachricht === null) {
            throw new RuntimeException("Konversation {$konversationId} nicht gefunden.");
        }

        Yii::$app->user->setIdentity($bot);

        $eintrag = MessageEntry::createForMessage($nachricht, $bot, $text);
        if (!$eintrag->save()) {
            throw new RuntimeException('Antwort konnte nicht gespeichert werden: ' . json_encode($eintrag->errors));
        }
        $eintrag->notify(true);
    }

    private function assistant(): User
    {
        $bot = User::findOne(['id' => $this->assistantUserId]);
        if ($bot === null) {
            throw new RuntimeException("Assistenten-Konto (id={$this->assistantUserId}) nicht gefunden.");
        }

        return $bot;
    }
}
