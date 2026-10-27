<?php

namespace nexus\modules\finanzen\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Eine Kostenkachel. Betrag in Cent: laufend = je Monat, einmalig = einmal fuer den beschriebenen Schritt.
 * "gruppe" fasst Kacheln nur fuer die Anzeige zusammen (z. B. "Dienste und Werkzeuge") -- eine Gruppe ist
 * selbst keine Kachel und wird nie zusaetzlich gezaehlt.
 *
 * @property int $id
 * @property string $bereich grund|optional|entwicklung|koordination
 * @property string|null $gruppe
 * @property string $titel
 * @property string|null $beschreibung
 * @property string $art laufend|einmalig
 * @property int $betrag
 * @property string $einordnung bestaetigt|geschaetzt|planung|rueckstellung|reserve
 * @property bool $freigegeben false = grau ("geplant / zu pruefen")
 * @property string|null $zusatz in_umsetzung|bezahlt|abgeschlossen|pausiert
 * @property int $rang Reihenfolge der Verteilung (klein = zuerst)
 * @property bool $oeffentlich
 * @property int|null $beguenstigt_id wer aus dieser Kachel verguetet wird (Vier-Augen-Pflicht)
 */
class Kachel extends ActiveRecord
{
    /** Reihenfolge = Reihenfolge der Bereiche auf der Seite */
    public const BEREICHE = ['grund', 'optional', 'entwicklung', 'koordination'];
    public const ARTEN = ['laufend', 'einmalig'];
    public const EINORDNUNGEN = ['bestaetigt', 'geschaetzt', 'planung', 'rueckstellung', 'reserve'];
    public const ZUSAETZE = ['in_umsetzung', 'bezahlt', 'abgeschlossen', 'pausiert'];

    public static function tableName()
    {
        return 'nexus_fin_kachel';
    }

    public function rules()
    {
        return [
            [['titel', 'bereich', 'art', 'einordnung'], 'required'],
            ['titel', 'string', 'max' => 120],
            ['gruppe', 'string', 'max' => 80],
            ['beschreibung', 'string', 'max' => 5000],
            ['bereich', 'in', 'range' => self::BEREICHE],
            ['art', 'in', 'range' => self::ARTEN],
            ['einordnung', 'in', 'range' => self::EINORDNUNGEN],
            ['zusatz', 'in', 'range' => self::ZUSAETZE, 'skipOnEmpty' => true],
            [['betrag', 'rang'], 'integer', 'min' => 0],
            [['freigegeben', 'oeffentlich'], 'boolean'],
            ['beguenstigt_id', 'integer'],
        ];
    }

    public function beforeSave($insert)
    {
        $jetzt = date('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $jetzt;
        }
        $this->updated_at = $jetzt;
        if ($this->gruppe !== null && trim($this->gruppe) === '') {
            $this->gruppe = null;
        }
        if ($this->zusatz === '') {
            $this->zusatz = null;
        }
        return parent::beforeSave($insert);
    }

    public function getBeguenstigt()
    {
        return $this->hasOne(User::class, ['id' => 'beguenstigt_id']);
    }

    public function istLaufend(): bool
    {
        return $this->art === 'laufend';
    }
}
