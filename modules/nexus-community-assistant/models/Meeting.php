<?php

namespace nexus\modules\communityAssistant\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $title
 * @property string $message
 * @property int|null $recurrence_weekday
 * @property string|null $recurrence_time
 * @property string|null $event_date
 * @property bool $active
 * @property string|null $last_posted_at
 * @property string $created_at
 * @property string $updated_at
 */
class Meeting extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_meeting';
    }

    public function rules()
    {
        return [
            [['title', 'message'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['message'], 'string'],
            [['recurrence_weekday'], 'integer', 'min' => 1, 'max' => 7],
            [['recurrence_time'], 'string', 'max' => 5],
            [['event_date', 'last_posted_at', 'created_at', 'updated_at'], 'safe'],
            [['active'], 'boolean'],
        ];
    }

    public function istWiederkehrend(): bool
    {
        return $this->recurrence_weekday !== null && $this->recurrence_time !== null;
    }

    public function istEinmalig(): bool
    {
        return $this->event_date !== null;
    }

    /**
     * @return int[]
     */
    public function zielSpaceIds(): array
    {
        return MeetingSpace::find()
            ->select('space_id')
            ->where(['meeting_id' => $this->id])
            ->column();
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
