<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\services\Entscheidung;

/**
 * @property int $id
 * @property int $space_id
 * @property string $titel
 * @property string|null $ziel
 * @property string|null $frist
 * @property string $status      aktiv | abgeschlossen
 * @property string $created_at
 * @property int|null $created_by
 * @property string $updated_at
 * @property string $regel       einfach | konsent | mehrheit -- wie ueber Vorschlaege entschieden wird
 * @property int $frist_tage     so lange laeuft eine Vorschlagsrunde
 * @property string|null $weitergabe  Gremium, dem angenommene Vorschlaege vorgelegt werden (z. B. "DAO")
 *
 * @property-read Space $kreis
 * @property-read Karte[] $karten
 */
class Projekt extends ActiveRecord
{
    public const STATUS = ['aktiv', 'abgeschlossen'];
    public const MITMACHER_TABELLE = 'nexus_projekt_mitmacher';
    public const LEITUNG_TABELLE = 'nexus_projekt_leitung';
    public const MAX_LEITUNG = 5;
    public const MAX_FRIST_TAGE = 60;

    public static function tableName()
    {
        return 'nexus_projekt';
    }

    public function rules()
    {
        return [
            [['titel', 'space_id'], 'required'],
            ['titel', 'string', 'max' => 190],
            ['ziel', 'string', 'max' => 5000],
            ['frist', 'date', 'format' => 'php:Y-m-d'],
            ['status', 'in', 'range' => self::STATUS],
            ['regel', 'in', 'range' => Entscheidung::REGELN],
            ['frist_tage', 'integer', 'min' => 1, 'max' => self::MAX_FRIST_TAGE],
            ['weitergabe', 'string', 'max' => 100],
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

    public function getKreis()
    {
        return $this->hasOne(Space::class, ['id' => 'space_id']);
    }

    /** @return int[] */
    public function leitungIds(): array
    {
        return array_map('intval', (new \yii\db\Query())->select('user_id')->from(self::LEITUNG_TABELLE)
            ->where(['projekt_id' => $this->id])->orderBy('user_id')->column());
    }

    /** @return User[] */
    public function leitungen(): array
    {
        $ids = $this->leitungIds();
        return $ids === [] ? [] : User::find()->where(['id' => $ids])->orderBy('id')->all();
    }

    public function istLeitung(int $userId): bool
    {
        return in_array($userId, $this->leitungIds(), true);
    }

    /** @param int[] $userIds */
    public function setzeLeitung(array $userIds): void
    {
        $db = static::getDb();
        $db->createCommand()->delete(self::LEITUNG_TABELLE, ['projekt_id' => $this->id])->execute();
        foreach (array_slice(array_unique(array_filter(array_map('intval', $userIds))), 0, self::MAX_LEITUNG) as $id) {
            $db->createCommand()->insert(self::LEITUNG_TABELLE, ['projekt_id' => $this->id, 'user_id' => $id])->execute();
        }
    }

    public function leitungNamen(): string
    {
        return implode(', ', array_map(fn(User $u) => $u->displayName, $this->leitungen())) ?: '–';
    }

    public function getKarten()
    {
        return $this->hasMany(Karte::class, ['projekt_id' => 'id'])->orderBy(['sortierung' => SORT_ASC, 'id' => SORT_ASC]);
    }

    /** @return int[] wer "Ich mache mit" geklickt hat */
    public function mitmacherIds(): array
    {
        return array_map('intval', (new \yii\db\Query())->select('user_id')->from(self::MITMACHER_TABELLE)
            ->where(['projekt_id' => $this->id])->column());
    }

    public function setzeMitmacher(int $userId, bool $dabei): void
    {
        $db = static::getDb();
        $db->createCommand()->delete(self::MITMACHER_TABELLE, ['projekt_id' => $this->id, 'user_id' => $userId])->execute();
        if ($dabei) {
            $db->createCommand()->insert(self::MITMACHER_TABELLE, ['projekt_id' => $this->id, 'user_id' => $userId])->execute();
        }
    }

    /**
     * Alle Beteiligten: Leitung, Verantwortliche, Mithelfende und wer "Ich mache mit" geklickt hat.
     * Sie werden an laufende Abstimmungen erinnert.
     * @return int[]
     */
    public function beteiligteIds(): array
    {
        $kartenIds = Karte::find()->select('id')->where(['projekt_id' => $this->id])->column();
        $ids = array_merge(
            $this->leitungIds(),
            array_map('intval', Karte::find()->select('verantwortlich_id')->where(['projekt_id' => $this->id])->andWhere(['not', ['verantwortlich_id' => null]])->column()),
            $kartenIds === [] ? [] : array_map('intval', (new \yii\db\Query())->select('user_id')->from(Karte::HELFER_TABELLE)->where(['karte_id' => $kartenIds])->column()),
            $this->mitmacherIds(),
        );
        return array_values(array_unique(array_filter($ids)));
    }

    /** @return array{gesamt: int, fertig: int} */
    public function fortschritt(): array
    {
        // column() liefert die ERSTE Spalte der Auswahl -- deshalb die Anzahl zuerst
        $zahlen = Karte::find()->select(['n' => 'COUNT(*)', 'spalte'])->where(['projekt_id' => $this->id])->groupBy('spalte')->indexBy('spalte')->column();
        return ['gesamt' => (int)array_sum($zahlen), 'fertig' => (int)($zahlen['fertig'] ?? 0)];
    }

    public function url(string $seite = 'ansicht', array $parameter = [], bool $absolut = false): string
    {
        $pfad = $this->kreis->createUrl('/nexus-projekte/projekt/' . $seite, ['p' => $this->id] + $parameter);
        return $absolut ? self::voll($pfad) : $pfad;
    }

    /** Volle Adresse auch ohne Browser-Anfrage (Cron): aus der HumHub-Einstellung baseUrl */
    public static function voll(string $pfad): string
    {
        if (preg_match('#^https?://#', $pfad)) {
            return $pfad;
        }
        $pfad = (string)preg_replace('#^.*?(/(?:s|u)/)#', '$1', $pfad);
        return rtrim((string)\Yii::$app->settings->get('baseUrl'), '/') . '/' . ltrim($pfad, '/');
    }
}
