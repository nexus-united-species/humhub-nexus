<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Ein Eintrag im Team-Postfach: eine Unterhaltung des Nachrichten-Moduls, an der das Team beteiligt ist.
 * Die Unterhaltung selbst bleibt im Nachrichten-Modul -- Antworten landen wie gewohnt beim Absender.
 *
 * @property int $id
 * @property int $team_id
 * @property int $message_id     Unterhaltung (mail: message.id)
 * @property string $betreff
 * @property string $quelle      team (an das Team geschrieben) | uebergabe (von jemandem uebergeben)
 * @property string $status      offen | uebernommen | kreisleitung | erledigt
 * @property int|null $uebernommen_von
 * @property int|null $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read User|null $bearbeiter
 * @property-read User|null $absender
 */
class TeamPost extends ActiveRecord
{
    public const STATUS = ['offen', 'uebernommen', 'kreisleitung', 'erledigt'];

    public static function tableName()
    {
        return 'nexus_team_post';
    }

    public function getBearbeiter()
    {
        return $this->hasOne(User::class, ['id' => 'uebernommen_von']);
    }

    public function getAbsender()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function adresse(): string
    {
        return '/mail/mail/index?id=' . (int)$this->message_id;
    }
}
