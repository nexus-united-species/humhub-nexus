<?php

use humhub\modules\content\widgets\ContentObjectLinks;
use humhub\modules\content\widgets\WallEntryControls;
use humhub\modules\content\widgets\WallEntryLinks;
use humhub\components\View;
use humhub\widgets\LayoutAddons;

return [
    'id' => 'nexus-teilen',
    'class' => 'nexus\modules\teilen\Module',
    'namespace' => 'nexus\modules\teilen',
    'events' => [
        // Sichtbare Link-Zeile unter Beitraegen im Stream ...
        ['class' => WallEntryLinks::class, 'event' => WallEntryLinks::EVENT_INIT, 'callback' => ['nexus\modules\teilen\Events', 'onLinksInit']],
        // ... und unter einer geoeffneten Wiki-Seite (die nutzt die allgemeinere Klasse).
        ['class' => ContentObjectLinks::class, 'event' => ContentObjectLinks::EVENT_INIT, 'callback' => ['nexus\modules\teilen\Events', 'onLinksInit']],
        // "..."-Menue eines Beitrags und Menue einer Wiki-Seite (WikiMenu erbt von WallEntryControls).
        ['class' => WallEntryControls::class, 'event' => WallEntryControls::EVENT_INIT, 'callback' => ['nexus\modules\teilen\Events', 'onControlsInit']],
        ['class' => LayoutAddons::class, 'event' => LayoutAddons::EVENT_INIT, 'callback' => ['nexus\modules\teilen\Events', 'onLayoutAddonInit']],
        // Gast-Startseite: Platzhalter durch die neuesten Schaufenster-Beitraege ersetzen (wie die Karte).
        ['class' => View::class, 'event' => View::EVENT_AFTER_RENDER, 'callback' => ['nexus\modules\teilen\Events', 'onViewAfterRender']],
    ],
];
