<?php

use humhub\widgets\LayoutAddons;

return [
    'id' => 'nexus-themen',
    'class' => 'nexus\modules\themen\Module',
    'namespace' => 'nexus\modules\themen',
    'events' => [
        ['class' => LayoutAddons::class, 'event' => LayoutAddons::EVENT_INIT, 'callback' => ['nexus\modules\themen\Module', 'onLayoutAddonInit']],
    ],
];
