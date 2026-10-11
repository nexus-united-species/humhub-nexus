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
 * @property string $created_at
 * @property int|null $created_by
 * @property string $updated_at
 *
 * @property-read Projekt $projekt
 * @property-read User|null $verantwortlich
 * @property-read Schritt[] $schritte
 */
class Karte extends ActiveRecord
{
    public const SPALTEN = ['offen', 'arbeit', 'abstimmung', 'fertig'];
    public const HELFER_TABELLE = 'nexus_projekt_helfer';
    public const MAX_LINKS = 8;

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

    /** @return Stimme[] Stimmen der aktuellen Runde */
    public function stimmen(): array
    {
        return Stimme::find()->where(['karte_id' => $this->id, 'runde' => $this->runde])->with('autor')->orderBy('id')->all();
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
