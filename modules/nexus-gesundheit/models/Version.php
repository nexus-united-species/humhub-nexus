<?php

namespace nexus\modules\gesundheit\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Eine gespeicherte Fassung eines Artikels (Import oder Bearbeitung im Portal).
 *
 * @property int $id
 * @property int $artikel_id
 * @property string $titel
 * @property string $inhalt
 * @property string $grund
 * @property string $created_at
 * @property int|null $created_by
 */
class Version extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_gesundheit_version';
    }

    public static function speichern(Artikel $artikel, string $grund, ?int $von): void
    {
        $v = new self([
            'artikel_id' => $artikel->id,
            'titel' => $artikel->titel,
            'inhalt' => $artikel->inhalt,
            'grund' => mb_substr($grund, 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $von,
        ]);
        $v->save(false);
    }

    public function getAutor()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}
