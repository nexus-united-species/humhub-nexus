<?php

namespace nexus\modules\protectedLibrary\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $title
 * @property string|null $author
 * @property string $language
 * @property string $media_type
 * @property int $sort_order
 * @property bool $active
 * @property string $created_at
 * @property string $updated_at
 */
class Book extends ActiveRecord
{
    public const SPRACHE_DE = 'de';
    public const SPRACHE_EN = 'en';
    public const SPRACHE_ES = 'es';

    public const SPRACHEN = [
        self::SPRACHE_DE => ['name' => 'Deutsch', 'flagge' => '🇩🇪'],
        self::SPRACHE_EN => ['name' => 'English', 'flagge' => '🇬🇧'],
        self::SPRACHE_ES => ['name' => 'Español', 'flagge' => '🇪🇸'],
    ];

    public const TYP_ROMAN = 'roman';
    public const TYP_HOERBUCH = 'hoerbuch';
    public const TYP_VIDEO = 'video';
    public const TYP_BILDER = 'bilder';
    public const TYP_TEXTE = 'texte';

    // Feste Reihenfolge fuer die Kategorie-Uebersicht -- unabhaengig davon,
    // ob eine Kategorie schon Inhalte hat (Vorgabe Josh 09.09.2026: die
    // Gliederung soll von Anfang an sichtbar sein, auch fuer noch leere
    // Kategorien wie "Video").
    public const TYPEN = [
        self::TYP_ROMAN => ['name' => 'Romane', 'icon' => 'book'],
        self::TYP_HOERBUCH => ['name' => 'Hörbücher', 'icon' => 'headphones'],
        self::TYP_VIDEO => ['name' => 'Videos', 'icon' => 'video-camera'],
        self::TYP_BILDER => ['name' => 'Bilder', 'icon' => 'picture-o'],
        self::TYP_TEXTE => ['name' => 'Texte', 'icon' => 'file-text-o'],
    ];

    public static function tableName()
    {
        return 'nexus_book';
    }

    public function rules()
    {
        return [
            [['title'], 'required'],
            [['title', 'author'], 'string', 'max' => 255],
            [['language'], 'in', 'range' => array_keys(self::SPRACHEN)],
            [['media_type'], 'in', 'range' => array_keys(self::TYPEN)],
            [['sort_order'], 'integer'],
            [['active'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    public function getChapters()
    {
        return $this->hasMany(Chapter::class, ['book_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
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
