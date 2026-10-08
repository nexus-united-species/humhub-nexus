<?php

namespace nexus\modules\karte\services;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\karte\models\Standort;
use nexus\modules\karte\models\Suchender;

/** Was auf der Karte steht und wer welchen Ort aendern darf. */
class KartenDaten
{
    /**
     * Gemeinschaften mit Ort, fuer Karte und Liste. Versteckte oder abgeschaltete Kreise fehlen.
     *
     * @return array<int, array{id: int, name: string, ort: string, lat: float, lng: float, text: string, url: string, bild: string}>
     */
    public static function punkte(): array
    {
        $punkte = [];
        foreach (self::gemeinschaften() as [$space, $standort]) {
            if (!$standort->hatOrt() || (int)$space->visibility === Space::VISIBILITY_NONE) {
                continue;
            }
            $punkte[] = [
                'id' => (int)$space->id,
                'name' => (string)$space->name,
                'ort' => (string)$standort->ort,
                'lat' => (float)$standort->lat,
                'lng' => (float)$standort->lng,
                'text' => (string)$space->description,
                'url' => $space->getUrl(),
                // Kreisbild fuer die Stecknadel (ohne eigenes Bild liefert HumHub sein Standardbild).
                'bild' => (string)$space->getProfileImage()->getUrl(),
            ];
        }
        return $punkte;
    }

    /**
     * Fuer Gaeste (Willkommensseite): NUR Name, Ort und Lage -- keine Beschreibung, kein Bild,
     * kein Link in den Kreis. Alles Weitere gibt es erst nach der Anmeldung.
     *
     * @return array<int, array{id: int, name: string, ort: string, lat: float, lng: float}>
     */
    public static function punkteFuerGaeste(): array
    {
        return array_map(
            fn(array $p) => ['id' => $p['id'], 'name' => $p['name'], 'ort' => $p['ort'], 'lat' => $p['lat'], 'lng' => $p['lng']],
            self::punkte()
        );
    }

    /**
     * Mitglieder, die in ihrer Region Mitstreiter suchen -- NUR fuer Angemeldete gedacht, wird
     * Gaesten nie mitgegeben. Deaktivierte Konten fehlen.
     *
     * @return array<int, array{id: int, name: string, ort: string, lat: float, lng: float, text: string, url: string}>
     */
    public static function suchende(): array
    {
        $liste = [];
        $zeilen = Suchender::find()->joinWith('user')->where(['user.status' => User::STATUS_ENABLED])
            ->orderBy(['nexus_karte_suchende.ort' => SORT_ASC, 'nexus_karte_suchende.created_at' => SORT_ASC])->all();
        foreach ($zeilen as $zeile) {
            $liste[] = [
                'id' => (int)$zeile->user_id,
                'name' => (string)$zeile->user->displayName,
                'ort' => (string)$zeile->ort,
                'lat' => (float)$zeile->lat,
                'lng' => (float)$zeile->lng,
                'text' => (string)$zeile->nachricht,
                'url' => $zeile->user->getUrl(),
            ];
        }
        return $liste;
    }

    /**
     * Alle als Gemeinschaft markierten, aktiven Kreise (auch ohne Ort), nach Kreis-Reihenfolge.
     *
     * @return array<int, array{0: Space, 1: Standort}>
     */
    public static function gemeinschaften(): array
    {
        $paare = [];
        $standorte = Standort::find()->indexBy('space_id')->all();
        if ($standorte === []) {
            return [];
        }
        $kreise = Space::find()->where(['id' => array_keys($standorte), 'status' => Space::STATUS_ENABLED])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all();
        foreach ($kreise as $space) {
            $paare[] = [$space, $standorte[$space->id]];
        }
        return $paare;
    }

    /** Ort aendern darf ein Systemadmin und wer den Kreis leitet (Besitzer/Kreis-Admin). */
    public static function darfOrtAendern(User $benutzer, Space $space): bool
    {
        return $benutzer->isSystemAdmin() || $space->isAdmin($benutzer->id);
    }

    /**
     * Die Gemeinschaften, deren Ort dieser Mensch aendern darf.
     *
     * @return array<int, array{0: Space, 1: Standort}>
     */
    public static function verwaltbar(User $benutzer): array
    {
        return array_values(array_filter(
            self::gemeinschaften(),
            fn(array $paar) => self::darfOrtAendern($benutzer, $paar[0])
        ));
    }
}
