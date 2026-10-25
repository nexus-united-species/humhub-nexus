<?php

namespace nexus\modules\finanzen\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Eine Verteilung aus dem freien Topf: vom System vorgeschlagen, von einer finanzverantwortlichen Person
 * bestaetigt. Enthaelt sie Geld fuer eine Kachel, aus der die bestaetigende Person selbst verguetet wird,
 * wartet sie auf eine zweite Person ("wartet_zweite"). Erst bei "gebucht" entstehen die Zuweisungen.
 *
 * @property int $id
 * @property string $monat
 * @property string $vorschlag JSON [{kachel_id, betrag}]
 * @property int $summe
 * @property string $status vorgeschlagen|wartet_zweite|gebucht|verworfen
 * @property int|null $vorgeschlagen_von
 * @property int|null $bestaetigt_von
 * @property int|null $zweite_von
 */
class Verteilung extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_fin_verteilung';
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

    /** @return array<int, array{kachel_id: int, betrag: int}> */
    public function posten(): array
    {
        return json_decode($this->vorschlag, true) ?: [];
    }

    public function getBestaetiger()
    {
        return $this->hasOne(User::class, ['id' => 'bestaetigt_von']);
    }
}
