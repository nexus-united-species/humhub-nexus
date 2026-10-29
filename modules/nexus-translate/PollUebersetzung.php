<?php

namespace humhub\modules\nexusTranslate;

/**
 * Umfragen im Stream in der Sprache des Lesers (Josh, 29.10.2026: "Umfragen werden nicht
 * uebersetzt"). Anders als Kalendertermine laesst sich hier nichts ueber die Austauschliste
 * ersetzen: Antworten wie "Ja" oder "Nein" sind zu kurz und kaemen sonst im ganzen Portal vor.
 * Deshalb arbeitet alles im Eintrag selbst, wie bei Beitraegen (TranslateLink, Typ "poll"):
 *   - Frage (steht im Kopf des Eintrags), Beschreibung und die Antworten werden einzeln
 *     uebersetzt und je Text und Sprache nur EINMAL gespeichert (UebersetzungsSpeicher),
 *   - das Skript tauscht sie im Eintrag aus und bietet "Original anzeigen".
 * Die Sprache wird an Frage + Beschreibung + Antworten zusammen erkannt (die Frage allein ist
 * meist zu kurz); ist sie nicht sicher erkennbar, bleibt das Original stehen.
 */
class PollUebersetzung
{
    public const KLASSE = 'humhub\modules\polls\models\Poll';

    public static function istPoll($record): bool
    {
        return is_object($record) && is_a($record, self::KLASSE);
    }

    /** Alle sichtbaren Texte der Umfrage -- Grundlage der Spracherkennung. */
    public static function text($poll): string
    {
        $teile = [trim((string)$poll->question), trim((string)$poll->description)];
        foreach ($poll->answers as $antwort) {
            $teile[] = trim((string)$antwort->answer);
        }
        return trim(implode("\n", array_filter($teile, fn($t) => $t !== '')));
    }
}
