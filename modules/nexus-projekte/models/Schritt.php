<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;

/**
 * Ein Teilschritt zum Abhaken innerhalb einer Kachel.
 *
 * @property int $id
 * @property int $karte_id
 * @property string $text
 * @property int $erledigt
 * @property int $sortierung
 */
class Schritt extends ActiveRecord
{
    public const MAX = 30;

    public static function tableName()
    {
        return 'nexus_projekt_schritt';
    }

    public function rules()
    {
        return [
            [['karte_id', 'text'], 'required'],
            ['text', 'string', 'max' => 255],
        ];
    }
}
