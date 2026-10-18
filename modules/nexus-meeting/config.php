<?php

use humhub\widgets\LayoutAddons;
use humhub\widgets\TopMenu;

return [
    'id' => 'nexus-meeting',
    'class' => 'nexus\modules\meeting\Module',
    'namespace' => 'nexus\modules\meeting',
    'events' => [
        ['class' => LayoutAddons::class, 'event' => LayoutAddons::EVENT_INIT, 'callback' => ['nexus\modules\meeting\Events', 'onLayoutAddonInit']],
        // "Offene Tür" oben in der Leiste (nur fuer die Menschen aus den Einstellungen)
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => ['nexus\modules\meeting\Events', 'onObenMenue']],
    ],
];
