<?php

use humhub\commands\CronController;
use humhub\modules\mail\widgets\ConversationEntry;
use humhub\modules\mail\widgets\Messages;
use humhub\modules\ui\menu\widgets\Menu;
use humhub\modules\user\widgets\AccountTopMenu;
use humhub\widgets\LayoutAddons;
use yii\base\Widget;

return [
    'id' => 'nexus-arbeitszeit',
    'class' => 'nexus\modules\arbeitszeit\Module',
    'namespace' => 'nexus\modules\arbeitszeit',
    'events' => [
        // Eintraege im Profil-Menue oben rechts (PC und Handy).
        ['class' => AccountTopMenu::class, 'event' => Menu::EVENT_RUN, 'callback' => ['nexus\modules\arbeitszeit\Events', 'onAccountMenu']],
        // Benachrichtigungen des Assistenten: Knoepfe je Nachricht, Hinweis statt Antwortfeld (siehe BenachrichtigungAnzeige)
        ['class' => ConversationEntry::class, 'event' => Widget::EVENT_AFTER_RUN, 'callback' => ['nexus\modules\arbeitszeit\Events', 'onNachrichtGezeigt']],
        ['class' => Messages::class, 'event' => Widget::EVENT_AFTER_RUN, 'callback' => ['nexus\modules\arbeitszeit\Events', 'onUnterhaltungGezeigt']],
        ['class' => LayoutAddons::class, 'event' => LayoutAddons::EVENT_INIT, 'callback' => ['nexus\modules\arbeitszeit\Events', 'onLayoutAddonInit']],
        // Excel-Bericht in der Nextcloud stuendlich nachziehen (nur bei Aenderungen, siehe NextcloudBericht).
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_HOURLY_RUN, 'callback' => ['nexus\modules\arbeitszeit\Events', 'onHourly']],
    ],
];
