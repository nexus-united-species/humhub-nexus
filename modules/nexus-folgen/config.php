<?php

use humhub\modules\user\models\User;

return [
    'id' => 'nexus-folgen',
    'class' => 'nexus\modules\folgen\Module',
    'namespace' => 'nexus\modules\folgen',
    'events' => [
        // Neues Konto: den eingestellten Kreisen folgen
        ['class' => User::class, 'event' => User::EVENT_AFTER_INSERT, 'callback' => ['nexus\modules\folgen\Module', 'onNeuesKonto']],
    ],
    // php yii folgen/alle [--probe] -- bestehende Konten nachziehen
    'consoleControllerMap' => [
        'folgen' => 'nexus\modules\folgen\commands\FolgenController',
    ],
];
