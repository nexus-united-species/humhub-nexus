<?php

namespace nexus\modules\projekte\models;

use humhub\components\ActiveRecord;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;

/**
 * @property int $id
 * @property int $space_id
 * @property string $titel
 * @property string|null $ziel
 * @property int|null $leitung_id
 * @property string|null $frist
 * @property string $status      aktiv | abgeschlossen
 * @property string $created_at
 * @property int|null $created_by
 * @property string $updated_at
 *
 * @property-read Space $kreis
 * @property-read User|null $leitung
 * @property-read Karte[] $karten
 */
class Projekt extends ActiveRecord
{
    public const STATUS = ['aktiv', 'abgeschlossen'];
    public const MITMACHER_TABELLE = 'nexus_projekt_mitmacher';

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
            ['leitung_id', 'integer'],
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

    public function getLeitung()
    {
        return $this->hasOne(User::class, ['id' => 'leitung_id']);
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
            [(int)$this->leitung_id],
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
