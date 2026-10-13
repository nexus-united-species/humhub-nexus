<?php

use humhub\commands\CronController;
use humhub\components\Controller;
use humhub\modules\user\widgets\AccountTopMenu;
use humhub\modules\user\widgets\Image;

return [
    'id' => 'nexus-abzeichen',
    'class' => 'nexus\modules\abzeichen\Module',
    'namespace' => 'nexus\modules\abzeichen',
    'events' => [
        // Jede Seite: aktive Zeit zaehlen, Stylesheet laden, faellige Feier zeigen
        ['class' => Controller::class, 'event' => Controller::EVENT_BEFORE_ACTION, 'callback' => ['nexus\modules\abzeichen\Events', 'onSeite']],
        // Rahmen und Siegel um jedes Profilbild eines Menschen, der sich zu einem Abzeichen bekennt
        ['class' => Image::class, 'event' => Image::EVENT_AFTER_RUN, 'callback' => ['nexus\modules\abzeichen\Events', 'onProfilbild']],
        // "Abzeichen" im Profilmenue
        ['class' => AccountTopMenu::class, 'event' => AccountTopMenu::EVENT_INIT, 'callback' => ['nexus\modules\abzeichen\Events', 'onKontoMenue']],
        // Freigegebene Arbeitsstunden: neue Stufe pruefen, sobald sich ein Eintrag aendert
        // (Klasse als Text -- fehlt das Modul nexus-arbeitszeit, passiert nichts)
        ['class' => 'nexus\modules\arbeitszeit\models\Eintrag', 'event' => 'afterInsert', 'callback' => ['nexus\modules\abzeichen\services\Arbeitsstunden', 'nachAenderung']],
        ['class' => 'nexus\modules\arbeitszeit\models\Eintrag', 'event' => 'afterUpdate', 'callback' => ['nexus\modules\abzeichen\services\Arbeitsstunden', 'nachAenderung']],
        // Alte Wochenzaehler aufraeumen
        ['class' => CronController::class, 'event' => CronController::EVENT_ON_DAILY_RUN, 'callback' => ['nexus\modules\abzeichen\Events', 'onTaeglich']],
    ],
];
