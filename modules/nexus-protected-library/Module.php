<?php

namespace nexus\modules\protectedLibrary;

use humhub\components\Module as BaseModule;
use yii\helpers\Url;

class Module extends BaseModule
{
    public function getName()
    {
        return 'N.E.X.U.S. Unterstuetzer-Bibliothek';
    }

    public function getDescription()
    {
        return 'Geschuetzter Lesebereich fuer Buecher -- ohne herunterladbare Originaldatei.';
    }

    public function getConfigUrl()
    {
        return Url::to(['/nexus-protected-library/admin/index']);
    }
}
