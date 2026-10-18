<?php

namespace nexus\modules\wissen\models;

use humhub\components\ActiveRecord;
use nexus\modules\wissen\services\Texte;

/**
 * Eine Kategorie (Thema) einer Sammlung.
 *
 * @property int $id
 * @property string $sammlung
 * @property string $code       steht so in nexus_wissen_artikel.thema
 * @property string $name       Text oder JSON {"de": ..., "en": ..., "es": ...}
 * @property string $symbol     Font-Awesome-Name
 * @property int $sortierung
 */
class Thema extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_wissen_thema';
    }

    /** @return string|array<string, string> so, wie Texte::waehle() es versteht */
    public function namen()
    {
        $wert = json_decode((string)$this->name, true);
        return is_array($wert) ? $wert : (string)$this->name;
    }

    public function anzeigeName(?string $sprache = null): string
    {
        return Texte::waehle($this->namen(), $sprache);
    }

    /** Umbenennen in einer Sprache -- die anderen Sprachen bleiben, wie sie sind. */
    public function setzeName(string $text, string $sprache): void
    {
        $namen = $this->namen();
        $namen = is_array($namen) ? $namen : ($namen !== '' ? [$sprache => $namen] : []);
        $namen[$sprache] = $text;
        $this->name = json_encode($namen, JSON_UNESCAPED_UNICODE);
    }
}
