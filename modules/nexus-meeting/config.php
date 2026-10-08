<?php

use humhub\widgets\LayoutAddons;

return [
    'id' => 'nexus-meeting',
    'class' => 'nexus\modules\meeting\Module',
    'namespace' => 'nexus\modules\meeting',
    'events' => [
        ['class' => LayoutAddons::class, 'event' => LayoutAddons::EVENT_INIT, 'callback' => ['nexus\modules\meeting\Events', 'onLayoutAddonInit']],
    ],
];
