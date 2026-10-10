<?php

use humhub\commands\CronController;
use humhub\modules\space\widgets\Menu;

return [
    'id' => 'nexus-gesundheit',
    'class' => 'nexus\modules\gesundheit\Module',
    'namespace' => 'nexus\modules\gesundheit',
    'events' => [
        // Menuepunkt "Gesundheitswissen" im Kreis (Einstellung "kreis")
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => ['nexus\modules\gesundheit\Events', 'onKreisMenue']],
        // Taeglich: faellige Wiedervorlagen an die Admins
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_DAILY_RUN, 'callback' => ['nexus\modules\gesundheit\Events', 'onTaeglich']],
    ],
];
