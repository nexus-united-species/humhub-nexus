<?php

namespace nexus\modules\protectedLibrary;

use humhub\modules\ui\menu\MenuLink;
use Yii;
use yii\helpers\Url;

class Events
{
    public static function onAdminMenuInit($event)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isSystemAdmin()) {
            return;
        }

        $event->sender->addItem([
            'label' => 'Unterstuetzer-Bibliothek',
            'icon' => 'book',
            'url' => ['/nexus-protected-library/admin/index'],
            'sortOrder' => 970,
        ]);
    }

    /**
     * Eintrag "Bibliothek" in der linken Seitenleiste -- ausschliesslich im
     * Unterstuetzer-Space selbst (SUPPORTER_SPACE_ID), damit der Link nicht
     * in jedem Space der Community auftaucht. Ohne diesen Eintrag findet
     * niemand den Lesebereich, da bewusst keine Dateien/Beitraege im Space
     * selbst liegen (Vorgabe: nicht herunterladbar).
     */
    public static function onSpaceMenuInit($event)
    {
        try {
            $spaceMenu = $event->sender;
            $space = $spaceMenu->space ?? null;
            $zielSpaceId = (int)getenv('SUPPORTER_SPACE_ID');

            if ($space === null || $zielSpaceId <= 0 || (int)$space->id !== $zielSpaceId) {
                return;
            }

            $spaceMenu->addEntry(new MenuLink([
                'label' => 'Bibliothek',
                'url' => Url::to(['/nexus-protected-library/reader/index']),
                'icon' => 'book',
                'sortOrder' => 200,
            ]));
        } catch (\Throwable $e) {
            Yii::error('nexus-protected-library: Space-Menu-Eintrag fehlgeschlagen: ' . $e->getMessage());
        }
    }
}
