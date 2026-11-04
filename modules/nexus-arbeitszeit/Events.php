<?php

namespace nexus\modules\arbeitszeit;

use humhub\modules\mail\widgets\ConversationEntry;
use humhub\modules\mail\widgets\Messages;
use humhub\modules\ui\menu\MenuLink;
use nexus\modules\arbeitszeit\assets\BenachrichtigungAsset;
use nexus\modules\arbeitszeit\models\Eintrag;
use nexus\modules\arbeitszeit\services\BenachrichtigungAnzeige;
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

    /** Knoepfe unter einer Nachricht des Assistenten in einer Benachrichtigungs-Unterhaltung */
    public static function onNachrichtGezeigt($event): void
    {
        try {
            if ($event->sender instanceof ConversationEntry && $event->sender->entry !== null) {
                $event->result .= BenachrichtigungAnzeige::knoepfe($event->sender->entry);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-arbeitszeit (Knoepfe): ' . $e->getMessage(), 'nexus-arbeitszeit');
        }
    }

    /** Hinweis statt Antwortfeld am Ende einer Benachrichtigungs-Unterhaltung */
    public static function onUnterhaltungGezeigt($event): void
    {
        try {
            // Nur beim ersten Aufbau der Unterhaltung -- beim Nachladen (aeltere/neue Nachrichten) gibt es "entries"
            if ($event->sender instanceof Messages && $event->sender->message !== null && $event->sender->entries === null) {
                $event->result .= BenachrichtigungAnzeige::hinweis($event->sender->message);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-arbeitszeit (Hinweis): ' . $e->getMessage(), 'nexus-arbeitszeit');
        }
    }

    public static function onLayoutAddonInit($event): void
    {
        try {
            if (!Yii::$app->user->isGuest) {
                BenachrichtigungAsset::register(Yii::$app->view);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-arbeitszeit (Skript): ' . $e->getMessage(), 'nexus-arbeitszeit');
        }
    }
}
