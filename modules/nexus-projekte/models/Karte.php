<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Eine Kachel = ein Arbeitsschritt im Projekt.
 *
 * @property int $id
 * @property int $projekt_id
 * @property string $titel
 * @property string|null $beschreibung
 * @property string $spalte          offen | arbeit | abstimmung | fertig
 * @property int|null $verantwortlich_id
 * @property string|null $frist
 * @property string|null $abstimmung_bis
 * @property int $runde
 * @property string|null $links       JSON
 * @property int $sortierung
 * @property string|null $erinnert_frist
 * @property string|null $erinnert_ueberfaellig
 * @property int $erinnert_runde
 * @property int $erinnert_auswertung  Runde, fuer die die Leitung ans Auswerten erinnert wurde
 * @property string $created_at
 * @property int|null $created_by
 * @property string $updated_at
 * @property string $art              aufgabe | vorschlag | auswahl
 * @property string|null $zusammenfassung
 * @property string|null $begruendung
 * @property string|null $ergebnis     angenommen | abgelehnt | zurueckgezogen
 * @property string|null $ergebnis_text
 * @property string|null $ergebnis_am
 * @property string|null $weitergabe_status  vorgelegt | beschlossen | abgelehnt
 * @property string|null $weitergabe_am
 * @property string|null $umgesetzt
 * @property string|null $umgesetzt_am
 *
 * @property-read Projekt $projekt
 * @property-read Aenderung[] $aenderungen
 * @property-read User|null $verantwortlich
 * @property-read Schritt[] $schritte
 */
class Karte extends ActiveRecord
{
    public const SPALTEN = ['offen', 'arbeit', 'abstimmung', 'fertig'];
    public const HELFER_TABELLE = 'nexus_projekt_helfer';
    public const MAX_LINKS = 8;
    public const ARTEN = ['aufgabe', 'vorschlag', 'auswahl'];
    public const ERGEBNISSE = ['angenommen', 'abgelehnt', 'zurueckgezogen'];
    public const WEITERGABE = ['vorgelegt', 'beschlossen', 'abgelehnt'];

    public static function tableName()
    {
        return 'nexus_projekt_karte';
    }

    public function rules()
    {
        return [
            [['titel', 'projekt_id'], 'required'],
            ['titel', 'string', 'max' => 190],
            ['beschreibung', 'string', 'max' => 10000],
            ['spalte', 'in', 'range' => self::SPALTEN],
            [['frist', 'abstimmung_bis'], 'date', 'format' => 'php:Y-m-d'],
            ['verantwortlich_id', 'integer'],
            ['art', 'in', 'range' => self::ARTEN],
            [['zusammenfassung', 'begruendung'], 'string', 'max' => 10000],
            ['zusammenfassung', 'required', 'when' => fn(self $k) => $k->istVorschlag()],
            ['ergebnis', 'in', 'range' => self::ERGEBNISSE],
            ['weitergabe_status', 'in', 'range' => self::WEITERGABE],
            ['umgesetzt', 'string', 'max' => 190],
            [['weitergabe_am', 'umgesetzt_am'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function beforeSave($insert)
    {
        $jetzt = date('Y-m-d H:i:s');
        if ($insert && empty($this->created_at)) {
            $this->created_at = $jetzt;
        }
        $this->updated_at = $jetzt;
        return parent::beforeSave($insert);
    }

    public function getProjekt()
    {
        return $this->hasOne(Projekt::class, ['id' => 'projekt_id']);
    }

    public function getVerantwortlich()
    {
        return $this->hasOne(User::class, ['id' => 'verantwortlich_id']);
    }

    public function getSchritte()
    {
        return $this->hasMany(Schritt::class, ['karte_id' => 'id'])->orderBy(['sortierung' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function getAenderungen()
    {
        return $this->hasMany(Aenderung::class, ['karte_id' => 'id'])->orderBy(['sortierung' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function istVorschlag(): bool
    {
        return $this->art === 'vorschlag';
    }

    /** Bild-Auswahl: mehrere Entwuerfe, Abstimmung im Konsent je Entwurf (services/Auswahl.php) */
    public function istAuswahl(): bool
    {
        return $this->art === 'auswahl';
    }

    /** @return User[] */
    public function helfer(): array
    {
        $ids = (new \yii\db\Query())->select('user_id')->from(self::HELFER_TABELLE)->where(['karte_id' => $this->id])->column();
        return $ids === [] ? [] : User::find()->where(['id' => $ids])->orderBy('id')->all();
    }

    /** @param int[] $userIds */
    public function setzeHelfer(array $userIds): void
    {
        $db = static::getDb();
        $db->createCommand()->delete(self::HELFER_TABELLE, ['karte_id' => $this->id])->execute();
        foreach (array_unique(array_map('intval', $userIds)) as $id) {
            if ($id > 0 && $id !== (int)$this->verantwortlich_id) {
                $db->createCommand()->insert(self::HELFER_TABELLE, ['karte_id' => $this->id, 'user_id' => $id])->execute();
            }
        }
    }

    public function istHelfer(int $userId): bool
    {
        return (new \yii\db\Query())->from(self::HELFER_TABELLE)->where(['karte_id' => $this->id, 'user_id' => $userId])->exists();
    }

    /** @return array<int, array{titel: string, url: string}> */
    public function linkListe(): array
    {
        $liste = json_decode((string)$this->links, true);
        return is_array($liste) ? array_values(array_filter($liste, fn($l) => is_array($l) && isset($l['url']))) : [];
    }

    /**
     * Nur Links, die man gefahrlos anzeigen kann: http(s) oder Pfade im Portal.
     * @param array<int, array{titel?: string, url?: string}> $liste
     */
    public function setzeLinks(array $liste): void
    {
        $sauber = [];
        foreach ($liste as $l) {
            $url = trim((string)($l['url'] ?? ''));
            if ($url === '' || !preg_match('#^(https?://|/)#i', $url) || str_starts_with($url, '//')) {
                continue;
            }
            $titel = trim((string)($l['titel'] ?? '')) ?: $url;
            $sauber[] = ['titel' => mb_substr($titel, 0, 120), 'url' => mb_substr($url, 0, 1000)];
        }
        $this->links = $sauber === [] ? null : json_encode(array_slice($sauber, 0, self::MAX_LINKS), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return Stimme[] Stimmen der aktuellen Runde zur ganzen Kachel (ohne Stimmen zu einzelnen Entwuerfen) */
    public function stimmen(): array
    {
        return Stimme::find()->where(['karte_id' => $this->id, 'runde' => $this->runde, 'option_id' => 0])->with('autor')->orderBy('id')->all();
    }

    /**
     * Rueckmeldungen aus abgeschlossenen Runden, neueste Runde zuerst: Runde => Stimmen. Sie bleiben sichtbar,
     * solange man an der Kachel weiterarbeitet (Rueckmeldung eines Mitglieds, 26.10.2026: nach "Zurueck in Arbeit"
     * waren die Kommentare weg -- die Stimmen zaehlen je Runde, die Anzeige kannte aber nur die laufende).
     * In "In Abstimmung" zaehlt die laufende Runde nicht dazu (sie steht ohnehin im Abstimmungskasten);
     * in allen anderen Spalten ist die zuletzt gelaufene Runde die juengste Rueckmeldung.
     *
     * @return array<int, Stimme[]>
     */
    public function fruehereStimmen(): array
    {
        $bis = $this->spalte === 'abstimmung' ? (int)$this->runde - 1 : (int)$this->runde;
        if ($bis < 1) {
            return [];
        }
        $je = [];
        foreach (Stimme::find()->where(['karte_id' => $this->id, 'option_id' => 0])->andWhere(['<=', 'runde', $bis])
            ->with('autor')->orderBy(['runde' => SORT_DESC, 'id' => SORT_ASC])->all() as $s) {
            $je[(int)$s->runde][] = $s;
        }
        return $je;
    }

    public function istUeberfaellig(): bool
    {
        return $this->frist !== null && $this->spalte !== 'fertig' && $this->frist < date('Y-m-d');
    }

    public function url(bool $absolut = false): string
    {
        return $this->projekt->url('ansicht', ['k' => $this->id], $absolut) . '#karte';
    }
}
