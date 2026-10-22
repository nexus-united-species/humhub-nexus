<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Ein Entwurf in einer Bild-Auswahl (A, B, C …). Das Bild selbst ist eine Datei der Kachel (Datei), liegt
 * also wie alle Entwuerfe im Dateibereich des Kreises.
 *
 * @property int $id
 * @property int $karte_id
 * @property int $datei_id
 * @property string $titel
 * @property int $gewaehlt
 * @property int|null $created_by
 * @property string $created_at
 *
 * @property-read Datei|null $datei
 * @property-read User|null $autor
 */
class Option extends ActiveRecord
{
    public const MAX_JE_KARTE = 26;

    public static function tableName()
    {
        return 'nexus_projekt_option';
    }

    public function getDatei()
    {
        return $this->hasOne(Datei::class, ['id' => 'datei_id']);
    }

    public function getAutor()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /** Bild-Adresse und Name -- null, wenn die Datei im Kreis geloescht wurde */
    public function info(): ?array
    {
        return $this->datei?->info();
    }
}
