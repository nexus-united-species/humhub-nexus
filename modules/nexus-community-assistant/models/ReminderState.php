<?php

namespace nexus\modules\communityAssistant\models;

use yii\db\ActiveRecord;

/**
 * Generischer "wann zuletzt gepostet"-Speicher fuer wiederkehrende
 * Aktionen ohne eigene Tabelle (Spendenaufruf, Selbstvorstellung,
 * Wochenbericht). Ueberlebt einen Neustart/Deploy, im Gegensatz zu einem
 * reinen Prozessspeicher.
 *
 * @property string $name
 * @property string $last_posted_at
 */
class ReminderState extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_reminder_state';
    }

    public function rules()
    {
        return [
            [['name', 'last_posted_at'], 'required'],
            [['name'], 'string', 'max' => 64],
            [['last_posted_at'], 'safe'],
        ];
    }

    public static function letzterLauf(string $name): ?\DateTimeImmutable
    {
        $state = self::findOne(['name' => $name]);
        return $state ? new \DateTimeImmutable($state->last_posted_at) : null;
    }

    public static function laufMarkieren(string $name, ?\DateTimeImmutable $zeitpunkt = null): void
    {
        $zeitpunkt ??= new \DateTimeImmutable();
        $state = self::findOne(['name' => $name]) ?? new self(['name' => $name]);
        $state->last_posted_at = $zeitpunkt->format('Y-m-d H:i:s');
        $state->save(false);
    }
}
