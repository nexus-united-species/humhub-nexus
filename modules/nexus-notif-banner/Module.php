<?php

namespace humhub\modules\nexusNotifBanner;

class Module extends \humhub\components\Module
{
    public function getName()
    {
        return 'N.E.X.U.S. Hinweis: Benachrichtigungen';
    }

    public function getDescription()
    {
        return 'Erklaerender Hinweis vor der Browser-Abfrage fuer Push-Benachrichtigungen.';
    }
}
