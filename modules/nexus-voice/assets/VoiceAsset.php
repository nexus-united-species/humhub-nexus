<?php

namespace humhub\modules\nexusVoice\assets;

use humhub\components\assets\AssetBundle;
use yii\web\View;

class VoiceAsset extends AssetBundle
{
    public $sourcePath = '@nexus-voice/resources';
    public $js = ['js/nexus.voice.js'];
    public $css = ['css/nexus.voice.css'];
    public $jsOptions = ['position' => View::POS_END];
    // Bei jeder Aenderung wirklich neu ausliefern (gleiche Lehre wie beim Uebersetzen-Modul).
    public $publishOptions = ['forceCopy' => true];
}
