<?php

/**
 * Theme-Ueberschreibung von
 * humhub/widgets/views/topNavigation.php (Josh, 13.09.2026).
 *
 * Blendet die Navigationszeile (Kreise, Kalender, Homepage, Cloud,
 * Cockpit) NUR auf der eigenen Gast-Willkommensseite aus
 * (Dashboard-Startseite, nicht angemeldet) -- siehe
 * themes/NEXUS/views/dashboard/index_guest.php fuer den Hintergrund.
 * Auf allen anderen Seiten (auch fuer Gaeste, z.B. im oeffentlichen
 * "Willkommen"-Sektor) bleibt die Navigation unveraendert wie im
 * HumHub-Original.
 */

use humhub\assets\TopNavigationAsset;
use humhub\helpers\Html;

/* @var $this \humhub\components\View */
/* @var $menu \humhub\widgets\TopMenu */
/* @var $entries \humhub\modules\ui\menu\MenuEntry[] */

$istGastWillkommensseite = Yii::$app->user->isGuest
    && Yii::$app->controller !== null
    && Yii::$app->controller->id === 'dashboard'
    && Yii::$app->controller->action !== null
    && Yii::$app->controller->action->id === 'index';

if ($istGastWillkommensseite) {
    return;
}

TopNavigationAsset::register($this);

?>

<?php foreach ($entries as $entry) : ?>
    <li class="nav-item top-menu-item">
        <?php
        $options = $entry->getHtmlOptions();
        $class = $options['class'] ?? '';
        $class = is_array($class) ? implode(' ', $class) : $class;
        $options['class'] = trim('nav-link ' . ($entry->getIsActive() ? 'active ' : '') . $class);
        ?>
        <?= Html::a(
            $entry->getIcon() . '<br />' . $entry->getLabel(),
            $entry->getUrl(),
            $options,
        ) ?>
    </li>
<?php endforeach; ?>

<li id="top-menu-sub" class="nav-item dropdown" style="display:none;">
    <a href="#" id="top-dropdown-menu" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
        <i class="fa fa-align-justify"></i><br>
        <?= Yii::t('base', 'Menu'); ?>
    </a>
    <ul id="top-menu-sub-dropdown" class="dropdown-menu dropdown-menu-end">

    </ul>
</li>
