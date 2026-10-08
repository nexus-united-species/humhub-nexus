<?php

namespace nexus\modules\karte\services;

use Yii;

/**
 * Sucht einen Ort bei OpenStreetMap (Nominatim) -- vom SERVER aus, der Browser des Mitglieds
 * spricht nie mit dem fremden Dienst.
 *
 * Angenommen werden nur Orte und Regionen (ERLAUBT). Eine Strasse oder ein Haus liefert bewusst
 * keinen Treffer: Die Karte soll nie eine Adresse zeigen.
 */
class Ortssuche
{
    private const URL = 'https://nominatim.openstreetmap.org/search';
    private const ZEITLIMIT_S = 8;
    private const MAX_TREFFER = 6;

    /**
     * Gebiete (Gemeinde-, Kreis-, Landesgrenzen; auch Inseln wie Teneriffa, die OSM als
     * "boundary/political" fuehrt) gelten immer. Bei "place" nur Orte und Regionen -- kein
     * einzelnes Haus, kein Hof, kein Weiler.
     */
    private const GEBIET = 'boundary';
    private const ORT = 'place';
    private const ORTSARTEN = [
        'city', 'town', 'village', 'municipality', 'city_district', 'borough', 'suburb',
        'county', 'district', 'state_district', 'province', 'state', 'region',
        'island', 'archipelago',
    ];

    /**
     * @return array{0: array<int, array{name: string, lat: float, lng: float}>, 1: bool}
     *         Treffer und "Suche hat funktioniert" (false = Dienst nicht erreichbar).
     */
    public static function suche(string $frage): array
    {
        $frage = trim(mb_substr($frage, 0, 120));
        if (mb_strlen($frage) < 2) {
            return [[], true];
        }
        $adresse = self::URL . '?' . http_build_query([
            'q' => $frage,
            'format' => 'jsonv2',
            'limit' => 20,
            'addressdetails' => 0,
            'accept-language' => Texte::sprache(),
        ]);
        $ch = curl_init($adresse);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::ZEITLIMIT_S,
            // Nominatim verlangt eine erkennbare Absenderangabe (Einstellung "absender").
            CURLOPT_USERAGENT => \nexus\modules\karte\Module::instanz()->absender(),
        ]);
        $antwort = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $daten = is_string($antwort) ? json_decode($antwort, true) : null;
        if ($status !== 200 || !is_array($daten)) {
            Yii::warning("nexus-karte: Ortssuche fehlgeschlagen (HTTP $status)", 'nexus-karte');
            return [[], false];
        }

        $treffer = [];
        foreach ($daten as $zeile) {
            $klasse = (string)($zeile['category'] ?? '');
            $erlaubt = $klasse === self::GEBIET
                || ($klasse === self::ORT && in_array((string)($zeile['type'] ?? ''), self::ORTSARTEN, true));
            if (!$erlaubt || !isset($zeile['lat'], $zeile['lon'], $zeile['display_name'])) {
                continue;
            }
            $lat = round((float)$zeile['lat'], 2);
            $lng = round((float)$zeile['lon'], 2);
            // Derselbe Ort kommt oft doppelt (als Grenze und als Ortspunkt).
            $treffer["$lat|$lng"] ??= ['name' => (string)$zeile['display_name'], 'lat' => $lat, 'lng' => $lng];
            if (count($treffer) >= self::MAX_TREFFER) {
                break;
            }
        }
        return [array_values($treffer), true];
    }
}
