<?php

namespace nexus\modules\wissen\models;

use humhub\components\ActiveRecord;

/**
 * Eine Sammlung, die im Portal angelegt wurde (von Kreisbesitzern, Kreis-Admins oder Systemadmins).
 * Sammlungen aus den Einstellungen (common.php) stehen hier nicht.
 *
 * @property int $id
 * @property string $kennung
 * @property int $kreis_id
 * @property string $name
 * @property string|null $beschreibung
 * @property string $symbol
 * @property string|null $hinweis
 * @property string $created_at
 * @property int|null $created_by
 */
class SammlungEintrag extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_wissen_sammlung';
    }

    public function rules()
    {
        return [
            [['kennung', 'kreis_id', 'name'], 'required'],
            ['name', 'string', 'min' => 2, 'max' => 120],
            ['beschreibung', 'string', 'max' => 255],
            ['hinweis', 'string', 'max' => 2000],
            ['symbol', 'match', 'pattern' => '/^[a-z0-9-]+$/'],
        ];
    }

    /** Einstellungen im selben Aufbau wie ein Eintrag aus common.php */
    public function einstellungen(): array
    {
        return array_filter([
            'kreis' => (int)$this->kreis_id,
            'name' => $this->name,
            'beschreibung' => $this->beschreibung,
            'symbol' => $this->symbol,
            'hinweis' => $this->hinweis,
        ], fn($w) => $w !== null && $w !== '');
    }
}
