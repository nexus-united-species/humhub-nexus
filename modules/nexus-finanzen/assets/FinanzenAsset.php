<?php

namespace nexus\modules\finanzen\assets;

use humhub\components\assets\AssetBundle;

class FinanzenAsset extends AssetBundle
{
    public $sourcePath = '@nexus-finanzen/resources';
    public $css = ['css/finanzen.css'];
    // Bei jeder Aenderung wirklich neu ausliefern (gleiche Lehre wie bei den anderen Modulen).
    public $publishOptions = ['forceCopy' => true];
}
