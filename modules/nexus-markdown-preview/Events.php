<?php

namespace humhub\modules\nexusMarkdownPreview;

use humhub\modules\nexusMarkdownPreview\assets\PreviewAsset;
use Yii;

/**
 * Haengt bei jedem Seitenaufruf eines angemeldeten Mitglieds ein kleines
 * Skript an, das Dateilisten (Kreis-"Dateien"-Reiter, Beitrags-Anhaenge,
 * private Nachrichten -- ueberall, wo HumHubs FilePreview-Widget Dateien
 * anzeigt) nach .md-Dateien absucht und daneben einen Vorschau-Knopf
 * einfuegt.
 *
 * Bewusst per MutationObserver im Skript selbst statt an ein bestimmtes
 * HumHub-Nachlade-Ereignis gebunden: diese HumHub-Version laedt
 * Reiter-Inhalte nicht mehr ueber jquery-pjax (kein einziges
 * 'pjax:*'-Ereignis im Kern-Code auffindbar) -- welches Ereignis genau
 * beim Reiterwechsel feuert, liess sich nicht zuverlaessig bestimmen. Ein
 * MutationObserver auf <body> ist unabhaengig davon, WIE der Inhalt
 * nachgeladen wird, und bleibt auch nach einem HumHub-Update richtig.
 */
class Events
{
    public static function onLayoutAddonInit($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        PreviewAsset::register(Yii::$app->view);
    }
}
