<?php

namespace nexus\modules\communityAssistant;

use nexus\modules\communityAssistant\services\DirectMessageReplyService;
use nexus\modules\communityAssistant\services\MentionReplyService;
use Yii;

class Events
{
    public static function onAdminMenuInit($event)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isSystemAdmin()) {
            return;
        }

        $event->sender->addItem([
            'label' => 'Nova (KI-Assistent)',
            'icon' => 'android',
            'url' => ['/nexus-community-assistant/admin/index'],
            'sortOrder' => 960,
        ]);
    }

    /**
     * Feuert bei jeder neuen Zeile in user_mentioning -- also bei jeder
     * @-Erwaehnung, egal welchen Nutzers. Der eigentliche Filter (wurde
     * ausgerechnet der Assistent erwaehnt?) passiert im Service, nicht hier
     * -- Events.php bleibt bewusst duenn und delegiert sofort.
     */
    public static function onMentioning($event)
    {
        try {
            (new MentionReplyService())->verarbeiten($event->sender);
        } catch (\Throwable $e) {
            Yii::error('nexus-community-assistant: Direktfrage-Verarbeitung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Feuert bei jedem neuen Nachrichten-Eintrag im Mail-Modul (jede
     * geschriebene Nachricht in jeder Konversation). Der Filter (ist der
     * Assistent beteiligt, 1:1?) passiert im Service.
     */
    public static function onMessageEntry($event)
    {
        try {
            (new DirectMessageReplyService())->verarbeiten($event->sender);
        } catch (\Throwable $e) {
            Yii::error('nexus-community-assistant: Direktnachricht-Verarbeitung fehlgeschlagen: ' . $e->getMessage());
        }
    }
}
