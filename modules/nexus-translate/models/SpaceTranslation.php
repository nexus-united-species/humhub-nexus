<?php

namespace humhub\modules\nexusTranslate\models;

use yii\db\ActiveRecord;

/**
 * @property int $space_id
 * @property string $language   Zielsprache als Kurzcode: en, es
 * @property string $feld       name | description
 * @property string $quelle_hash md5 des deutschen Originals
 * @property string $text
 * @property bool $geprueft
 * @property string $updated_at
 */
class SpaceTranslation extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_space_translation';
    }
}
