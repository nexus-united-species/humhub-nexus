<?php

namespace nexus\modules\finanzen\models;

use humhub\components\ActiveRecord;

/**
 * Eingegangenes Geld. Frei (zweck_kachel_id NULL) oder zweckgebunden. Referenz und Notiz sind intern --
 * sie stehen nie auf der Ampel-Seite der Mitglieder und nie in den oeffentlichen Zahlen.
 *
 * @property int $id
 * @property string $datum
 * @property int $betrag Cent
 * @property string $waehrung
 * @property string $quelle kofi|stripe|ueberweisung|bar|sonstiges|start
 * @property int|null $zweck_kachel_id
 * @property string|null $referenz
 * @property string|null $notiz
 * @property string|null $kofi_id
 * @property int|null $erfasst_von
 * @property string $created_at
 */
class Eingang extends ActiveRecord
{
    public const QUELLEN = ['kofi', 'stripe', 'ueberweisung', 'bar', 'sonstiges', 'start'];

    public static function tableName()
    {
        return 'nexus_fin_eingang';
    }

    public function getZweck()
    {
        return $this->hasOne(Kachel::class, ['id' => 'zweck_kachel_id']);
    }
}
