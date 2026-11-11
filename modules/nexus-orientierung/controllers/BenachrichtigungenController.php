<?php

namespace nexus\modules\orientierung\controllers;

use Exception;
use humhub\modules\notification\controllers\ListController;
use humhub\modules\notification\models\Notification;
use Yii;
use yii\db\IntegrityException;

/**
 * Ersetzt die Nachlade-Aktion der Benachrichtigungsliste (Glocke oben), siehe Events::onAnfrage().
 *
 * HumHub blaettert dort ueber die Nummer des zuletzt gezeigten Eintrags weiter (Notification::loadMore():
 * "id < letzte Nummer"). Die Liste ist aber nach ZEIT sortiert und fasst Eintraege zusammen
 * ("A und B kommentierten ..."); die Nummer eines zusammengefassten Eintrags ist die seiner AELTESTEN
 * Benachrichtigung. Enthielt die erste Seite eine Gruppe, die vor Wochen begann, sprang die naechste Seite
 * dorthin zurueck -- alles dazwischen fehlte (gemeldet 11.11.2026: kein einziger Eintrag aus dem Oktober).
 *
 * Hier wird stattdessen ueber die Position in der sortierten Liste weitergeblaettert. Das Skript der
 * Glocke schickt den Wert "lastEntryId" unveraendert zurueck, deshalb steht dort jetzt die Position.
 * Die Seite "Alle Benachrichtigungen anzeigen" blaettert schon richtig und bleibt unberuehrt.
 */
class BenachrichtigungenController extends ListController
{
    /** Wie im Original: das Skript erkennt das Ende daran, dass weniger als 6 kommen. */
    private const JE_SEITE = 6;

    public function actionIndex()
    {
        $versatz = max(0, (int)Yii::$app->request->get('from', 0));
        $notifications = Notification::findGrouped()->offset($versatz)->limit(self::JE_SEITE)->all();

        $output = '';
        foreach ($notifications as $notification) {
            try {
                $baseModel = $notification->getBaseModel();
                if (!$baseModel || !$baseModel->validate()) {
                    throw new IntegrityException('Invalid base model found for notification');
                }
                $output .= $baseModel->render();
                $notification->update();
            } catch (IntegrityException $ie) {
                $notification->delete();
                Yii::warning('Deleted inconsistent notification with id ' . $notification->id . '. ' . $ie->getMessage());
            } catch (Exception $e) {
                Yii::error('Could not display notification: ' . $notification->id . '(' . $e . ')');
            }
        }

        return $this->asJson([
            'newNotifications' => Notification::findUnseen()->count(),
            'lastEntryId' => $versatz + count($notifications),
            'output' => $output,
            'counter' => count($notifications),
        ]);
    }
}
