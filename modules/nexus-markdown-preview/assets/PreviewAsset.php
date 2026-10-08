<?php

namespace humhub\modules\nexusMarkdownPreview\assets;

use humhub\components\assets\AssetBundle;

class PreviewAsset extends AssetBundle
{
    public $sourcePath = '@nexus-markdown-preview/resources/js';
    public $js = ['vorschau.js'];

    // Wie bei nexus-notif-banner: bei jeder Aenderung wirklich neu
    // ausliefern statt eine alte Fassung aus dem Veroeffentlichungs-
    // Zwischenspeicher zu behalten.
    public $publishOptions = ['forceCopy' => true];
}
