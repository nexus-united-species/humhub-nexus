<?php

namespace nexus\modules\projekte\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\assets\ProjekteAsset;
use nexus\modules\projekte\models\Team;
use nexus\modules\projekte\models\TeamAufgabe;
use nexus\modules\projekte\models\TeamMitglied;
use nexus\modules\projekte\models\TeamPost;
use nexus\modules\projekte\Module;
use nexus\modules\projekte\services\Teams;
use nexus\modules\projekte\services\Texte;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Teams im Kreis (services/Teams.php): ausschreiben, mitmachen, aufnehmen, Aufgaben und Vertretung,
 * Arbeitsweise, Abwesenheit, Team-Postfach. Jede Aktion prueft, dass das Team zu diesem Kreis gehoert.
 */
class TeamController extends ContentContainerController
{
    public const FLASH = 'nexus-team';

    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_POST => ['speichern', 'loeschen', 'mitmachen', 'entscheiden', 'aufgabe', 'erledigt', 'arbeitsweise',
                'koordination', 'status', 'weg', 'aufhoeren', 'schreiben', 'uebergeben', 'post', 'nova']],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!$this->contentContainer instanceof Space || !Module::istAn($this->contentContainer)) {
            throw new NotFoundHttpException();
        }
        ProjekteAsset::register($this->view);
        return true;
    }

    // ── Uebersicht und Ausschreiben ─────────────────────────────────────────

    public function actionIndex()
    {
        $kreis = $this->kreis();
        return $this->render('index', [
            'kreis' => $kreis,
            'teams' => Team::find()->where(['space_id' => $kreis->id])->with('aufgaben')
                ->orderBy([new \yii\db\Expression("FIELD(status, 'suchend', 'aktiv', 'pausiert')"), 'titel' => SORT_ASC])->all(),
            'darfAusschreiben' => Teams::darfAusschreiben($kreis, $this->ich()),
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
        ]);
    }

    public function actionFormular(int $t = 0)
    {
        $team = $t > 0 ? $this->team($t) : new Team(['space_id' => $this->kreis()->id, 'status' => 'suchend']);
        $this->pruefe($team->isNewRecord ? Teams::darfAusschreiben($this->kreis(), $this->ich()) : Teams::darfLeiten($team, $this->ich()));
        return $this->render('formular', ['kreis' => $this->kreis(), 'team' => $team, 'fehler' => null]);
    }

    public function actionSpeichern(int $t = 0)
    {
        $team = $t > 0 ? $this->team($t) : new Team(['space_id' => $this->kreis()->id, 'status' => 'suchend']);
        $this->pruefe($team->isNewRecord ? Teams::darfAusschreiben($this->kreis(), $this->ich()) : Teams::darfLeiten($team, $this->ich()));
        $r = Yii::$app->request;
        $team->titel = trim((string)$r->post('titel'));
        $team->auftrag = trim((string)$r->post('auftrag')) ?: null;
        if (!$team->isNewRecord && in_array($r->post('status'), Team::STATUS, true)) {
            $team->status = (string)$r->post('status');
        }
        try {
            Teams::speichern($team, (array)$r->post('aufgaben', []), $this->ich());
        } catch (\Throwable $e) {
            return $this->render('formular', ['kreis' => $this->kreis(), 'team' => $team, 'fehler' => $e->getMessage()]);
        }
        return $this->zurTeamseite($team, Texte::t('gespeichert'));
    }

    public function actionLoeschen(int $t)
    {
        $team = $this->team($t);
        $this->pruefe(Teams::darfAusschreiben($this->kreis(), $this->ich()));
        $team->delete();
        Yii::$app->session->setFlash(self::FLASH, Texte::t('team_geloescht'));
        return $this->redirect($this->kreis()->createUrl('/nexus-projekte/team/index'));
    }

    // ── Teamseite ───────────────────────────────────────────────────────────

    public function actionAnsicht(int $t)
    {
        $team = $this->team($t);
        $ich = $this->ich();
        $darfPostfach = Teams::darfPostfach($team, $ich);
        return $this->render('ansicht', [
            'kreis' => $this->kreis(),
            'team' => $team,
            'ich' => $ich,
            'meine' => $team->mitgliedschaft((int)$ich->id),
            'mitglieder' => $team->mitglieder(),
            'anfragen' => Teams::darfLeiten($team, $ich) ? $team->mitglieder('angefragt') : [],
            'posts' => $darfPostfach ? TeamPost::find()->where(['team_id' => $team->id])
                ->orderBy([new \yii\db\Expression("FIELD(status, 'kreisleitung', 'offen', 'uebernommen', 'erledigt')"), 'updated_at' => SORT_DESC])
                ->limit(60)->all() : [],
            'meldung' => Yii::$app->session->getFlash(self::FLASH),
        ]);
    }

    public function actionMitmachen(int $t)
    {
        $team = $this->team($t);
        $this->pruefe(Teams::darfMitmachen($team, $this->ich()));
        $r = Yii::$app->request;
        Teams::mitmachen($team, $this->ich(), (array)$r->post('aufgaben', []), (string)$r->post('zeit'), (string)$r->post('nachricht'));
        return $this->zurTeamseite($team, Texte::t('team_angefragt_ok'));
    }

    public function actionEntscheiden(int $t, int $m)
    {
        $team = $this->team($t);
        $this->pruefe(Teams::darfLeiten($team, $this->ich()));
        $mitglied = TeamMitglied::findOne(['id' => $m, 'team_id' => $team->id, 'status' => 'angefragt']) ?? throw new NotFoundHttpException();
        $aufnehmen = (string)Yii::$app->request->post('aufnehmen') === '1';
        Teams::entscheiden($team, $mitglied, $aufnehmen, $this->ich());
        return $this->zurTeamseite($team, Texte::t($aufnehmen ? 'team_aufgenommen' : 'team_abgesagt'));
    }

    /** Selbst eintragen (zustaendig/Vertretung) oder austragen; die Leitung darf auch austragen */
    public function actionAufgabe(int $t, int $a)
    {
        $team = $this->team($t);
        $aufgabe = $this->aufgabe($team, $a);
        $ich = $this->ich();
        $rolle = Yii::$app->request->post('rolle') === 'vertretung' ? 'vertretung' : 'zustaendig';
        $an = (string)Yii::$app->request->post('an') === '1';
        $spalte = $rolle === 'vertretung' ? 'vertretung_id' : 'zustaendig_id';
        if ($an) {
            $this->pruefe(Teams::istMitglied($team, $ich) && $aufgabe->$spalte === null);
            Teams::aufgabeEintragen($aufgabe, $rolle, (int)$ich->id);
        } else {
            $this->pruefe((int)$aufgabe->$spalte === (int)$ich->id || Teams::darfLeiten($team, $ich));
            Teams::aufgabeEintragen($aufgabe, $rolle, null);
        }
        return $this->zurTeamseite($team, null);
    }

    public function actionErledigt(int $t, int $a)
    {
        $team = $this->team($t);
        $aufgabe = $this->aufgabe($team, $a);
        $this->pruefe(Teams::darfPostfach($team, $this->ich()) && $aufgabe->art === 'einmalig');
        $aufgabe->updateAttributes(['erledigt' => $aufgabe->erledigt ? 0 : 1]);
        return $this->zurTeamseite($team, null);
    }

    public function actionArbeitsweise(int $t)
    {
        $team = $this->team($t);
        $this->pruefe(Teams::darfPostfach($team, $this->ich()));
        $team->arbeitsweise = mb_substr(trim((string)Yii::$app->request->post('arbeitsweise')), 0, 10000) ?: null;
        $team->save(false);
        return $this->zurTeamseite($team, Texte::t('gespeichert'));
    }

    public function actionKoordination(int $t)
    {
        $team = $this->team($t);
        $this->pruefe(Teams::darfLeiten($team, $this->ich()));
        $id = (int)Yii::$app->request->post('user');
        $team->koordination_id = in_array($id, $team->mitgliedIds(), true) ? $id : null;
        $team->save(false);
        return $this->zurTeamseite($team, Texte::t('gespeichert'));
    }

    public function actionStatus(int $t)
    {
        $team = $this->team($t);
        $this->pruefe(Teams::darfLeiten($team, $this->ich()));
        $status = (string)Yii::$app->request->post('status');
        if (in_array($status, Team::STATUS, true)) {
            $team->status = $status;
            $team->save(false);
        }
        return $this->zurTeamseite($team, Texte::t('gespeichert'));
    }

    public function actionWeg(int $t)
    {
        $team = $this->team($t);
        $m = $this->meineMitgliedschaft($team);
        $bis = trim((string)Yii::$app->request->post('bis'));
        $bis = preg_match('/^\d{4}-\d{2}-\d{2}$/', $bis) && $bis >= date('Y-m-d') ? $bis : null;
        Teams::weg($team, $m, $bis);
        return $this->zurTeamseite($team, Texte::t($bis ? 'team_weg_ok' : 'team_zurueck_ok'));
    }

    public function actionAufhoeren(int $t)
    {
        $team = $this->team($t);
        Teams::aufhoeren($team, $this->meineMitgliedschaft($team));
        return $this->zurTeamseite($team, Texte::t('team_aufgehoert'));
    }

    // ── Team-Postfach ───────────────────────────────────────────────────────

    public function actionSchreiben(int $t)
    {
        $team = $this->team($t);
        $this->pruefe(Teams::darfAnschreiben($team, $this->ich()));
        $r = Yii::$app->request;
        $betreff = trim((string)$r->post('betreff'));
        $text = trim((string)$r->post('text'));
        if ($betreff === '' || mb_strlen($text) < 5) {
            return $this->zurTeamseite($team, Texte::t('team_nachricht_leer'));
        }
        Teams::anTeamSchreiben($team, $this->ich(), $betreff, $text);
        return $this->zurTeamseite($team, Texte::t('team_nachricht_ok'));
    }

    /** GET: eigene Unterhaltungen zur Auswahl; POST: die gewaehlte an das Team uebergeben */
    public function actionUebergeben(int $t)
    {
        $team = $this->team($t);
        $ich = $this->ich();
        $this->pruefe(Teams::darfPostfach($team, $ich) && Teams::mailDa());
        if (Yii::$app->request->isPost) {
            $r = Yii::$app->request;
            try {
                Teams::uebergeben($team, (int)$r->post('mid'), $ich, (string)$r->post('notiz'), (string)$r->post('verlassen') === '1');
            } catch (\RuntimeException $e) {
                throw new ForbiddenHttpException($e->getMessage());
            }
            return $this->zurTeamseite($team, Texte::t('team_uebergeben_ok'));
        }
        $schon = array_map('intval', TeamPost::find()->select('message_id')->where(['team_id' => $team->id])->column());
        $unterhaltungen = (new \yii\db\Query())->select(['m.id', 'm.title', 'm.updated_at'])->from(['um' => 'user_message'])
            ->innerJoin(['m' => 'message'], 'm.id = um.message_id')->where(['um.user_id' => $ich->id])
            ->andFilterWhere(['not in', 'm.id', $schon])->orderBy(['m.updated_at' => SORT_DESC])->limit(40)->all();
        return $this->render('uebergeben', ['kreis' => $this->kreis(), 'team' => $team, 'unterhaltungen' => $unterhaltungen]);
    }

    public function actionPost(int $t, int $p)
    {
        $team = $this->team($t);
        $ich = $this->ich();
        $this->pruefe(Teams::darfPostfach($team, $ich));
        $post = TeamPost::findOne(['id' => $p, 'team_id' => $team->id]) ?? throw new NotFoundHttpException();
        $aktion = (string)Yii::$app->request->post('aktion');
        if ($aktion === 'uebernehmen') {
            $ok = Teams::uebernehmen($post, $ich);
            return $this->zurTeamseite($team, Texte::t($ok ? 'team_uebernommen_ok' : 'team_schon_uebernommen'));
        }
        $neu = ['erledigt' => 'erledigt', 'kreisleitung' => 'kreisleitung', 'oeffnen' => 'offen'][$aktion] ?? null;
        if ($neu === null) {
            throw new NotFoundHttpException();
        }
        Teams::setzeStatus($post, $neu, $ich);
        return $this->zurTeamseite($team, Texte::t('gespeichert'));
    }

    /** "Antwort mit Nova vorbereiten": neues Nova-Gespraech mit dem Verlauf als Vorlage im Eingabefeld */
    public function actionNova(int $t, int $p)
    {
        $team = $this->team($t);
        $ich = $this->ich();
        $this->pruefe(Teams::darfPostfach($team, $ich));
        $post = TeamPost::findOne(['id' => $p, 'team_id' => $team->id]) ?? throw new NotFoundHttpException();
        $gespraeche = 'nexus\modules\communityAssistant\services\Gespraeche';
        $gespraechKlasse = 'nexus\modules\communityAssistant\models\Gespraech';
        if (!class_exists($gespraeche) || !$gespraeche::darfNutzen($ich)) {
            return $this->zurTeamseite($team, Texte::t('team_nova_fehlt'));
        }
        $jetzt = date('Y-m-d H:i:s');
        $g = new $gespraechKlasse(['user_id' => $ich->id, 'titel' => mb_substr('Antwort: ' . $post->betreff, 0, 120), 'art' => 'antwort',
            'created_at' => $jetzt, 'updated_at' => $jetzt]);
        $g->save(false);
        Yii::$app->session->setFlash('nx-nova-vorlage', sprintf(Texte::t('team_nova_vorlage'), $team->titel, Teams::verlaufText($post)));
        return $this->redirect(['/nexus-community-assistant/gespraech/index', 'g' => $g->id, '#' => 'nx-nova-ende']);
    }

    // ── Hilfen ──────────────────────────────────────────────────────────────

    private function kreis(): Space
    {
        return $this->contentContainer;
    }

    private function ich(): User
    {
        return Yii::$app->user->getIdentity();
    }

    private function pruefe(bool $erlaubt): void
    {
        if (!$erlaubt) {
            throw new ForbiddenHttpException(Texte::t('team_kein_recht'));
        }
    }

    private function team(int $id): Team
    {
        $team = Team::findOne(['id' => $id, 'space_id' => $this->kreis()->id]) ?? throw new NotFoundHttpException();
        $team->populateRelation('kreis', $this->kreis());
        return $team;
    }

    private function aufgabe(Team $team, int $id): TeamAufgabe
    {
        return TeamAufgabe::findOne(['id' => $id, 'team_id' => $team->id]) ?? throw new NotFoundHttpException();
    }

    private function meineMitgliedschaft(Team $team): TeamMitglied
    {
        return TeamMitglied::findOne(['team_id' => $team->id, 'user_id' => $this->ich()->id, 'status' => 'dabei']) ?? throw new ForbiddenHttpException(Texte::t('team_kein_recht'));
    }

    private function zurTeamseite(Team $team, ?string $meldung)
    {
        if ($meldung !== null) {
            Yii::$app->session->setFlash(self::FLASH, $meldung);
        }
        return $this->redirect($team->url());
    }
}
