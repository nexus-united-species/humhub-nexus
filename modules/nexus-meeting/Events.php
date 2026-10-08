<?php

namespace nexus\modules\meeting;

use nexus\modules\meeting\assets\MeetingAsset;
use Throwable;
use Yii;

class Events
{
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
}
