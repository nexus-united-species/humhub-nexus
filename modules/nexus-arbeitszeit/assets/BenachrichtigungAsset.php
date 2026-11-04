<?php

namespace nexus\modules\arbeitszeit\assets;

use humhub\components\assets\AssetBundle;
use yii\web\View;

class BenachrichtigungAsset extends AssetBundle
{
    public $sourcePath = '@nexus-arbeitszeit/resources';
    public $js = ['benachrichtigung.js'];
    public $css = ['benachrichtigung.css'];
    public $jsOptions = ['position' => View::POS_END];
}
