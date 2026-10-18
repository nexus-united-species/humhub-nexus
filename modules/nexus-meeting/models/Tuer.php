<?php

namespace nexus\modules\meeting\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;

/**
 * Eine offene Tuer: Dieser Mensch ist gerade am Rechner und ansprechbar -- wer mag, kommt ins Gespraech.
 *
 * Bewusst nur auf Knopfdruck (nie automatisch "online") und mit zwei Sicherungen, damit nie
 * "ist da" angezeigt wird, waehrend der Mensch laengst weg ist:
 * - Lebenszeichen: Solange das Gespraechsfenster offen ist, meldet der Browser sich jede Minute. Bleibt
 *   das LEBENSZEICHEN_MINUTEN lang aus (Browser zu, Rechner aus), gilt die Tuer als geschlossen.
 * - Hoechstdauer: Nach MAX_STUNDEN schliesst sie sich in jedem Fall (vergessen, Rechner laeuft weiter).
 *
 * @property int $user_id
 * @property string $raum
 * @property int $warteraum
 * @property string $geoeffnet_am
 * @property string $lebenszeichen_am
 */
class Tuer extends ActiveRecord
{
    public const LEBENSZEICHEN_MINUTEN = 5;
    public const MAX_STUNDEN = 4;

    public static function tableName()
    {
        return 'nexus_meeting_tuer';
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function istAbgelaufen(): bool
    {
        return strtotime($this->lebenszeichen_am) < time() - self::LEBENSZEICHEN_MINUTEN * 60
            || strtotime($this->geoeffnet_am) < time() - self::MAX_STUNDEN * 3600;
    }

    /** Raeumt abgelaufene Tueren weg und gibt die offenen zurueck. @return Tuer[] */
    public static function offene(): array
    {
        $grenzeLeben = date('Y-m-d H:i:s', time() - self::LEBENSZEICHEN_MINUTEN * 60);
        $grenzeMax = date('Y-m-d H:i:s', time() - self::MAX_STUNDEN * 3600);
        static::deleteAll(['or', ['<', 'lebenszeichen_am', $grenzeLeben], ['<', 'geoeffnet_am', $grenzeMax]]);
        return static::find()->with('user')->orderBy('geoeffnet_am')->all();
    }

    /** Neuer Raumname nur aus Buchstaben und Ziffern -- so verlangt es das Jitsi-Modul. */
    public static function neuerRaum(User $mensch): string
    {
        // Vorname statt Benutzername ("OffeneTuerJosh..." statt "OffeneTuerAdmin...") -- steht im kMeet-Fenster
        $vorname = strtr(explode(' ', trim((string)$mensch->displayName))[0], ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss']);
        $name = preg_replace('/[^A-Za-z0-9]/', '', ucfirst($vorname)) ?: 'Tuer';
        return 'OffeneTuer' . substr($name, 0, 30) . strtoupper(bin2hex(random_bytes(4)));
    }
}
