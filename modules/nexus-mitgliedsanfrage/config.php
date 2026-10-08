<?php

use humhub\modules\user\controllers\AuthController;

return [
    'id' => 'nexus-mitgliedsanfrage',
    'class' => 'nexus\modules\mitgliedsanfrage\Module',
    'namespace' => 'nexus\modules\mitgliedsanfrage',
    'events' => [
        // Nach jeder erfolgreichen Anmeldung ueber Authentik -- dort liegen die Antworten vor.
        ['class' => AuthController::class, 'event' => AuthController::EVENT_AFTER_LOGIN, 'callback' => ['nexus\modules\mitgliedsanfrage\Events', 'onLogin']],
    ],
];
