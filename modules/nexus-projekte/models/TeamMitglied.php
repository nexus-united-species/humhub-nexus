<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Mitgliedschaft in einem Team: angefragt (nach "Da helfe ich mit") -> dabei (nach dem Gespraech von der
 * Leitung aufgenommen) -> beendet ("Ich hoere auf" oder Absage). Eine Zeile je Mensch und Team.
 *
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property string $status      angefragt | dabei | abgesagt | beendet
 * @property string|null $zeit   wann/wie viel, in eigenen Worten
 * @property string|null $nachricht
 * @property string|null $aufgaben  gewuenschte Aufgaben (IDs, kommagetrennt)
 * @property string|null $weg_bis   abwesend bis einschliesslich
 * @property string $created_at
 * @property int|null $entschieden_von
 * @property string|null $entschieden_am
 *
 * @property-read User $user
 */
class TeamMitglied extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_team_mitglied';
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function istWeg(): bool
    {
        return $this->weg_bis !== null && $this->weg_bis >= date('Y-m-d');
    }

    /** @return int[] */
    public function aufgabenIds(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string)$this->aufgaben))));
    }
}
