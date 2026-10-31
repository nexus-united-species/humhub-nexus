<?php

namespace nexus\modules\kalender;

use humhub\modules\ui\menu\MenuLink;
use nexus\modules\kalender\services\Texte;
use Throwable;
use Yii;
use yii\helpers\Url;

class Events
{
    public static function onKreisMenue($event): void
    {
        try {
            $modul = Module::instanz();
            $kreis = $event->sender->space ?? null;
            if ($modul === null || $kreis === null || Yii::$app->user->isGuest || !in_array((int)$kreis->id, $modul->kreise, true)) {
                return;
            }
            $event->sender->addEntry(new MenuLink([
                'label' => Texte::titel(),
                'url' => Url::to(['/nexus-kalender/kalender/index']),
                'icon' => 'calendar',
                'sortOrder' => 120,
            ]));
        } catch (Throwable $e) {
            Yii::error('nexus-kalender: Menuepunkt: ' . $e->getMessage(), 'nexus-kalender');
        }
    }
}
