<?php

use humhub\modules\nexusVoice\Events;
use humhub\widgets\LayoutAddons;

return [
    'id' => 'nexus-voice',
    'class' => 'humhub\modules\nexusVoice\Module',
    'namespace' => 'humhub\modules\nexusVoice',
    'events' => [
        [LayoutAddons::class, LayoutAddons::EVENT_INIT, [Events::class, 'onLayoutAddonInit']],
    ],
];
