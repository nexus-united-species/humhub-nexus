<?php

namespace nexus\modules\communityAssistant\models;

use humhub\components\ActiveRecord;

/**
 * Eine Nachricht in einem Nova-Gespraech.
 *
 * @property int $id
 * @property int $gespraech_id
 * @property string $rolle     user | assistant
 * @property string $inhalt    Markdown
 * @property string|null $quellen  JSON-Liste [{titel, art, datei|url}]
 * @property string $kosten    geschaetzte KI-Kosten dieser Antwort in Euro
 * @property string $created_at
 */
class GespraechNachricht extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_nova_nachricht';
    }

    /** @return array<int, array{titel: string, art: string, url?: string}> */
    public function quellenListe(): array
    {
        $liste = json_decode((string)$this->quellen, true);
        return is_array($liste) ? $liste : [];
    }
}
