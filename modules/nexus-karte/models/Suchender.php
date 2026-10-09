<?php

namespace nexus\modules\karte\models;

use humhub\modules\user\models\User;
use humhub\components\ActiveRecord;

/**
 * Ein Mitglied, das in seiner Region Mitstreiter sucht.
 *
 * @property int $user_id
 * @property string $ort
 * @property string $lat
 * @property string $lng
 * @property string|null $nachricht
 * @property string $created_at
 * @property string $updated_at
 */
class Suchender extends ActiveRecord
{
    public const MAX_NACHRICHT = 300;

    public static function tableName()
    {
        return 'nexus_karte_suchende';
    }

    public function rules()
    {
        return [
            [['user_id', 'ort', 'lat', 'lng'], 'required'],
            ['user_id', 'integer'],
            [['ort', 'nachricht'], 'filter', 'filter' => 'trim'],
            ['ort', 'string', 'max' => 150],
            ['nachricht', 'string', 'max' => self::MAX_NACHRICHT],
            ['lat', 'number', 'min' => -90, 'max' => 90],
            ['lng', 'number', 'min' => -180, 'max' => 180],
        ];
    }

    public function beforeSave($insert)
    {
        // Wie bei den Gemeinschaften: Ortsmitte (~1 km), nie ein Haus.
        $this->lat = round((float)$this->lat, Standort::NACHKOMMASTELLEN);
        $this->lng = round((float)$this->lng, Standort::NACHKOMMASTELLEN);
        $jetzt = date('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $jetzt;
        }
        $this->updated_at = $jetzt;
        return parent::beforeSave($insert);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
