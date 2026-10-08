<?php

use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\space\widgets\Menu as SpaceMenu;
use humhub\modules\ui\menu\widgets\Menu;

return [
    'id' => 'nexus-protected-library',
    'class' => 'nexus\modules\protectedLibrary\Module',
    'namespace' => 'nexus\modules\protectedLibrary',
    'events' => [
        ['class' => AdminMenu::class, 'event' => Menu::EVENT_RUN, 'callback' => ['nexus\modules\protectedLibrary\Events', 'onAdminMenuInit']],
        // Eigener Menuepunkt in der linken Seitenleiste -- aber nur im
        // Unterstuetzer-Space selbst (siehe Events::onSpaceMenuInit),
        // sonst wuerde er in jedem Space der Community auftauchen.
        ['class' => SpaceMenu::class, 'event' => SpaceMenu::EVENT_INIT, 'callback' => ['nexus\modules\protectedLibrary\Events', 'onSpaceMenuInit']],
    ],
];
