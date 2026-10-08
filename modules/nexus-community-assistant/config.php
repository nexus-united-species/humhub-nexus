<?php

use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\ui\menu\widgets\Menu;
use humhub\modules\user\models\Mentioning;
use yii\db\ActiveRecord;

return [
    'id' => 'nexus-community-assistant',
    'class' => 'nexus\modules\communityAssistant\Module',
    'namespace' => 'nexus\modules\communityAssistant',
    'events' => [
        ['class' => AdminMenu::class, 'event' => Menu::EVENT_RUN, 'callback' => ['nexus\modules\communityAssistant\Events', 'onAdminMenuInit']],
        // Feuert bei jeder neuen @-Erwaehnung (Zeile in user_mentioning) --
        // unser Haken fuer die Direktfrage-Funktion (siehe Events::onMentioning).
        ['class' => Mentioning::class, 'event' => ActiveRecord::EVENT_AFTER_INSERT, 'callback' => ['nexus\modules\communityAssistant\Events', 'onMentioning']],
        // Feuert bei jeder neuen Nachricht im Mail-Modul -- unser Haken fuer
        // die Direktnachricht-Funktion (siehe Events::onMessageEntry).
        ['class' => MessageEntry::class, 'event' => ActiveRecord::EVENT_AFTER_INSERT, 'callback' => ['nexus\modules\communityAssistant\Events', 'onMessageEntry']],
    ],
];
