<?php

namespace nexus\modules\supporterBridge\models;

use yii\db\ActiveRecord;

/**
 * Schlankes Audit-Protokoll. Bewusst OHNE Zahlungsdetails -- nur wer, was,
 * wann, und ein Verweis (z. B. Ko-fi message_id) zur Nachvollziehbarkeit.
 *
 * @property int $id
 * @property int|null $humhub_user_id
 * @property string $event_type
 * @property string|null $reference
 * @property string $created_at
 */
class SupporterEvent extends ActiveRecord
{
    public const PAYMENT_RECEIVED = 'PAYMENT_RECEIVED';
    public const USER_MATCHED = 'USER_MATCHED';
    public const SPACE_ACCESS_GRANTED = 'SPACE_ACCESS_GRANTED';
    public const GRACE_PERIOD_STARTED = 'GRACE_PERIOD_STARTED';
    public const ACCESS_REVOKED = 'ACCESS_REVOKED';
    public const MANUAL_ACCESS_GRANTED = 'MANUAL_ACCESS_GRANTED';
    public const DUPLICATE_IGNORED = 'DUPLICATE_IGNORED';
    public const UNMATCHED_PAYMENT = 'UNMATCHED_PAYMENT';

    public static function tableName()
    {
        return 'nexus_supporter_event';
    }

    public function rules()
    {
        return [
            [['event_type'], 'required'],
            [['humhub_user_id'], 'integer'],
            [['event_type'], 'string', 'max' => 64],
            [['reference'], 'string', 'max' => 128],
            [['created_at'], 'safe'],
        ];
    }

    public static function protokollieren(string $eventType, ?int $humhubUserId, ?string $reference): void
    {
        $event = new self([
            'event_type' => $eventType,
            'humhub_user_id' => $humhubUserId,
            'reference' => $reference,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $event->save(false);
    }
}
