<?php

namespace nexus\modules\team\assets;

use humhub\components\assets\AssetBundle;

class TeamAsset extends AssetBundle
{
    public $sourcePath = '@nexus-team/resources';
    public $css = ['css/team.css'];
    public $js = ['js/team.js'];
    // Bei jeder Aenderung wirklich neu ausliefern (gleiche Lehre wie bei den anderen Modulen).
    public $publishOptions = ['forceCopy' => true];
}
