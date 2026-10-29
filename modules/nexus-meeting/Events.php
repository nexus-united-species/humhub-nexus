<?php

namespace nexus\modules\meeting;

use humhub\modules\ui\menu\MenuLink;
use humhub\modules\user\models\User;
use nexus\modules\meeting\assets\MeetingAsset;
use Throwable;
use Yii;

class Events
{
    private const MENUE_OBEN = 450;
    private const BESCHRIFTUNG = ['de' => 'Offene Tür', 'en' => 'Open door', 'es' => 'Puerta abierta', 'ru' => 'Открытая дверь'];

    /** Skript nur fuer Angemeldete -- Gaeste sehen keine Kreise und keine Termine. */
    public static function onLayoutAddonInit($event): void
    {
        try {
            if (Yii::$app->user->isGuest) {
                return;
            }
            MeetingAsset::register(Yii::$app->view);
        } catch (Throwable $e) {
            Yii::error('nexus-meeting: ' . $e->getMessage(), 'nexus-meeting');
        }
    }

    /**
     * "Offene Tür" oben in der Leiste -- nur fuer die Menschen aus den Einstellungen. Kein Seitenwechsel:
     * das Skript faengt den Klick ab (data-nexus-tuer) und fragt nach dem Warteraum.
     */
    public static function onObenMenue($event): void
    {
        try {
            $ich = Yii::$app->user->getIdentity();
            $modul = Yii::$app->getModule('nexus-meeting');
            if (!$ich instanceof User || !$modul instanceof Module || !$modul->darfTuerOeffnen($ich)) {
                return;
            }
            $sprache = strtolower(explode('-', (string)Yii::$app->language)[0]);
            $event->sender->addEntry(new MenuLink([
                'label' => self::BESCHRIFTUNG[$sprache] ?? self::BESCHRIFTUNG['en'],
                'url' => '#',
                'icon' => 'coffee',
                'sortOrder' => self::MENUE_OBEN,
                'htmlOptions' => ['data-nexus-tuer' => '1', 'data-pjax-prevent' => '1'],
            ]));
        } catch (Throwable $e) {
            Yii::error('nexus-meeting: obere Leiste: ' . $e->getMessage(), 'nexus-meeting');
        }
    }
}
