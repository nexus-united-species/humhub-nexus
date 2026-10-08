<?php

/**
 * Modul-Registrierung fuer den ModuleAutoLoader (HumHub scannt
 * '/data/modules-custom' automatisch nach Ordnern mit dieser Datei --
 * siehe moduleAutoloadPaths in den HumHub-Grundeinstellungen).
 */

use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\ui\menu\widgets\Menu;

return [
    'id' => 'nexus-supporter-bridge',
    'class' => 'nexus\modules\supporterBridge\Module',
    'namespace' => 'nexus\modules\supporterBridge',
    'events' => [
        ['class' => AdminMenu::class, 'event' => Menu::EVENT_RUN, 'callback' => ['nexus\modules\supporterBridge\Events', 'onAdminMenuInit']],
    ],
];
