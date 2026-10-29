<?php

use humhub\widgets\TopMenu;

return [
    'id' => 'nexus-team',
    'class' => 'nexus\modules\team\Module',
    'namespace' => 'nexus\modules\team',
    'events' => [
        // "Team" oben in der Navigation -- nur fuer das Community-Team und Systemadmins
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => ['nexus\modules\team\Events', 'onTopMenuInit']],
    ],
];
