<?php

namespace nexus\modules\communityAssistant\models;

use humhub\components\ActiveRecord;

/**
 * Ein Gespraech eines Mitglieds mit Nova (Seite "Nova – Gespraeche"). Gehoert genau einem Menschen.
 *
 * @property int $id
 * @property int $user_id
 * @property string $titel
 * @property string $art     frage | kritik | idee | text (siehe services/Gespraeche::ARTEN)
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read GespraechNachricht[] $nachrichten
 */
class Gespraech extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_nova_gespraech';
    }

    public function getNachrichten()
    {
        return $this->hasMany(GespraechNachricht::class, ['gespraech_id' => 'id'])->orderBy('id');
    }
}
