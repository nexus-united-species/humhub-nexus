<?php

use humhub\modules\nexusMarkdownPreview\Events;
use humhub\widgets\LayoutAddons;

return [
    'id' => 'nexus-markdown-preview',
    'class' => 'humhub\modules\nexusMarkdownPreview\Module',
    'namespace' => 'humhub\modules\nexusMarkdownPreview',
    'events' => [
        [LayoutAddons::class, LayoutAddons::EVENT_INIT, [Events::class, 'onLayoutAddonInit']],
    ],
];
