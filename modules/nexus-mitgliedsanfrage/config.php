<?php

use humhub\modules\user\controllers\AuthController;
use humhub\modules\user\controllers\RegistrationController;

return [
    'id' => 'nexus-mitgliedsanfrage',
    'class' => 'nexus\modules\mitgliedsanfrage\Module',
    'namespace' => 'nexus\modules\mitgliedsanfrage',
    'events' => [
        // Nach jeder erfolgreichen Anmeldung ueber Authentik -- dort liegen die Antworten vor.
        ['class' => AuthController::class, 'event' => AuthController::EVENT_AFTER_LOGIN, 'callback' => ['nexus\modules\mitgliedsanfrage\Events', 'onLogin']],
        // Einladungslink eines Kreises: zuerst die E-Mail-Adresse (geraeteunabhaengig), siehe EinladungController
        ['class' => RegistrationController::class, 'event' => RegistrationController::EVENT_BEFORE_ACTION, 'callback' => ['nexus\modules\mitgliedsanfrage\Events', 'onEinladungslink']],
    ],
];
