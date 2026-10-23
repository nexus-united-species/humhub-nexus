<?php

namespace nexus\modules\communityAssistant\models;

use humhub\components\ActiveRecord;

/**
 * Etwas, das Nova sich ueber einen Menschen merkt (z. B. "arbeitet in Kreis 7 am Kalender mit").
 * Der Mensch sieht die Liste auf seiner Gespraechsseite und loescht Eintraege selbst.
 *
 * @property int $id
 * @property int $user_id
 * @property string $text
 * @property string $created_at
 */
class Merkpunkt extends ActiveRecord
{
    public const MAX_JE_MENSCH = 30;
    public const MAX_LAENGE = 300;

    public static function tableName()
    {
        return 'nexus_nova_merk';
    }
}
