<?php

use humhub\modules\post\models\Post;
use humhub\widgets\LayoutAddons;

return [
    'id' => 'nexus-themen',
    'class' => 'nexus\modules\themen\Module',
    'namespace' => 'nexus\modules\themen',
    'events' => [
        ['class' => LayoutAddons::class, 'event' => LayoutAddons::EVENT_INIT, 'callback' => ['nexus\modules\themen\Module', 'onLayoutAddonInit']],
        // Thema beim Bearbeiten eines Beitrags aendern (Feld "nexusThema" aus unserem Skript)
        ['class' => Post::class, 'event' => Post::EVENT_AFTER_UPDATE, 'callback' => ['nexus\modules\themen\Module', 'onBeitragGespeichert']],
    ],
];
