<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\cfiles\models\File as KreisDatei;
use humhub\modules\user\models\User;

/**
 * Eine an einen Vorschlag angehaengte Datei. Die Datei selbst liegt im Dateibereich des Kreises
 * (Ordner "Projekte/<Projekt>/<Kachel>") -- dort gelten die Leserechte des Kreises, und die
 * Nextcloud-Spiegelung nimmt sie mit. Mehrere Dateien eines Hochladens bilden eine Fassung.
 *
 * @property int $id
 * @property int $karte_id
 * @property int $cfile_id
 * @property int $fassung
 * @property string|null $hinweis
 * @property int|null $created_by
 * @property string $created_at
 *
 * @property-read KreisDatei|null $kreisDatei
 * @property-read User|null $autor
 */
class Datei extends ActiveRecord
{
    public const MAX_JE_FASSUNG = 10;

    public static function tableName()
    {
        return 'nexus_projekt_datei';
    }

    public function getKreisDatei()
    {
        return $this->hasOne(KreisDatei::class, ['id' => 'cfile_id']);
    }

    public function getAutor()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /** Name, Adresse zum Herunterladen und ob es ein Bild ist -- null, wenn die Datei im Kreis geloescht wurde */
    public function info(): ?array
    {
        $basis = $this->kreisDatei?->baseFile;
        if ($basis === null) {
            return null;
        }
        return [
            'name' => (string)$basis->file_name,
            // Ohne das "F2 - " vorn, das nur dazu dient, Fassungen im Dateibereich auseinanderzuhalten
            'anzeige' => (string)preg_replace('/^F\d+ - /', '', (string)$basis->file_name),
            'url' => $basis->getUrl(),
            'bild' => str_starts_with((string)$basis->mime_type, 'image/'),
            'groesse' => (int)$basis->size,
        ];
    }
}
