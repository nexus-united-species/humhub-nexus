<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Rueckmeldung zu einer Kachel in der Abstimmung: "Passt so" oder "Ich habe eine Aenderung".
 *
 * @property int $id
 * @property int $karte_id
 * @property int $runde
 * @property int $user_id
 * @property string $art     ok | aenderung (Bild-Auswahl: zustimmung | bedenken | einwand)
 * @property int $option_id  0 = Stimme zur Kachel, sonst Stimme zu einem Entwurf der Bild-Auswahl
 * @property string|null $text
 * @property string $created_at
 *
 * @property-read User|null $autor
 */
class Stimme extends ActiveRecord
{
    public const MIN_ZEICHEN = 5;
    public const MAX_ZEICHEN = 3000;

    public static function tableName()
    {
        return 'nexus_projekt_stimme';
    }

    public function getAutor()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /** Antworten auf diese Rueckmeldung, aelteste zuerst */
    public function getAntworten()
    {
        return $this->hasMany(Antwort::class, ['stimme_id' => 'id'])->orderBy('id')->with('autor');
    }
}
