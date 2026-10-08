<?php

namespace nexus\modules\supporterBridge\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $humhub_user_id
 * @property string $ko_fi_email
 * @property string|null $ko_fi_message_id
 * @property string $support_type
 * @property float|null $amount
 * @property string|null $currency
 * @property string|null $membership_tier
 * @property string|null $started_at
 * @property string|null $last_payment_at
 * @property string|null $expires_at
 * @property string $status
 * @property string|null $note
 * @property string $created_at
 * @property string $updated_at
 */
class Supporter extends ActiveRecord
{
    public const SUPPORT_TYPE_SUBSCRIPTION = 'subscription';
    public const SUPPORT_TYPE_ONE_TIME = 'one_time';

    public const STATUS_UNMATCHED = 'UNMATCHED';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_GRACE_PERIOD = 'GRACE_PERIOD';
    public const STATUS_EXPIRED_PENDING = 'EXPIRED_PENDING';
    public const STATUS_MANUAL = 'MANUAL';
    public const STATUS_LIFETIME = 'LIFETIME';
    public const STATUS_REVOKED = 'REVOKED';
    // Zusaetzlich zu Josh' urspruenglicher Liste: fuer zugeordnete
    // einmalige Unterstuetzungen, die bewusst NICHT automatisch Zugang
    // erhalten (Abschnitt 5 der Vorgabe), sondern auf eine Admin-
    // Entscheidung warten. UNMATCHED war dafuer nicht passend, das
    // bedeutet "keine passende E-Mail gefunden", hier ist die Person aber
    // bereits zugeordnet.
    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';

    public static function tableName()
    {
        return 'nexus_supporter';
    }

    public function rules()
    {
        return [
            [['ko_fi_email', 'support_type', 'status'], 'required'],
            [['humhub_user_id'], 'integer'],
            [['ko_fi_email'], 'string', 'max' => 255],
            [['ko_fi_message_id', 'membership_tier'], 'string', 'max' => 128],
            [['support_type', 'status'], 'string', 'max' => 32],
            [['currency'], 'string', 'max' => 8],
            [['note'], 'string', 'max' => 255],
            [['amount'], 'number'],
            [['started_at', 'last_payment_at', 'expires_at', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * Konten, die einen Zugang aktiv haben oder haben sollten -- also alles
     * ausser UNMATCHED (noch nicht zugeordnet) und REVOKED (bewusst entzogen).
     */
    public function hatAktivenAnspruch(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_GRACE_PERIOD,
            self::STATUS_MANUAL,
            self::STATUS_LIFETIME,
        ], true);
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
