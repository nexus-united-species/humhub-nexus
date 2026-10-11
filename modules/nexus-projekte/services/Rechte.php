<?php

namespace nexus\modules\projekte\services;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\Module;

/**
 * Wer darf was -- an einer Stelle, damit Ansicht und Steuerung dasselbe pruefen.
 */
class Rechte
{
    public static function istKreisAdmin(Space $kreis, ?User $mensch): bool
    {
        return $mensch !== null && ($mensch->isSystemAdmin() || $kreis->isAdmin($mensch->id));
    }

    public static function istMitglied(Space $kreis, ?User $mensch): bool
    {
        return $mensch !== null && ($kreis->isMember($mensch->id) || $mensch->isSystemAdmin());
    }

    public static function darfProjektAnlegen(Space $kreis, ?User $mensch): bool
    {
        $modul = Module::instanz();
        if ($modul !== null && $modul->projektAnlegen === 'mitglieder') {
            return self::istMitglied($kreis, $mensch);
        }
        return self::istKreisAdmin($kreis, $mensch);
    }

    /** Projektleitung und Kreis-Admins: Projekt aendern, Kacheln anlegen, zuweisen, verschieben, loeschen */
    public static function darfLeiten(Projekt $projekt, ?User $mensch): bool
    {
        return $mensch !== null && ($projekt->istLeitung((int)$mensch->id) || self::istKreisAdmin($projekt->kreis, $mensch));
    }

    /** Verantwortliche duerfen ihre Kachel bearbeiten und verschieben -- nur "Fertig" setzt die Leitung */
    public static function darfKarteBearbeiten(Karte $karte, ?User $mensch): bool
    {
        return $mensch !== null && ((int)$karte->verantwortlich_id === (int)$mensch->id || self::darfLeiten($karte->projekt, $mensch));
    }

    public static function darfVerschieben(Karte $karte, string $ziel, ?User $mensch): bool
    {
        if (self::darfLeiten($karte->projekt, $mensch)) {
            return true;
        }
        return $ziel !== 'fertig' && $karte->spalte !== 'fertig' && self::darfKarteBearbeiten($karte, $mensch);
    }

    /** Teilschritte abhaken: wer an der Kachel beteiligt ist */
    public static function darfAbhaken(Karte $karte, ?User $mensch): bool
    {
        return $mensch !== null && (self::darfKarteBearbeiten($karte, $mensch) || $karte->istHelfer((int)$mensch->id));
    }

    public static function darfUebernehmen(Karte $karte, ?User $mensch): bool
    {
        return $karte->verantwortlich_id === null && $karte->spalte !== 'fertig' && $karte->projekt->status === 'aktiv'
            && self::istMitglied($karte->projekt->kreis, $mensch);
    }

    public static function darfAbstimmen(Karte $karte, ?User $mensch): bool
    {
        return $karte->spalte === 'abstimmung' && self::istMitglied($karte->projekt->kreis, $mensch);
    }
}
