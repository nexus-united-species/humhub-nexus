<?php

namespace nexus\modules\gesundheit\models;

use humhub\components\ActiveRecord;
use humhub\modules\space\models\Space;

/**
 * @property int $id
 * @property string $schluessel  vollstaendiger Original-Dateiname (eindeutig)
 * @property string|null $gnummer
 * @property string $datei       Dateiname im Artikel-Ordner
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
    public const THEMEN = [
        '01_Ernaehrung' => 'ernaehrung',
        '02_Heilpflanzen' => 'heilpflanzen',
        '03_Bewegung' => 'bewegung',
        '04_Beschwerden' => 'beschwerden',
        '05_Vorsorge' => 'vorsorge',
        '06_Gesundheitswissen' => 'wissen',
    ];

    public static function tableName()
    {
        return 'nexus_gesundheit_artikel';
    }

    public function rules()
    {
        return [
            [['schluessel', 'datei', 'slug', 'titel', 'kurztitel', 'thema', 'inhalt'], 'required'],
            [['titel', 'schluessel'], 'string', 'max' => 255],
            [['pruefdatum', 'wiedervorlage'], 'date', 'format' => 'php:Y-m-d'],
            ['thema', 'in', 'range' => array_keys(self::THEMEN)],
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

    public static function kurztitelAus(string $titel): string
    {
        $teil = trim(explode(':', $titel, 2)[0]);
        return mb_substr($teil !== '' ? $teil : $titel, 0, 190);
    }

    /** @return string[] */
    public function woerter(string $art): array
    {
        return array_map('strval', (new \yii\db\Query())->select('wort')->from('nexus_gesundheit_wort')
            ->where(['artikel_id' => $this->id, 'art' => $art])->orderBy('wort')->column());
    }

    /** @param string[] $woerter */
    public function setzeWoerter(string $art, array $woerter): void
    {
        $db = static::getDb();
        $db->createCommand()->delete('nexus_gesundheit_wort', ['artikel_id' => $this->id, 'art' => $art])->execute();
        $gesehen = [];
        foreach ($woerter as $wort) {
            $wort = trim(preg_replace('/\s+/u', ' ', (string)$wort));
            $schluessel = mb_strtolower($wort);
            if ($wort === '' || mb_strlen($wort) > 120 || isset($gesehen[$schluessel])) {
                continue;
            }
            $gesehen[$schluessel] = true;
            $db->createCommand()->insert('nexus_gesundheit_wort', ['artikel_id' => $this->id, 'wort' => $wort, 'art' => $art])->execute();
        }
    }

    public function url(Space $kreis, bool $absolut = false): string
    {
        return $kreis->createUrl('/nexus-gesundheit/wissen/artikel', ['a' => $this->slug], $absolut);
    }
}
