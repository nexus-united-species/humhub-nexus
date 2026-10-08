<?php

use humhub\modules\nexusNotifBanner\Events;
use humhub\widgets\LayoutAddons;

return [
    'id' => 'nexus-notif-banner',
    'class' => 'humhub\modules\nexusNotifBanner\Module',
    'namespace' => 'humhub\modules\nexusNotifBanner',
    'events' => [
        [LayoutAddons::class, LayoutAddons::EVENT_INIT, [Events::class, 'onLayoutAddonInit']],
    ],
];
