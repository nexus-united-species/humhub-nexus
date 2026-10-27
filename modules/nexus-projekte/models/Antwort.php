<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Antwort auf eine Rueckmeldung (Stimme mit Text) in der Abstimmung.
 *
 * @property int $id
 * @property int $stimme_id
 * @property int $user_id
 * @property string $text
 * @property string $created_at
 *
 * @property-read User|null $autor
 */
class Antwort extends ActiveRecord
{
    public const MIN_ZEICHEN = 2;
    public const MAX_ZEICHEN = 3000;

    public static function tableName()
    {
        return 'nexus_projekt_antwort';
    }

    public function getAutor()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
