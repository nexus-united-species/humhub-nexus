<?php

namespace nexus\modules\abzeichen\services;

use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\abzeichen\Module;

/**
 * Eine Feier freiwillig mit der Gemeinschaft teilen: ein Beitrag im eingestellten Kreis (teilenKreis),
 * verfasst von dem Menschen selbst. Nur auf Knopfdruck -- von sich aus bleibt alles privat.
 * Derselbe Stand laesst sich nur einmal teilen.
 */
class Teilen
{
    public const MAX_NOTIZ = 500;

    public static function kreis(): ?Space
    {
        $modul = Module::instanz();
        $id = (int)($modul->teilenKreis ?? 0);
        return $id > 0 ? Space::findOne(['id' => $id]) : null;
    }

    /** Darf dieser Mensch diesen Moment jetzt teilen? (erreicht, noch nicht geteilt, Mitglied im Kreis) */
    public static function moeglich(User $ich, Moment $moment, ?Space $kreis = null): bool
    {
        $kreis ??= self::kreis();
        $wert = (int)(Aktivzeit::zaehler((int)$ich->id)[$moment->kennung] ?? 0);
        return $kreis !== null && $wert > 0
            && (int)(Aktivzeit::geteilt((int)$ich->id)[$moment->kennung] ?? 0) < $wert
            && $kreis->isMember($ich->id);
    }

    /** @return string|null Adresse des neuen Beitrags */
    public static function teilen(User $ich, Moment $moment, string $notiz): ?string
    {
        $kreis = self::kreis();
        if ($kreis === null || !self::moeglich($ich, $moment, $kreis)) {
            return null;
        }
        $wert = (int)(Aktivzeit::zaehler((int)$ich->id)[$moment->kennung] ?? 0);
        $notiz = trim(mb_substr(strip_tags($notiz), 0, self::MAX_NOTIZ));
        $post = new Post($kreis);
        $post->message = $moment->teilenText($wert) . ($notiz !== '' ? "\n\n" . $notiz : '');
        if (!$post->save()) {
            return null;
        }
        Aktivzeit::geteiltMerken((int)$ich->id, $moment->kennung, $wert);
        return $post->content->getUrl();
    }
}
