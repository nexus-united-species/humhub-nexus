<?php

namespace nexus\modules\communityAssistant\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $meeting_id
 * @property int $space_id
 */
class MeetingSpace extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_meeting_space';
    }

    public function rules()
    {
        return [
            [['meeting_id', 'space_id'], 'required'],
            [['meeting_id', 'space_id'], 'integer'],
        ];
    }
}
