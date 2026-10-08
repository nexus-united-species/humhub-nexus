<?php

namespace humhub\modules\nexusVoice;

class Module extends \humhub\components\Module
{
    /**
     * @var string Adresse des Mitschrift-Dienstes (siehe extras/nexus-whisper). Einstellung in
     * protected/config/common.php: 'modules' => ['nexus-voice' => ['mitschriftUrl' => '...']].
     * Der Schluessel dazu steht als Moduleinstellung "whisperToken" in der Datenbank.
     */
    public $mitschriftUrl = 'http://nexus-whisper:8000/mitschrift';

    public function getName()
    {
        return 'N.E.X.U.S. Sprachnachrichten';
    }

    public function getDescription()
    {
        return 'Mikrofon-Knopf in Beitraegen, Kommentaren und privaten Nachrichten (bis 5 Minuten).';
    }
}
