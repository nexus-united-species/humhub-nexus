<?php

namespace humhub\modules\nexusNotifBanner\assets;

use humhub\components\assets\AssetBundle;
use humhub\modules\fcmPush\assets\FcmPushAsset;
use humhub\modules\fcmPush\assets\FirebaseAsset;

class BannerAsset extends AssetBundle
{
    public $defer = false;
    public $sourcePath = '@nexus-notif-banner/resources/js';
    public $js = ['hinweis.js'];

    // Bei jeder Aenderung wirklich neu ausliefern, nicht die alte Fassung
    // aus dem Veroeffentlichungs-Zwischenspeicher behalten (gleiches
    // Problem wie beim Uebersetzen-Modul: ohne das kann eine geaenderte
    // JS-Datei unbemerkt weiter in der alten Fassung ausgeliefert werden).
    public $publishOptions = ['forceCopy' => true];

    // Muss NACH den Firebase-Skripten laden, damit unser Ersatz fuer
    // Notification.requestPermission() erst existiert, nachdem fcm-push
    // die urspruengliche Fassung eingerichtet hat (die zuletzt im HTML
    // stehende Definition gewinnt).
    public $depends = [
        FirebaseAsset::class,
        FcmPushAsset::class,
    ];
}
