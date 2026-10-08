<?php

namespace nexus\modules\supporterBridge;

use yii\helpers\Url;

class Module extends \humhub\components\Module
{
    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to(['/nexus-supporter-bridge/admin/index']);
    }
}
