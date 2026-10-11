<?php

namespace nexus\modules\wissen\models;

use humhub\components\ActiveRecord;
use nexus\modules\wissen\services\Sammlung;

/**
 * @property int $id
 * @property string $sammlung    Kennung der Sammlung
 * @property string $schluessel  eindeutig je Sammlung (Manifest-Schluessel oder Dateipfad)
 * @property string|null $kennung kurze Kennung der Redaktion
 * @property string $datei       Dateiname -- Ziel relativer .md-Links
 * @property string $slug
 * @property string $titel
 * @property string $kurztitel   Titel bis zum Doppelpunkt -- fuer Querverweise
 * @property string $thema
 * @property string $inhalt      Markdown
 * @property string|null $pruefdatum
 * @property string|null $wiedervorlage
 * @property string|null $erinnert_am
 * @property string|null $import_hash
 * @property int $im_portal_geaendert
 * @property string $created_at
 * @property string $updated_at
 * @property int|null $updated_by
 */
class Artikel extends ActiveRecord
{
    public const WORT_TABELLE = 'nexus_wissen_wort';

    public static function tableName()
    {
        return 'nexus_wissen_artikel';
    }

    public function rules()
    {
        return [
            [['sammlung', 'schluessel', 'datei', 'slug', 'titel', 'kurztitel', 'inhalt'], 'required'],
            [['titel', 'schluessel'], 'string', 'max' => 255],
            ['thema', 'string', 'max' => 100],
            ['thema', 'default', 'value' => ''],
            [['pruefdatum', 'wiedervorlage'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function beforeSave($insert)
    {
        $jetzt = date('Y-m-d H:i:s');
        if ($insert && empty($this->created_at)) {
            $this->created_at = $jetzt;
        }
        $this->updated_at = $jetzt;
        return parent::beforeSave($insert);
    }

    public static function inSammlung(string $kennung): \yii\db\ActiveQuery
    {
        return static::find()->where(['sammlung' => $kennung]);
    }

    public static function kurztitelAus(string $titel): string
    {
        $teil = trim(explode(':', $titel, 2)[0]);
        return mb_substr($teil !== '' ? $teil : $titel, 0, 190);
    }

    /** @return string[] */
    public function woerter(string $art): array
    {
        return array_map('strval', (new \yii\db\Query())->select('wort')->from(self::WORT_TABELLE)
            ->where(['artikel_id' => $this->id, 'art' => $art])->orderBy('wort')->column());
    }

    /** @param string[] $woerter */
    public function setzeWoerter(string $art, array $woerter): void
    {
        $db = static::getDb();
        $db->createCommand()->delete(self::WORT_TABELLE, ['artikel_id' => $this->id, 'art' => $art])->execute();
        $gesehen = [];
        foreach ($woerter as $wort) {
            $wort = trim(preg_replace('/\s+/u', ' ', (string)$wort));
            $schluessel = mb_strtolower($wort);
            if ($wort === '' || mb_strlen($wort) > 120 || isset($gesehen[$schluessel])) {
                continue;
            }
            $gesehen[$schluessel] = true;
            $db->createCommand()->insert(self::WORT_TABELLE, ['artikel_id' => $this->id, 'wort' => $wort, 'art' => $art])->execute();
        }
    }

    public function url(Sammlung $sammlung, bool $absolut = false): string
    {
        return $sammlung->url('artikel', ['a' => $this->slug], $absolut);
    }
}
