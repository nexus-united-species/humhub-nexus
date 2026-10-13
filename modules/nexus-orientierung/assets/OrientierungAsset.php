<?php

namespace nexus\modules\orientierung\assets;

use humhub\components\assets\AssetBundle;
use yii\web\View;

class OrientierungAsset extends AssetBundle
{
    public $sourcePath = '@nexus-orientierung/resources';
    public $js = ['orientierung.js'];
    public $css = ['orientierung.css'];
    public $jsOptions = ['position' => View::POS_END];
}
