<?php

namespace nexus\modules\orientierung;

use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\models\User;
use nexus\modules\orientierung\assets\OrientierungAsset;
use nexus\modules\orientierung\services\Texte;
use Throwable;
use Yii;

class Events
{
    /**
     * HumHub 1.19 wertet rememberMe bei Anmeldungen ueber Authentik nicht mehr aus: Sie gelten nur fuer die Browsersitzung
     * und laufen nach gut 20 Minuten Pause ab. Deshalb hier nachgezogen, nur fuer den Ablauf der echten Anmeldung.
     */
    public static function onAngemeldet($event): void
    {
        try {
            $controller = Yii::$app->controller;
            if ($event->duration > 0 || $event->cookieBased || !$controller instanceof \humhub\modules\user\controllers\AuthController) {
                return;
            }
            $event->sender->switchIdentity($event->identity, (int)Yii::$app->getModule('user')->loginRememberMeDuration);
        } catch (Throwable $e) {
            Yii::error('nexus-orientierung: ' . $e->getMessage(), 'nexus-orientierung');
        }
    }

    /** Zwischen "Stream" (200) und "Ueber mich" (300) */
    private const MENUE_POSITION = 250;

    public static function onLayoutAddonInit($event): void
    {
        try {
            if (!Yii::$app->user->isGuest) {
                OrientierungAsset::register(Yii::$app->view);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-orientierung (Skript): ' . $e->getMessage(), 'nexus-orientierung');
        }
    }

    public static function onProfilMenue($event): void
    {
        try {
            $person = $event->sender->user ?? null;
            if (Yii::$app->user->isGuest || !$person instanceof User) {
                return;
            }
            $event->sender->addEntry(new MenuLink([
                'label' => Texte::t('menue'),
                'icon' => 'comments-o',
                'url' => $person->createUrl('/nexus-orientierung/beitraege/index'),
                'sortOrder' => self::MENUE_POSITION,
                'isActive' => Yii::$app->controller !== null && Yii::$app->controller->module !== null
                    && Yii::$app->controller->module->id === 'nexus-orientierung',
            ]));
        } catch (Throwable $e) {
            Yii::error('nexus-orientierung (Menue): ' . $e->getMessage(), 'nexus-orientierung');
        }
    }
}
