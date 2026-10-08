<?php

namespace nexus\modules\teilen\models;

use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * Ein oeffentlich lesbarer Inhalt.
 *
 * @property int $content_id
 * @property string $schluessel
 * @property int $created_by
 * @property string $created_at
 * @property int $schaufenster       1 = steht auf der oeffentlichen Seite "Aus der N.E.X.U.S.-Gemeinschaft"
 * @property string|null $schaufenster_at
 * @property int|null $schaufenster_von
 */
class Freigabe extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_teilen_freigabe';
    }

    public static function fuer(int $contentId): ?self
    {
        return self::findOne(['content_id' => $contentId]);
    }

    public static function anlegen(int $contentId, int $userId): self
    {
        $freigabe = self::fuer($contentId);
        if ($freigabe !== null) {
            return $freigabe;
        }
        $freigabe = new self([
            'content_id' => $contentId,
            // 128 Bit Zufall, als Hex -- nicht zu erraten, nicht zu erzaehlen.
            'schluessel' => bin2hex(random_bytes(16)),
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $freigabe->save(false);
        return $freigabe;
    }

    public function imSchaufenster(): bool
    {
        return (int)$this->schaufenster === 1;
    }

    public function insSchaufenster(int $userId): void
    {
        $this->updateAttributes(['schaufenster' => 1, 'schaufenster_at' => date('Y-m-d H:i:s'), 'schaufenster_von' => $userId]);
    }

    public function ausSchaufenster(): void
    {
        $this->updateAttributes(['schaufenster' => 0]);
    }

    /**
     * Inhalte im Schaufenster, neueste zuerst -- nur veroeffentlichte (nichts aus dem Papierkorb,
     * keine Entwuerfe).
     *
     * @return self[]
     */
    public static function schaufenster(int $anzahl, int $ab = 0): array
    {
        return self::find()
            ->alias('f')
            ->innerJoin('content c', 'c.id = f.content_id AND c.state = 1')
            ->where(['f.schaufenster' => 1])
            ->orderBy(['f.schaufenster_at' => SORT_DESC])
            ->offset($ab)
            ->limit($anzahl)
            ->all();
    }

    /** Adresse der Leseseite; mit Sprache, damit der Empfaenger dieselbe Fassung sieht wie der Teilende. */
    public function adresse(?string $sprache = null): string
    {
        $ziel = ['/nexus-teilen/lesen/index', 't' => $this->schluessel];
        if ($sprache !== null) {
            $ziel['sprache'] = $sprache;
        }
        return Url::to($ziel, true);
    }
}
