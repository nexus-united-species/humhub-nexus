<?php

namespace nexus\modules\team;

use humhub\modules\ui\menu\MenuLink;
use nexus\modules\team\services\Texte;
use Throwable;
use Yii;
use yii\helpers\Url;

class Events
{
    public static function onTopMenuInit($event): void
    {
        try {
            $modul = Module::instanz();
            if (Yii::$app->user->isGuest || $modul === null || !$modul->istTeam(Yii::$app->user->getIdentity())) {
                return;
            }
            $event->sender->addEntry(new MenuLink([
                'label' => Texte::t('nav'),
                'url' => Url::to(['/nexus-team/ueberblick/index']),
                'icon' => 'handshake-o',
                'sortOrder' => 650,
                'isActive' => Yii::$app->controller !== null && Yii::$app->controller->module !== null
                    && Yii::$app->controller->module->id === 'nexus-team',
            ]));
        } catch (Throwable $e) {
            // Ein Fehler hier darf nie die ganze Navigation (und damit jede Seite) kaputt machen.
            Yii::error('nexus-team: Menue-Eintrag fehlgeschlagen: ' . $e->getMessage(), 'nexus-team');
        }
    }
}
