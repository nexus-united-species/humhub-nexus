<?php

namespace nexus\modules\supporterBridge;

use humhub\components\Event;
use humhub\helpers\ControllerHelper;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\ui\menu\MenuLink;
use humhub\modules\ui\menu\widgets\Menu;
use Yii;

class Events
{
    /**
     * Haengt einen Eintrag in die bestehende Administration-Seitenleiste,
     * damit man nicht die rohe Adresse kennen muss. Nur fuer System-
     * Administratoren sichtbar.
     */
    public static function onAdminMenuInit($event)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isSystemAdmin()) {
            return;
        }

        /** @var Menu $menu */
        $menu = $event->sender;

        $menu->addEntry(new MenuLink([
            'id' => 'nexus-supporter-bridge',
            'label' => 'Unterstuetzer-Verwaltung',
            'url' => ['/nexus-supporter-bridge/admin/index'],
            'icon' => 'heart',
            'sortOrder' => 950,
            'isActive' => ControllerHelper::isActivePath('nexus-supporter-bridge'),
        ]));
    }
}
