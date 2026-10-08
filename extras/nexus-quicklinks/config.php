<?php

use humhub\modules\nexusQuicklinks\Events;
use humhub\widgets\TopMenu;

return [
    'id' => 'nexus-quicklinks',
    'class' => 'humhub\modules\nexusQuicklinks\Module',
    'namespace' => 'humhub\modules\nexusQuicklinks',
    'events' => [
        [TopMenu::class, TopMenu::EVENT_INIT, [Events::class, 'onTopMenuInit']],
    ],
];
