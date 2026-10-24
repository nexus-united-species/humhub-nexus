<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;

/**
 * Ein Team im Kreis (services/Teams.php): Auftrag, Aufgaben, Mitglieder, Arbeitsweise, Postfach.
 *
 * @property int $id
 * @property int $space_id
 * @property string $titel
 * @property string|null $auftrag
 * @property string $status        suchend | aktiv | pausiert
 * @property int|null $koordination_id
 * @property string|null $arbeitsweise
 * @property int|null $projekt_id  eigene Projekt-Tafel des Teams
 * @property int|null $created_by  wer das Team ausgeschrieben hat
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read Space $kreis
 * @property-read TeamAufgabe[] $aufgaben
 * @property-read User|null $koordination
 * @property-read User|null $ausschreiber
 */
class Team extends ActiveRecord
{
    public const STATUS = ['suchend', 'aktiv', 'pausiert'];

    public static function tableName()
    {
        return 'nexus_team';
    }

    public function rules()
    {
        return [
            [['titel', 'space_id'], 'required'],
            ['titel', 'string', 'max' => 120],
            [['auftrag', 'arbeitsweise'], 'string', 'max' => 10000],
            ['status', 'in', 'range' => self::STATUS],
        ];
    }

    public function beforeSave($insert)
    {
        $jetzt = date('Y-m-d H:i:s');
        if ($insert && empty($this->created_at)) {
            $this->created_at = $jetzt;
        }
        $this->updated_at = $jetzt;
        return parent::beforeSave($insert);
    }

    public function getKreis()
    {
        return $this->hasOne(Space::class, ['id' => 'space_id']);
    }

    public function getAufgaben()
    {
        return $this->hasMany(TeamAufgabe::class, ['team_id' => 'id'])->orderBy(['sortierung' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function getKoordination()
    {
        return $this->hasOne(User::class, ['id' => 'koordination_id']);
    }

    public function getAusschreiber()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /** @return TeamMitglied[] */
    public function mitglieder(string $status = 'dabei'): array
    {
        return TeamMitglied::find()->where(['team_id' => $this->id, 'status' => $status])->with('user')->orderBy('id')->all();
    }

    /** @return int[] */
    public function mitgliedIds(): array
    {
        return array_map('intval', TeamMitglied::find()->select('user_id')->where(['team_id' => $this->id, 'status' => 'dabei'])->column());
    }

    public function mitgliedschaft(int $userId): ?TeamMitglied
    {
        return TeamMitglied::findOne(['team_id' => $this->id, 'user_id' => $userId]);
    }

    public function url(string $seite = 'ansicht', array $parameter = [], bool $absolut = false): string
    {
        $pfad = $this->kreis->createUrl('/nexus-projekte/team/' . $seite, ['t' => $this->id] + $parameter);
        return $absolut ? Projekt::voll($pfad) : $pfad;
    }
}
