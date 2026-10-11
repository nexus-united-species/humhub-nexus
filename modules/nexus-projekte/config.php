<?php

use humhub\commands\CronController;
use humhub\modules\dashboard\widgets\Sidebar;
use humhub\modules\space\widgets\Menu;
use humhub\modules\space\widgets\Sidebar as KreisSeitenleiste;
use humhub\widgets\TopMenu;

return [
    'id' => 'nexus-projekte',
    'class' => 'nexus\modules\projekte\Module',
    'namespace' => 'nexus\modules\projekte',
    'events' => [
        // Menuepunkt "Projekte" in Kreisen, in denen das Modul eingeschaltet ist
        ['class' => Menu::class, 'event' => Menu::EVENT_INIT, 'callback' => ['nexus\modules\projekte\Events', 'onKreisMenue']],
        // "Meine Aufgaben" in der oberen Leiste und auf der Startseite
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => ['nexus\modules\projekte\Events', 'onObenMenue']],
        ['class' => Sidebar::class, 'event' => Sidebar::EVENT_INIT, 'callback' => ['nexus\modules\projekte\Events', 'onStartseite']],
        // ... und rechts in der Seitenleiste jedes Kreises (nur die Aufgaben dieses Kreises)
        ['class' => KreisSeitenleiste::class, 'event' => KreisSeitenleiste::EVENT_INIT, 'callback' => ['nexus\modules\projekte\Events', 'onKreisSeitenleiste']],
        // Taeglich: Fristen und Abstimmungen erinnern
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_DAILY_RUN, 'callback' => ['nexus\modules\projekte\Events', 'onTaeglich']],
    ],
];
