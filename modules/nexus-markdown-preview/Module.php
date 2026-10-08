<?php

namespace humhub\modules\nexusMarkdownPreview;

class Module extends \humhub\components\Module
{
    public function getName()
    {
        return 'N.E.X.U.S. Markdown-Vorschau';
    }

    public function getDescription()
    {
        return 'Zeigt .md-Dateien in Kreis-Verzeichnissen formatiert an statt nur als Rohtext-Download.';
    }
}
