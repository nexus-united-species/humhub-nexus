<?php

namespace nexus\modules\arbeitszeit\models;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use humhub\components\ActiveRecord;

/**
 * Ein Arbeitsstunden-Eintrag.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $name_extern
 * @property string|null $verlauf
 * @property int|null $space_id
 * @property string $stunden
 * @property string $datum
 * @property string $beschreibung
 * @property string $status
 * @property string $quelle
 * @property int|null $geprueft_von
 * @property string|null $geprueft_am
 * @property string|null $rueckfrage
 * @property string $created_at
 * @property string $updated_at
 */
class Eintrag extends ActiveRecord
{
    public const STATUS_ENTWURF = 'entwurf';          // Assistent wartet noch auf den Kreis
    public const STATUS_OFFEN = 'offen';              // wartet auf Freigabe
    public const STATUS_FREIGEGEBEN = 'freigegeben';
    public const STATUS_RUECKSPRACHE = 'ruecksprache';

    public const MAX_STUNDEN = 16.0;                  // mehr an einem Tag ist fast sicher ein Tippfehler
    public const MAX_BESCHREIBUNG = 500;

    public static function tableName()
    {
        return 'nexus_arbeitszeit';
    }

    public function rules()
    {
        return [
            [['stunden', 'datum', 'beschreibung'], 'required'],
            ['user_id', 'required', 'when' => fn($m) => empty($m->name_extern)],
            ['name_extern', 'string', 'max' => 255],
            ['stunden', 'number', 'min' => 0.25, 'max' => self::MAX_STUNDEN],
            ['datum', 'date', 'format' => 'php:Y-m-d'],
            ['datum', 'nichtInDerZukunft'],
            ['beschreibung', 'string', 'max' => self::MAX_BESCHREIBUNG],
            ['beschreibung', 'filter', 'filter' => 'trim'],
            ['space_id', 'integer'],
        ];
    }

    public function nichtInDerZukunft(string $attribut): void
    {
        if ($this->$attribut > date('Y-m-d')) {
            $this->addError($attribut, 'zukunft');
        }
    }

    public function beforeSave($insert)
    {
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

    public function getSpace()
    {
        return $this->hasOne(Space::class, ['id' => 'space_id']);
    }

    public function getPruefer()
    {
        return $this->hasOne(User::class, ['id' => 'geprueft_von']);
    }

    /** Name der Person: Portal-Konto, sonst der uebernommene Name (Telegram-Stunden ohne Konto). */
    public function name(): string
    {
        return $this->user->displayName ?? ($this->name_extern ?: '?');
    }

    /** Aenderung ins Protokoll schreiben (aelteste oben). */
    public function protokolliere(string $wer, string $was): void
    {
        $zeile = date('d.m.Y H:i') . ' ' . $wer . ': ' . $was;
        $this->verlauf = trim(($this->verlauf ? $this->verlauf . "\n" : '') . $zeile);
    }

    /** "1,5" statt "1.50" -- so, wie Menschen Stunden schreiben. */
    public function stundenText(): string
    {
        return rtrim(rtrim(number_format((float)$this->stunden, 2, ',', ''), '0'), ',');
    }
}
