<?php

use humhub\modules\space\widgets\Menu;

return [
    'id' => 'nexus-kalender',
    'class' => 'nexus\modules\kalender\Module',
    'namespace' => 'nexus\modules\kalender',
    'events' => [
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => ['nexus\modules\kalender\Events', 'onKreisMenue']],
    ],
];
