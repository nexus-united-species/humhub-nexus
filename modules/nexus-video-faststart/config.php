<?php

use humhub\modules\file\models\File;
use humhub\modules\nexusVideoFaststart\Events;

return [
    'id' => 'nexus-video-faststart',
    'class' => 'humhub\modules\nexusVideoFaststart\Module',
    'namespace' => 'humhub\modules\nexusVideoFaststart',
    'events' => [
        [File::class, File::EVENT_AFTER_NEW_STORED_FILE, [Events::class, 'onAfterNewStoredFile']],
    ],
];
