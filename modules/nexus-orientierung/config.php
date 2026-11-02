<?php

use humhub\modules\user\widgets\ProfileMenu;
use humhub\widgets\LayoutAddons;

return [
    'id' => 'nexus-orientierung',
    'class' => 'nexus\modules\orientierung\Module',
    'namespace' => 'nexus\modules\orientierung',
    'events' => [
        // Anmeldung ueber Authentik soll bestehen bleiben (HumHub 1.19 kennt dafuer keinen Schalter mehr)
        ['class' => \yii\web\User::class, 'event' => \yii\web\User::EVENT_AFTER_LOGIN, 'callback' => ['nexus\modules\orientierung\Events', 'onAngemeldet']],
        // Skript fuer "zurueck an dieselbe Stelle" auf jeder Seite (nur Angemeldete)
        ['class' => LayoutAddons::class, 'event' => LayoutAddons::EVENT_INIT, 'callback' => ['nexus\modules\orientierung\Events', 'onLayoutAddonInit']],
        // "Beitraege & Kommentare" im Profilmenue jedes Menschen
        ['class' => ProfileMenu::class, 'event' => ProfileMenu::EVENT_INIT, 'callback' => ['nexus\modules\orientierung\Events', 'onProfilMenue']],
    ],
];
