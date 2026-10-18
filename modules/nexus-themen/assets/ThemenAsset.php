<?php

namespace nexus\modules\themen\assets;

use humhub\components\assets\AssetBundle;
use yii\web\View;

class ThemenAsset extends AssetBundle
{
    public $sourcePath = '@nexus-themen/resources';
    public $js = ['js/nexus.themen.js'];
    public $css = ['css/nexus.themen.css'];
    public $jsOptions = ['position' => View::POS_END];
}
