<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Eine Aufgabe eines Teams: regelmaessig ("Vorstellungen beantworten") oder einmalig, mit zustaendiger Person
 * und Vertretung. Die Mitglieder tragen sich selbst ein.
 *
 * @property int $id
 * @property int $team_id
 * @property string $titel
 * @property string $art          regelmaessig | einmalig
 * @property string|null $aufwand z. B. "ca. 1 Std./Woche"
 * @property int|null $zustaendig_id
 * @property int|null $vertretung_id
 * @property int $erledigt        nur fuer einmalige Aufgaben
 * @property int $sortierung
 *
 * @property-read User|null $zustaendig
 * @property-read User|null $vertretung
 */
class TeamAufgabe extends ActiveRecord
{
    public const ARTEN = ['regelmaessig', 'einmalig'];
    public const MAX = 30;

    public static function tableName()
    {
        return 'nexus_team_aufgabe';
    }

    public function getZustaendig()
    {
        return $this->hasOne(User::class, ['id' => 'zustaendig_id']);
    }

    public function getVertretung()
    {
        return $this->hasOne(User::class, ['id' => 'vertretung_id']);
    }
}
