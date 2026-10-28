<?php

use humhub\modules\mail\models\AbstractMessageEntry;
use humhub\widgets\LayoutAddons;
use yii\db\ActiveRecord;

return [
    'id' => 'nexus-reaktionen',
    'class' => 'nexus\modules\reaktionen\Module',
    'namespace' => 'nexus\modules\reaktionen',
    'events' => [
        // Skript fuer die Emoji-Auswahl auf jeder Seite (nur Angemeldete)
        ['class' => LayoutAddons::class, 'event' => LayoutAddons::EVENT_INIT, 'callback' => ['nexus\modules\reaktionen\Events', 'onLayoutAddonInit']],
        // Neue Nachricht, die auf eine andere antwortet: Bezug merken
        ['class' => AbstractMessageEntry::class, 'event' => ActiveRecord::EVENT_AFTER_INSERT, 'callback' => ['nexus\modules\reaktionen\Events', 'onNachrichtGespeichert']],
    ],
];
