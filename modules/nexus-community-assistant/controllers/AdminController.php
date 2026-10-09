<?php

namespace nexus\modules\communityAssistant\controllers;

use humhub\modules\space\models\Space;
use nexus\modules\communityAssistant\models\Meeting;
use nexus\modules\communityAssistant\models\MeetingSpace;
use nexus\modules\communityAssistant\models\ReminderState;
use Yii;
use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use yii\web\NotFoundHttpException;

/**
 * Einfache Admin-Oberflaeche: Meeting-Liste + Formular, dazu ein
 * schneller Ueberblick, wann Spendenaufruf/Selbstvorstellung/Wochenbericht
 * zuletzt gelaufen sind. Bewusst ohne eigenes Menu-Widget oder AJAX
 * (Vorgabe: "keine unnoetig komplexe Oberflaeche").
 */
class AdminController extends Controller
{
    /** Nur Systemadministratoren -- ueber HumHubs Zugriffsregeln (auch Gast-, Wartungs- und Kontoregeln). */
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_ADMIN_ONLY]];
    }

    public function actionIndex()
    {
        $meetings = Meeting::find()->orderBy(['active' => SORT_DESC, 'title' => SORT_ASC])->all();
        $spaces = Space::find()->orderBy(['name' => SORT_ASC])->all();

        $spaceNamenJeMeeting = [];
        foreach ($meetings as $meeting) {
            $namen = [];
            foreach ($meeting->zielSpaceIds() as $spaceId) {
                $space = Space::findOne(['id' => $spaceId]);
                $namen[] = $space ? $space->name : "Space {$spaceId} (geloescht)";
            }
            $spaceNamenJeMeeting[$meeting->id] = $namen;
        }

        return $this->render('index', [
            'meetings' => $meetings,
            'spaces' => $spaces,
            'spaceNamenJeMeeting' => $spaceNamenJeMeeting,
            'letzterWochenbericht' => ReminderState::letzterLauf('weekly_summary'),
            'letzterSpendenaufruf' => ReminderState::letzterLauf('donation_reminder'),
            'letzteSelbstvorstellung' => ReminderState::letzterLauf('self_intro_reminder'),
        ]);
    }

    public function actionCreate()
    {
        return $this->form(new Meeting());
    }

    public function actionEdit(int $id)
    {
        return $this->form($this->findMeeting($id));
    }

    private function form(Meeting $meeting)
    {
        $spaces = Space::find()->orderBy(['name' => SORT_ASC])->all();
        $ausgewaehlteSpaceIds = $meeting->isNewRecord ? [] : $meeting->zielSpaceIds();

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $meeting->title = trim((string)($post['title'] ?? ''));
            $meeting->message = trim((string)($post['message'] ?? ''));
            $meeting->active = !empty($post['active']);

            $typ = $post['typ'] ?? 'wiederkehrend';
            if ($typ === 'einmalig') {
                $meeting->recurrence_weekday = null;
                $meeting->recurrence_time = null;
                $eventDate = trim((string)($post['event_date'] ?? ''));
                $meeting->event_date = $eventDate !== '' ? date('Y-m-d H:i:s', strtotime($eventDate)) : null;
            } else {
                $meeting->event_date = null;
                $meeting->recurrence_weekday = (int)($post['recurrence_weekday'] ?? 1);
                $meeting->recurrence_time = trim((string)($post['recurrence_time'] ?? '18:00'));
            }

            $ausgewaehlteSpaceIds = array_map('intval', (array)($post['space_ids'] ?? []));

            if ($meeting->save()) {
                MeetingSpace::deleteAll(['meeting_id' => $meeting->id]);
                foreach ($ausgewaehlteSpaceIds as $spaceId) {
                    (new MeetingSpace(['meeting_id' => $meeting->id, 'space_id' => $spaceId]))->save(false);
                }
                Yii::$app->session->setFlash('success', 'Meeting gespeichert.');
                return $this->redirect(['index']);
            }
        }

        return $this->render('meeting-form', [
            'meeting' => $meeting,
            'spaces' => $spaces,
            'ausgewaehlteSpaceIds' => $ausgewaehlteSpaceIds,
        ]);
    }

    public function actionDelete(int $id)
    {
        $this->requirePost();
        $meeting = $this->findMeeting($id);
        MeetingSpace::deleteAll(['meeting_id' => $meeting->id]);
        $meeting->delete();

        Yii::$app->session->setFlash('success', 'Meeting geloescht.');
        return $this->redirect(['index']);
    }

    private function findMeeting(int $id): Meeting
    {
        $meeting = Meeting::findOne($id);
        if ($meeting === null) {
            throw new NotFoundHttpException('Meeting nicht gefunden.');
        }

        return $meeting;
    }

    private function requirePost(): void
    {
        if (!Yii::$app->request->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException();
        }
    }
}
