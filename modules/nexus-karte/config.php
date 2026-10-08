<?php

use humhub\components\View;
use humhub\modules\space\widgets\HeaderControlsMenu;
use humhub\widgets\TopMenu;

return [
    'id' => 'nexus-karte',
    'class' => 'nexus\modules\karte\Module',
    'namespace' => 'nexus\modules\karte',
    'events' => [
        // "Karte" oben in der Navigation, direkt hinter "Kreise".
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => ['nexus\modules\karte\Events', 'onTopMenuInit']],
        // Hinweis auf die Karte oben auf der Kreise-Uebersicht (kein Theme-Override noetig).
        ['class' => View::class, 'event' => View::EVENT_AFTER_RENDER, 'callback' => ['nexus\modules\karte\Events', 'onViewAfterRender']],
        // "Standort" im Zahnrad-Menue eines Kreises (ueber ein Ereignis -- kein Eingriff in HumHub selbst).
        ['class' => HeaderControlsMenu::class, 'event' => HeaderControlsMenu::EVENT_INIT, 'callback' => ['nexus\modules\karte\Events', 'onKreisMenue']],
    ],
];
