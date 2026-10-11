<?php

use humhub\commands\CronController;
use humhub\modules\space\widgets\Menu;

return [
    'id' => 'nexus-wissen',
    'class' => 'nexus\modules\wissen\Module',
    'namespace' => 'nexus\modules\wissen',
    'events' => [
        // Menuepunkt je Sammlung in ihrem Kreis
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => ['nexus\modules\wissen\Events', 'onKreisMenue']],
        // Taeglich: faellige Wiedervorlagen an die Bearbeitenden
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_DAILY_RUN, 'callback' => ['nexus\modules\wissen\Events', 'onTaeglich']],
    ],
    // php yii wissen/liste | wissen/import <sammlung> | wissen/schlagworte <sammlung>
    'consoleControllerMap' => [
        'wissen' => 'nexus\modules\wissen\commands\WissenController',
    ],
];
