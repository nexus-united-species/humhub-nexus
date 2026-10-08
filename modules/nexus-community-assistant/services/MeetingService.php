<?php

namespace nexus\modules\communityAssistant\services;

use nexus\modules\communityAssistant\models\Meeting;
use Yii;

/**
 * Prueft alle aktiven Meeting-Eintraege und postet faellige automatisch in
 * ihre konfigurierten Ziel-Spaces. Gedacht fuer einen stuendlichen Aufruf
 * (siehe commands/check_meetings.php) -- daher die Sperren gegen
 * Mehrfachversand innerhalb derselben Stunde/Woche.
 */
class MeetingService
{
    public function __construct(
        private readonly int $assistantUserId,
    ) {
    }

    /**
     * @return string[] Titel der in diesem Lauf tatsaechlich geposteten Meetings
     */
    public function faelligePosten(): array
    {
        $jetzt = new \DateTimeImmutable();
        $heuteWochentag = (int)$jetzt->format('N'); // 1=Montag .. 7=Sonntag (ISO)
        $heuteStunde = $jetzt->format('H');

        $gepostet = [];
        foreach (Meeting::find()->where(['active' => true])->all() as $meeting) {
            if ($this->istFaellig($meeting, $jetzt, $heuteWochentag, $heuteStunde)) {
                $this->posten($meeting, $jetzt);
                $gepostet[] = $meeting->title;
            }
        }

        return $gepostet;
    }

    private function istFaellig(Meeting $meeting, \DateTimeImmutable $jetzt, int $heuteWochentag, string $heuteStunde): bool
    {
        if ($meeting->istEinmalig()) {
            if ($meeting->last_posted_at !== null) {
                return false;
            }
            $ziel = new \DateTimeImmutable($meeting->event_date);
            // Faellig, sobald der Zeitpunkt erreicht ist -- aber kein
            // Nachholen, wenn der stuendliche Check den Termin um mehr als
            // 2h verpasst hat (z.B. nach einem Serverausfall).
            $differenz = $jetzt->getTimestamp() - $ziel->getTimestamp();
            return $differenz >= 0 && $differenz < 7200;
        }

        if ($meeting->istWiederkehrend()) {
            if ((int)$meeting->recurrence_weekday !== $heuteWochentag) {
                return false;
            }
            [$zielStunde] = explode(':', $meeting->recurrence_time);
            if (str_pad($zielStunde, 2, '0', STR_PAD_LEFT) !== $heuteStunde) {
                return false;
            }
            if ($meeting->last_posted_at !== null) {
                $letztesMal = new \DateTimeImmutable($meeting->last_posted_at);
                if ($jetzt->getTimestamp() - $letztesMal->getTimestamp() < 6 * 24 * 3600) {
                    return false; // schon diese Woche gepostet
                }
            }
            return true;
        }

        return false;
    }

    private function posten(Meeting $meeting, \DateTimeImmutable $jetzt): void
    {
        $poster = new PosterService($this->assistantUserId);
        foreach ($meeting->zielSpaceIds() as $spaceId) {
            try {
                $poster->postInSpace($spaceId, $meeting->message);
            } catch (\Throwable $e) {
                Yii::error("nexus-community-assistant: Meeting '{$meeting->title}' -> Space {$spaceId} fehlgeschlagen: " . $e->getMessage());
            }
        }

        $meeting->last_posted_at = $jetzt->format('Y-m-d H:i:s');
        $meeting->save(false);
    }
}
