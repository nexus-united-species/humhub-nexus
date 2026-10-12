<?php

namespace nexus\modules\folgen;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\Follow;
use humhub\modules\user\models\User;
use Throwable;
use Yii;

/**
 * Neue Konten folgen automatisch ausgewaehlten Kreisen (12.10.2026).
 *
 * Warum: Die Startseite ("Ueberblick") zeigt bei HumHub nur Beitraege aus Kreisen, in denen man
 * Mitglied ist oder denen man folgt. Die Arbeitskreise sind fuer alle Angemeldeten lesbar, aber ohne
 * Folgen bekam niemand mit, was dort passiert. Folgen heisst: Beitraege auf der Startseite sehen,
 * schreiben und kommentieren erst als Mitglied.
 *
 * - Ohne Benachrichtigungen (Glocke/E-Mail) -- nur die Startseite.
 * - Mitglieder eines Kreises werden uebersprungen (HumHub laesst Mitglieder nicht zusaetzlich folgen).
 * - Wer schon folgt, behaelt seine Einstellung (z. B. Benachrichtigungen an).
 * - Jeder kann im Kreis jederzeit "Nicht mehr folgen" klicken; fuer bestehende Konten wird das nicht
 *   rueckgaengig gemacht, solange niemand "php yii folgen/alle" erneut ausfuehrt.
 */
class Module extends \humhub\components\Module
{
    /*
     * Einstellungen / settings: protected/config/common.php
     *   'modules' => ['nexus-folgen' => ['kreise' => [5, 6, 7]]]   // Space-IDs
     */

    /** @var int[] Space-IDs, denen neue Konten folgen */
    public $kreise = [];

    /** @var int[] Konten, die nie automatisch folgen (z. B. Bot-Konten) */
    public $ausnahmen = [];

    public static function instanz(): ?self
    {
        $modul = Yii::$app->getModule('nexus-folgen');
        return $modul instanceof self ? $modul : null;
    }

    public function getName()
    {
        return 'N.E.X.U.S. Follow Spaces';
    }

    public function getDescription()
    {
        return 'New accounts automatically follow selected spaces.';
    }

    public static function onNeuesKonto($event): void
    {
        $modul = self::instanz();
        if ($modul === null || !$event->sender instanceof User) {
            return;
        }
        try {
            $modul->folgenLassen($event->sender);
        } catch (Throwable $e) {
            Yii::error('nexus-folgen: ' . $e->getMessage(), 'nexus-folgen');
        }
    }

    /** @return int Anzahl neu angelegter Folgen */
    public function folgenLassen(User $mensch, bool $probe = false): int
    {
        if (in_array((int)$mensch->id, array_map('intval', (array)$this->ausnahmen), true)) {
            return 0;
        }
        $neu = 0;
        foreach (array_map('intval', (array)$this->kreise) as $id) {
            $kreis = Space::findOne(['id' => $id, 'status' => Space::STATUS_ENABLED]);
            if ($kreis === null || $kreis->isMember($mensch->id)) {
                continue;
            }
            $folgt = Follow::find()->where(['user_id' => $mensch->id, 'object_model' => Space::class, 'object_id' => $id])->exists();
            if (!$folgt && ($probe || $kreis->follow($mensch->id, false))) {
                $neu++;
            }
        }
        return $neu;
    }
}
