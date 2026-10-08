<?php

namespace nexus\modules\teilen\assets;

use humhub\components\assets\AssetBundle;
use yii\web\View;

class TeilenAsset extends AssetBundle
{
    public $sourcePath = '@nexus-teilen/resources';
    public $js = ['js/nexus.teilen.js'];
    public $css = ['css/nexus.teilen.css'];
    public $jsOptions = ['position' => View::POS_END];
    public $publishOptions = ['forceCopy' => true];
}
