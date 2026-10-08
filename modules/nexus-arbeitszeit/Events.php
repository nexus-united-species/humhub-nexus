<?php

namespace nexus\modules\arbeitszeit;

use humhub\modules\ui\menu\MenuLink;
use nexus\modules\arbeitszeit\models\Eintrag;
use nexus\modules\arbeitszeit\services\NextcloudBericht;
use nexus\modules\arbeitszeit\services\Texte;
use Throwable;
use Yii;
use yii\helpers\Url;

class Events
{
    /**
     * Profil-Menue oben rechts: "Meine Arbeitsstunden" fuer alle (direkt unter "Einstellungen", 200),
     * "Stunden freigeben (N)" fuer Admins (unter "Administration", 400) -- die Zahl zeigt, ob etwas wartet.
     */
    public static function onAccountMenu($event): void
    {
        try {
            if (Yii::$app->user->isGuest) {
                return;
            }
            $menu = $event->sender;
            $menu->addEntry(new MenuLink([
                'label' => Texte::t('titel'),
                'icon' => 'clock-o',
                'url' => Url::to(['/nexus-arbeitszeit/meine/index']),
                'sortOrder' => 250,
            ]));
            if (Yii::$app->user->getIdentity()->isSystemAdmin()) {
                $offen = (int)Eintrag::find()->where(['status' => Eintrag::STATUS_OFFEN])->count();
                $menu->addEntry(new MenuLink([
                    'label' => Texte::t('freigabe_link') . ($offen > 0 ? " ($offen)" : ''),
                    'icon' => 'check-square-o',
                    'url' => Url::to(['/nexus-arbeitszeit/freigabe/index']),
                    'sortOrder' => 410,
                ]));
            }
        } catch (Throwable $e) {
            // Ein Fehler hier darf nie das ganze Profil-Menue (und damit die Seite) kaputt machen.
            Yii::error('nexus-arbeitszeit: Menue-Eintrag fehlgeschlagen: ' . $e->getMessage(), 'nexus-arbeitszeit');
        }
    }

    /** Stuendlich ueber HumHubs Zeitplaner: Excel-Bericht nachziehen, falls sich etwas geaendert hat. */
    public static function onHourly($event): void
    {
        NextcloudBericht::stuendlich();
    }
}
