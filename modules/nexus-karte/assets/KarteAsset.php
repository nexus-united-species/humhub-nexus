<?php

namespace nexus\modules\karte\assets;

use humhub\components\assets\AssetBundle;
use yii\web\View;

/** Leaflet 1.9.4 liegt im Modul (resources/leaflet) -- nichts wird von fremden Servern geladen. */
class KarteAsset extends AssetBundle
{
    public $sourcePath = '@nexus-karte/resources';
    public $js = ['leaflet/leaflet.js', 'js/nexus.karte.js'];
    public $css = ['leaflet/leaflet.css', 'css/nexus.karte.css'];
    public $jsOptions = ['position' => View::POS_END];
    // Bei jeder Aenderung wirklich neu ausliefern (gleiche Lehre wie bei den anderen Modulen).
    public $publishOptions = ['forceCopy' => true];
}
