<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;

/**
 * Eine Textaenderung in einem Vorschlag: an welcher Stelle, was bisher steht, was kuenftig stehen soll.
 * Abgestimmt wird ueber genau diesen Wortlaut -- so wird spaeter auch genau das eingearbeitet.
 *
 * @property int $id
 * @property int $karte_id
 * @property string $stelle   z. B. "Bauplan V13.1, Kap. 2.2, Absatz 'Das Geistesleben'"
 * @property string|null $alt
 * @property string|null $neu
 * @property int $sortierung
 */
class Aenderung extends ActiveRecord
{
    public const MAX = 20;
    public const MAX_TEXT = 20000;

    public static function tableName()
    {
        return 'nexus_projekt_aenderung';
    }

    public function rules()
    {
        return [
            [['karte_id', 'stelle'], 'required'],
            ['stelle', 'string', 'max' => 255],
            [['alt', 'neu'], 'string', 'max' => self::MAX_TEXT],
        ];
    }
}
