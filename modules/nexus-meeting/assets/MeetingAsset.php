<?php

namespace nexus\modules\meeting\assets;

use humhub\components\assets\AssetBundle;
use yii\web\View;

class MeetingAsset extends AssetBundle
{
    public $sourcePath = '@nexus-meeting/resources';
    public $js = ['js/nexus.meeting.js'];
    public $css = ['css/nexus.meeting.css'];
    public $jsOptions = ['position' => View::POS_END];
    public $publishOptions = ['forceCopy' => true];
}
