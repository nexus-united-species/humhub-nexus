<?php

use humhub\commands\CronController;
use humhub\modules\ui\menu\widgets\Menu;
use humhub\modules\user\widgets\AccountTopMenu;

return [
    'id' => 'nexus-arbeitszeit',
    'class' => 'nexus\modules\arbeitszeit\Module',
    'namespace' => 'nexus\modules\arbeitszeit',
    'events' => [
        // Eintraege im Profil-Menue oben rechts (PC und Handy).
        ['class' => AccountTopMenu::class, 'event' => Menu::EVENT_RUN, 'callback' => ['nexus\modules\arbeitszeit\Events', 'onAccountMenu']],
        // Excel-Bericht in der Nextcloud stuendlich nachziehen (nur bei Aenderungen, siehe NextcloudBericht).
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_HOURLY_RUN, 'callback' => ['nexus\modules\arbeitszeit\Events', 'onHourly']],
    ],
];
