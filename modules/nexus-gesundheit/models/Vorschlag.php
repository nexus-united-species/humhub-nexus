<?php

namespace nexus\modules\gesundheit\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Ein Vorschlag eines Kreis-Mitglieds: etwas ergaenzen oder aendern.
 *
 * @property int $id
 * @property int $artikel_id
 * @property string $art      ergaenzen | aendern
 * @property string $text
 * @property string $status   offen | uebernommen | abgelehnt
 * @property string|null $antwort
 * @property string $created_at
 * @property int $created_by
 * @property string|null $erledigt_am
 * @property int|null $erledigt_von
 */
class Vorschlag extends ActiveRecord
{
    public const ARTEN = ['ergaenzen', 'aendern'];
    public const MIN_ZEICHEN = 10;
    public const MAX_ZEICHEN = 5000;

    public static function tableName()
    {
        return 'nexus_gesundheit_vorschlag';
    }

    public function rules()
    {
        return [
            [['artikel_id', 'art', 'text', 'created_by'], 'required'],
            ['art', 'in', 'range' => self::ARTEN],
            ['text', 'string', 'min' => self::MIN_ZEICHEN, 'max' => self::MAX_ZEICHEN],
            ['status', 'in', 'range' => ['offen', 'uebernommen', 'abgelehnt']],
        ];
    }

    public function getArtikel()
    {
        return $this->hasOne(Artikel::class, ['id' => 'artikel_id']);
    }

    public function getAutor()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }
}
