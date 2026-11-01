<?php

namespace nexus\modules\folgen\commands;

use humhub\modules\user\models\User;
use nexus\modules\folgen\Module;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Bestehende Konten den eingestellten Kreisen folgen lassen (einmalig nach dem Einrichten):
 *
 *   php yii folgen/alle --probe   nur zaehlen
 *   php yii folgen/alle           ausfuehren
 *   php yii folgen/personen [--probe]   nur die eingestellten Profile, Kreise bleiben unberuehrt
 *
 * Achtung: Wer zwischenzeitlich "Nicht mehr folgen" geklickt hat, folgt danach wieder.
 */
class FolgenController extends Controller
{
    /** @var bool|null nur zaehlen (null statt false: Yii wandelt Schalter in den Typ der Vorgabe um) */
    public $probe = null;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), in_array($actionID, ['alle', 'personen'], true) ? ['probe'] : []);
    }

    public function actionAlle(): int
    {
        $modul = Module::instanz();
        if ($modul === null || $modul->kreise === []) {
            $this->stderr("Modul nexus-folgen ist nicht aktiv oder 'kreise' ist leer.\n");
            return ExitCode::CONFIG;
        }
        $probe = $this->probe !== null && $this->probe !== false && $this->probe !== '0';
        $neu = 0;
        $konten = 0;
        foreach (User::find()->where(['status' => User::STATUS_ENABLED])->each() as $mensch) {
            $konten++;
            $neu += $modul->folgenLassen($mensch, $probe);
        }
        $this->stdout(($probe ? 'PROBE: ' : '') . "$konten Konten, $neu neue Folgen\n");
        return ExitCode::OK;
    }

    public function actionPersonen(): int
    {
        $modul = Module::instanz();
        if ($modul === null || $modul->personen === []) {
            $this->stderr("Modul nexus-folgen ist nicht aktiv oder 'personen' ist leer.\n");
            return ExitCode::CONFIG;
        }
        $probe = $this->probe !== null && $this->probe !== false && $this->probe !== '0';
        $neu = 0;
        $konten = 0;
        foreach (User::find()->where(['status' => User::STATUS_ENABLED])->each() as $mensch) {
            $konten++;
            $neu += $modul->personenFolgen($mensch, $probe);
        }
        $this->stdout(($probe ? 'PROBE: ' : '') . "$konten Konten, $neu neue Folgen\n");
        return ExitCode::OK;
    }
}
