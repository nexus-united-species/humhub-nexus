<?php

namespace humhub\modules\nexusNotifBanner;

use humhub\modules\nexusNotifBanner\assets\BannerAsset;
use Yii;

/**
 * Zeigt einen eigenen, freundlichen Hinweis, BEVOR die rohe
 * Browser-Systemabfrage fuer Benachrichtigungen erscheint.
 *
 * Grund: HumHubs fcm-push-Modul loest Notification.requestPermission()
 * bisher automatisch und unangekuendigt aus, sobald sich jemand anmeldet.
 * Ohne Erklaerung klicken die meisten Menschen reflexartig auf
 * "Blockieren" -- danach fragt der Browser aus Prinzip nie wieder von
 * selbst.
 *
 * Wie es funktioniert (Stand 09.09.2026, siehe resources/js/hinweis.js):
 * das Skript ersetzt die Browser-Funktion Notification.requestPermission()
 * selbst durch eine eigene Fassung, die zuerst den Hinweis zeigt und die
 * echte Browser-Anfrage erst nach einem Klick darauf auslöst. Ein erster
 * Ansatz ueber HumHubs afterServiceWorkerRegistration()-Callback wurde
 * verworfen, weil er von der Ladereihenfolge der Skripte abhing und nur
 * unzuverlaessig griff.
 *
 * WICHTIGE EINSCHRAENKUNG (iOS/Safari): Weil unser Ersatz die eigentliche
 * Browser-Anfrage aus einem SPAETEREN Klick heraus stellt (auf unseren
 * eigenen Knopf, nicht auf den urspruenglichen Ausloeser), kann das mit
 * fcm-push's eigenem, extra fuer iOS gebauten Knopf-Handler kollidieren
 * (humhub.firebase.js, enableNotificationsButtonHandler -- dort steht
 * ausdruecklich, dass iOS/WebKit Notification.requestPermission() nur
 * synchron aus einem echten Fingertipp heraus akzeptiert). Meldung dazu:
 * Rueckmeldung eines Mitglieds, iPhone, 09.09.2026 -- Hinweisfenster erschien, liess sich aber
 * nicht bedienen. Behoben (Platzierung/Touch-CSS), die grundsaetzliche
 * Kollision mit dem iOS-Sonderfall in fcm-push bleibt aber ein Punkt, den
 * man bei kuenftigen Problemen mit iOS zuerst pruefen sollte.
 *
 * Bewusst als eigenes, kleines Modul statt einer Aenderung an HumHub-Kern
 * oder am fcm-push-Modul selbst -- beides wuerde beim naechsten
 * HumHub-Update wieder verschwinden.
 */
class Events
{
    public static function onLayoutAddonInit($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $module = Yii::$app->getModule('fcm-push');
        if (!$module || !$module->getDriverService()->hasConfiguredWebDriver()) {
            return;
        }

        BannerAsset::register(Yii::$app->view);
    }
}
