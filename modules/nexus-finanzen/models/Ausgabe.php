<?php

namespace nexus\modules\finanzen\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Tatsaechlich bezahltes Geld aus einer Kachel. Auszahlungen aus einer Verguetungs-Kachel (beguenstigt_id)
 * zaehlen erst, wenn eine ZWEITE finanzverantwortliche Person sie freigegeben hat (status "offen" bis dahin).
 *
 * @property int $id
 * @property int $kachel_id
 * @property string|null $monat
 * @property string $datum
 * @property int $betrag
 * @property string|null $notiz
 * @property string|null $beleg
 * @property string $status offen|freigegeben
 * @property int|null $erfasst_von
 * @property int|null $freigegeben_von
 */
class Ausgabe extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_fin_ausgabe';
    }

    public function getKachel()
    {
        return $this->hasOne(Kachel::class, ['id' => 'kachel_id']);
    }

    public function getErfasser()
    {
        return $this->hasOne(User::class, ['id' => 'erfasst_von']);
    }
}
