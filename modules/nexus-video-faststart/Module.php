<?php

namespace humhub\modules\nexusVideoFaststart;

class Module extends \humhub\components\Module
{
    /*
     * Pfade zu ffmpeg und ffprobe (statisch gebaut, z. B. von johnvansickle.com). Einstellung in
     * protected/config/common.php: 'modules' => ['nexus-video-faststart' => ['ffmpeg' => '...']].
     * Fehlt eines der Programme, tut das Modul nichts.
     */

    /** @var string */
    public $ffmpeg = '/data/bin/ffmpeg';

    /** @var string */
    public $ffprobe = '/data/bin/ffprobe';

    public function getName()
    {
        return 'N.E.X.U.S. Video Faststart';
    }

    public function getDescription()
    {
        return 'Verschiebt bei hochgeladenen Videos den moov-Atom an den Dateianfang, damit die Wiedergabe im Browser sofort startet.';
    }
}
