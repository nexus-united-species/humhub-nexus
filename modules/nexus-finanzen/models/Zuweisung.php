<?php

namespace nexus\modules\finanzen\models;

use humhub\components\ActiveRecord;

/**
 * Geld, das einer Kachel zugewiesen ist (= reserviert). Laufende Kacheln: fuer einen Monat (YYYY-MM),
 * einmalige: monat NULL -- das Geld bleibt fuer den Zweck erhalten und verfaellt nicht am Monatsende.
 * Entsteht nur auf zwei Wegen: zweckgebundener Eingang (eingang_id) oder bestaetigte Verteilung (verteilung_id).
 *
 * @property int $id
 * @property int $kachel_id
 * @property string|null $monat
 * @property int $betrag
 * @property int|null $eingang_id
 * @property int|null $verteilung_id
 */
class Zuweisung extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_fin_zuweisung';
    }

    public function getKachel()
    {
        return $this->hasOne(Kachel::class, ['id' => 'kachel_id']);
    }
}
