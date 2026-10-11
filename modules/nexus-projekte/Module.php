<?php

namespace nexus\modules\projekte;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\space\models\Space;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\services\Texte;

/**
 * Projekt-Tafeln (11.10.2026): Projekte in Kreisen als Pinnwand mit Kacheln in vier Spalten
 * (Offen -> In Arbeit -> Zur Abstimmung -> Fertig), dazu "Meine Aufgaben" fuer jeden Menschen.
 *
 * Wird je Kreis eingeschaltet (Kreis -> Einstellungen -> Module), wie Wiki oder Kalender.
 *
 * Rollen (services/Rechte.php):
 * - Projekt anlegen: Kreis-Admins (mit 'projektAnlegen' => 'mitglieder' alle Mitglieder)
 * - Projektleitung (eine Person je Projekt) und Kreis-Admins: Kacheln anlegen, zuweisen, verschieben
 * - Verantwortliche einer Kachel: sie bearbeiten und bis "Zur Abstimmung" schieben
 * - Mitglieder des Kreises: "Das uebernehme ich", Teilschritte abhaken (wenn beteiligt), abstimmen
 *
 * Bewusst einfach gehalten: Die HumHub-Aufgaben waren fuer viele Mitglieder zu abstrakt
 * (Listen, Status, "Aufgabe starten"). Hier gibt es genau eine verantwortliche Person je Kachel und
 * Knoepfe statt Ziehen und Ablegen -- das funktioniert auch auf dem Handy.
 */
class Module extends ContentContainerModule
{
    /*
     * Einstellungen / settings: protected/config/common.php (alle optional)
     *   'modules' => ['nexus-projekte' => ['projektAnlegen' => 'kreisadmins', 'erinnern' => true]]
     */

    /** @var string 'kreisadmins' (Vorgabe) oder 'mitglieder' -- wer neue Projekte anlegen darf */
    public $projektAnlegen = 'kreisadmins';

    /** @var bool Erinnerungen an Fristen und Abstimmungen als private Nachricht (braucht nexus-arbeitszeit) */
    public $erinnern = true;

    /** @var int Tage, die eine Abstimmung laeuft, wenn kein Datum gewaehlt wird */
    public $abstimmungTage = 7;

    public static function instanz(): ?self
    {
        $modul = \Yii::$app->getModule('nexus-projekte');
        return $modul instanceof self ? $modul : null;
    }

    public function getName()
    {
        return 'N.E.X.U.S. Project Boards';
    }

    public function getDescription()
    {
        return 'Project boards with cards, responsibilities, deadlines and one-click feedback.';
    }

    public function getContentContainerTypes()
    {
        return [Space::class];
    }

    public function getContentContainerName(ContentContainerActiveRecord $container)
    {
        return Texte::t('projekte');
    }

    public function getContentContainerDescription(ContentContainerActiveRecord $container)
    {
        return Texte::t('modul_beschreibung');
    }

    public function disableContentContainer(ContentContainerActiveRecord $container)
    {
        parent::disableContentContainer($container);
        // Projekte haengen am Kreis: wer das Modul im Kreis ausschaltet, entfernt sie mit
        foreach (Projekt::find()->where(['space_id' => $container->id])->all() as $projekt) {
            $projekt->delete();
        }
    }

    public static function istAn(Space $kreis): bool
    {
        return $kreis->moduleManager->isEnabled('nexus-projekte');
    }
}
