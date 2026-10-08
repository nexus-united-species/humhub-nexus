<?php

namespace humhub\modules\nexusTranslate\models;

use yii\db\ActiveRecord;

/**
 * @property int $post_id
 * @property string $language
 * @property string $translated_text
 * @property string $created_at
 */
class TranslationCache extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_translation_cache';
    }
}
