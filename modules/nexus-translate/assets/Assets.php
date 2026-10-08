<?php

namespace humhub\modules\nexusTranslate\assets;

use humhub\components\assets\AssetBundle;
use yii\web\View;

class Assets extends AssetBundle
{
    public $sourcePath = '@nexus-translate/resources';
    public $js = ['js/nexus.translate.js'];
    public $jsOptions = ['position' => View::POS_END];
    // Bei jeder Aenderung wirklich neu ausliefern, nicht die alte Fassung
    // aus dem Veroeffentlichungs-Zwischenspeicher behalten.
    public $publishOptions = ['forceCopy' => true];
}
