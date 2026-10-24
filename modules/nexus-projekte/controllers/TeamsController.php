<?php

namespace nexus\modules\projekte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\assets\ProjekteAsset;
use nexus\modules\projekte\models\Team;
use nexus\modules\projekte\Module;
use nexus\modules\projekte\services\Texte;
use Yii;

/**
 * "Teams – mach mit": alle Teams, die Mitwirkende suchen oder arbeiten, aus den Kreisen, die man sehen darf
 * (Mitgliedschaft oder fuer alle Angemeldeten sichtbar). Private Kreise ohne Mitgliedschaft bleiben aussen vor.
 */
class TeamsController extends Controller
{
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY]];
    }

    public function actionIndex()
    {
        /** @var User $ich */
        $ich = Yii::$app->user->getIdentity();
        ProjekteAsset::register($this->view);
        $this->view->title = Texte::t('teams_mach_mit');
        $liste = [];
        foreach (Team::find()->where(['status' => ['suchend', 'aktiv']])->with(['kreis', 'aufgaben'])
                     ->orderBy([new \yii\db\Expression("FIELD(status, 'suchend', 'aktiv')"), 'titel' => SORT_ASC])->all() as $team) {
            $kreis = $team->kreis;
            if ($kreis === null || !Module::istAn($kreis)) {
                continue;
            }
            $mitglied = $kreis->isMember($ich->id);
            if (!$mitglied && (int)$kreis->visibility === Space::VISIBILITY_NONE && !$ich->isSystemAdmin()) {
                continue;
            }
            $liste[] = ['team' => $team, 'mitglied' => $mitglied];
        }
        return $this->render('index', ['liste' => $liste]);
    }
}
