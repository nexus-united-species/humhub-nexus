<?php

namespace nexus\modules\communityAssistant\services;

use nexus\modules\communityAssistant\Module;
use RuntimeException;

/**
 * Ruft die Nova-Schnittstelle des Cockpits auf (/api/nova/...). Dort laufen Claude und die Suche in den
 * freigegebenen Dokumenten; der KI-Schluessel liegt nur dort.
 */
class CockpitClient
{
    /** Eine Antwort mit mehreren Suchrunden dauert gemessen 20-60 Sekunden */
    private const ZEITLIMIT = 240;

    /** @return array{antwort: string, quellen: array, kosten_eur: float, modell: string} */
    public static function antwort(array $verlauf, string $system, string $zusatz, string $modell): array
    {
        return self::anfrage('POST', '/api/nova/antwort', [
            'verlauf' => $verlauf, 'system' => $system, 'system_zusatz' => $zusatz, 'modell' => $modell,
        ]);
    }

    /** @return array{freigegeben: array, ausgelassen: array} */
    public static function quellen(): array
    {
        return self::anfrage('GET', '/api/nova/quellen');
    }

    private static function anfrage(string $methode, string $pfad, ?array $daten = null): array
    {
        $basis = rtrim((string)Module::instanz()->cockpitUrl, '/');
        $token = Gespraeche::token();
        if ($basis === '' || $token === '') {
            throw new RuntimeException('Cockpit-Schnittstelle nicht eingerichtet.');
        }
        @set_time_limit(self::ZEITLIMIT + 30);
        $ch = curl_init($basis . $pfad);
        $kopf = ['Authorization: Bearer ' . $token, 'Accept: application/json'];
        $optionen = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => self::ZEITLIMIT, CURLOPT_CONNECTTIMEOUT => 10];
        if ($methode === 'POST') {
            $json = json_encode($daten, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if ($json === false) {
                throw new RuntimeException('Anfrage nicht als JSON kodierbar: ' . json_last_error_msg());
            }
            $kopf[] = 'Content-Type: application/json';
            $optionen += [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $json];
        }
        curl_setopt_array($ch, $optionen + [CURLOPT_HTTPHEADER => $kopf]);
        $roh = curl_exec($ch);
        $fehler = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($fehler !== '') {
            throw new RuntimeException("Cockpit nicht erreichbar: {$fehler}");
        }
        $antwort = json_decode((string)$roh, true);
        if ($code !== 200 || !is_array($antwort)) {
            $detail = is_array($antwort) ? json_encode($antwort['detail'] ?? $antwort, JSON_UNESCAPED_UNICODE) : mb_substr((string)$roh, 0, 200);
            throw new RuntimeException("Cockpit meldet HTTP {$code}: {$detail}");
        }
        return $antwort;
    }
}
