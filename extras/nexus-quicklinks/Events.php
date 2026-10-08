<?php

namespace humhub\modules\nexusQuicklinks;

use humhub\modules\ui\menu\MenuLink;
use humhub\widgets\TopMenu;
use Yii;

/**
 * Verlinkt die drei weiteren N.E.X.U.S.-Dienste (Startseite, Cloud, Cockpit)
 * oben in der Navigationsleiste -- auf Josh' Wunsch vom 04.09.2026, damit
 * man nicht erst die Adresse von Hand eintippen muss. Oeffnet bewusst in
 * einem neuen Tab (target=_blank), damit die HumHub-Sitzung/Position im
 * Stream nicht verloren geht.
 */
class Events
{
    public static function onTopMenuInit($event): void
    {
        /** @var TopMenu $topNav */
        $topNav = $event->sender;

        $topNav->addEntry(new MenuLink([
            'label' => 'Homepage',
            'url' => 'https://www.nexus-terminal.org',
            'icon' => 'home',
            'sortOrder' => 500,
            'htmlOptions' => ['target' => '_blank', 'rel' => 'noopener'],
        ]));

        $topNav->addEntry(new MenuLink([
            'label' => 'Cloud',
            'url' => 'https://cloud.nexus-terminal.org',
            'icon' => 'cloud',
            'sortOrder' => 510,
            'htmlOptions' => ['target' => '_blank', 'rel' => 'noopener'],
        ]));

        $topNav->addEntry(new MenuLink([
            'label' => 'Cockpit',
            'url' => 'https://cockpit.nexus-terminal.org',
            'icon' => 'dashboard',
            'sortOrder' => 520,
            'htmlOptions' => ['target' => '_blank', 'rel' => 'noopener'],
        ]));
    }
}
