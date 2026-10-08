<?php

namespace nexus\modules\karte\models;

use humhub\modules\space\models\Space;
use yii\db\ActiveRecord;

/**
 * Ort einer Gemeinschaft.
 *
 * @property int $space_id
 * @property string|null $ort
 * @property string|null $lat
 * @property string|null $lng
 * @property int|null $updated_by
 * @property string $updated_at
 */
class Standort extends ActiveRecord
{
    /** Zwei Nachkommastellen = rund 1 km: Ortsmitte, kein Haus. */
    public const NACHKOMMASTELLEN = 2;

    public static function tableName()
    {
        return 'nexus_gemeinschaft_ort';
    }

    public function rules()
    {
        return [
            ['space_id', 'required'],
            ['space_id', 'integer'],
            ['ort', 'filter', 'filter' => 'trim'],
            ['ort', 'string', 'max' => 150],
            ['lat', 'number', 'min' => -90, 'max' => 90],
            ['lng', 'number', 'min' => -180, 'max' => 180],
        ];
    }

    public function beforeSave($insert)
    {
        if ($this->lat !== null && $this->lat !== '') {
            $this->lat = round((float)$this->lat, self::NACHKOMMASTELLEN);
            $this->lng = round((float)$this->lng, self::NACHKOMMASTELLEN);
        }
        $this->updated_at = date('Y-m-d H:i:s');
        return parent::beforeSave($insert);
    }

    public function getSpace()
    {
        return $this->hasOne(Space::class, ['id' => 'space_id']);
    }

    public function hatOrt(): bool
    {
        return $this->lat !== null && $this->lng !== null && trim((string)$this->ort) !== '';
    }
}
