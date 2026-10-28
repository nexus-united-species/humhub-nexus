<?php

namespace nexus\modules\reaktionen\assets;

use humhub\components\assets\AssetBundle;
use yii\web\View;

class ReaktionenAsset extends AssetBundle
{
    public $sourcePath = '@nexus-reaktionen/resources';
    public $js = ['reaktionen.js'];
    public $css = ['reaktionen.css'];
    public $jsOptions = ['position' => View::POS_END];
}
