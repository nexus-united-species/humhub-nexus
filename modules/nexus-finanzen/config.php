<?php

use humhub\widgets\TopMenu;

return [
    'id' => 'nexus-finanzen',
    'class' => 'nexus\modules\finanzen\Module',
    'namespace' => 'nexus\modules\finanzen',
    'events' => [
        // "Finanzierung" oben in der Navigation -- fuer alle angemeldeten Mitglieder
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => ['nexus\modules\finanzen\Events', 'onTopMenuInit']],
    ],
];
