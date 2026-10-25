<?php

namespace nexus\modules\finanzen;

use humhub\modules\ui\menu\MenuLink;
use nexus\modules\finanzen\services\Texte;
use Throwable;
use Yii;
use yii\helpers\Url;

class Events
{
    public static function onTopMenuInit($event): void
    {
        try {
            if (Yii::$app->user->isGuest) {
                return;
            }
            $event->sender->addEntry(new MenuLink([
                'label' => Texte::t('nav'),
                'url' => Url::to(['/nexus-finanzen/ampel/index']),
                'icon' => 'heartbeat',
                'sortOrder' => 600,
                'isActive' => Yii::$app->controller !== null && Yii::$app->controller->module !== null
                    && Yii::$app->controller->module->id === 'nexus-finanzen',
            ]));
        } catch (Throwable $e) {
            // Ein Fehler hier darf nie die ganze Navigation (und damit jede Seite) kaputt machen.
            Yii::error('nexus-finanzen: Menue-Eintrag fehlgeschlagen: ' . $e->getMessage(), 'nexus-finanzen');
        }
    }
}
