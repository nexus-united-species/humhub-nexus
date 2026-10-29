<?php

namespace nexus\modules\team;

use humhub\modules\user\models\Group;
use humhub\modules\user\models\GroupUser;
use humhub\modules\user\models\User;
use Yii;

/**
 * Community-Team (Josh, 29.10.2026: "Ein Mitglied moechte mich mit dem Communitymanagement unterstuetzen --
 * Moderation, Mitglieder willkommen heissen -- so, dass es ihr leicht faellt und sie einen guten Ueberblick hat").
 *
 * Wer zum Team gehoert, bestimmt eine HumHub-Gruppe (Einstellung "gruppe", Vorgabe "Community-Team") plus alle
 * Systemadmins. Die Gruppe vergibt auch die Moderationsrechte (siehe Server_Dokumentation.md); dieses Modul
 * liefert nur die Uebersichtsseite. Es veraendert nichts an Inhalten.
 */
class Module extends \humhub\components\Module
{
    public function getName()
    {
        return 'N.E.X.U.S. Community-Team';
    }

    public function getDescription()
    {
        return 'Ueberblick fuer das Community-Team: neue Mitglieder, Meldungen, Beitraege ohne Antwort, Support, ruhige Kreise.';
    }

    public static function instanz(): ?self
    {
        $m = Yii::$app->getModule('nexus-team');
        return $m instanceof self ? $m : null;
    }

    public function gruppenName(): string
    {
        return trim((string)$this->settings->get('gruppe', 'Community-Team')) ?: 'Community-Team';
    }

    public function istTeam(?User $mensch): bool
    {
        if ($mensch === null) {
            return false;
        }
        if ($mensch->isSystemAdmin()) {
            return true;
        }
        $gruppe = Group::findOne(['name' => $this->gruppenName()]);
        return $gruppe !== null && GroupUser::find()->where(['group_id' => $gruppe->id, 'user_id' => $mensch->id])->exists();
    }

    /** @return User[] Aktive Mitglieder der Team-Gruppe (ohne die Systemadmins) */
    public function teamMenschen(): array
    {
        $gruppe = Group::findOne(['name' => $this->gruppenName()]);
        if ($gruppe === null) {
            return [];
        }
        $ids = GroupUser::find()->select('user_id')->where(['group_id' => $gruppe->id])->column();
        return $ids === [] ? [] : User::find()->where(['id' => $ids, 'status' => User::STATUS_ENABLED])->all();
    }
}
