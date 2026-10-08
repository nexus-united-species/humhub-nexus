<?php

namespace nexus\modules\arbeitszeit\services;

use humhub\modules\user\models\User;
use nexus\modules\arbeitszeit\models\Eintrag;
use nexus\modules\communityAssistant\services\AiService;
use Throwable;
use Yii;

/**
 * Arbeitsstunden per privater Nachricht an den Assistenten, in ganz normalen Worten
 * ("heute 2 Stunden am Newsletter fuer Kreis 7 gearbeitet") -- ohne Befehle und ohne "|"-Zeichen,
 * an dem bei Telegram viele scheiterten. Aufgerufen aus DirectMessageReplyService (Modul
 * nexus-community-assistant) VOR der normalen KI-Antwort; null = "nicht meins", dann antwortet
 * der Assistent wie gewohnt.
 *
 * Die KI springt nur an, wenn eine Zeitangabe im Text steht (billiger Vorfilter) -- normale
 * Fragen an den Assistenten laufen unveraendert. Fehlt der Kreis, entsteht ein ENTWURF und der
 * Assistent fragt mit nummerierter Liste nach; die naechste Antwort ("3" oder "Kreis 7") ergaenzt ihn.
 */
class AssistentErfassung
{
    private const ZEITANGABE = '/\d+(?:[.,]\d+)?\s*(?:h\b|std\b|std\.|stunde|stunden|hours?\b|hrs?\b|horas?\b)|halbe[n]?\s+stunde|dreiviertel\s*stunde|viertelstunde|half\s+an\s+hour|an\s+hour\b|media\s+hora|una\s+hora|eine\s+stunde/iu';
    private const UEBERSICHT = '/\b(meine\s+(arbeits)?stunden|my\s+(working\s+)?hours|mis\s+horas)\b/iu';
    private const ABBRUCH = '/^\s*(abbrechen|nein|stopp|cancel|no|cancelar)\s*[.!]?\s*$/iu';
    private const ENTWURF_GUELTIG_STUNDEN = 48;

    public static function verarbeiten(User $user, string $text): ?string
    {
        $text = trim($text);
        $sprache = ZeitService::spracheVon($user);
        try {
            $entwurf = Eintrag::find()->where(['user_id' => $user->id, 'status' => Eintrag::STATUS_ENTWURF])
                ->andWhere(['>=', 'created_at', date('Y-m-d H:i:s', time() - self::ENTWURF_GUELTIG_STUNDEN * 3600)])
                ->orderBy(['id' => SORT_DESC])->one();

            if ($entwurf !== null && !preg_match(self::ZEITANGABE, $text)) {
                return self::entwurfErgaenzen($entwurf, $text, $sprache);
            }
            if (preg_match(self::UEBERSICHT, $text) && !preg_match(self::ZEITANGABE, $text)) {
                return self::uebersicht($user, $sprache);
            }
            if (!preg_match(self::ZEITANGABE, $text)) {
                return null;
            }
            return self::neuerEintrag($user, $text, $sprache);
        } catch (Throwable $e) {
            Yii::error('nexus-arbeitszeit: Erfassung per Assistent fehlgeschlagen: ' . $e->getMessage(), 'nexus-arbeitszeit');
            return null; // lieber normale Antwort als gar keine
        }
    }

    private static function neuerEintrag(User $user, string $text, string $sprache): ?string
    {
        $kreise = ZeitService::kreiseFuer($user->id);
        $daten = self::ausText($text, $kreise);
        if ($daten === null || empty($daten['ist_eintrag'])) {
            return null; // Zeitangabe, aber keine Arbeitsmeldung ("wie lange dauert das Meeting? 2 Stunden?")
        }
        $kreisId = isset($daten['kreis_id']) && array_key_exists((int)$daten['kreis_id'], $kreise) ? (int)$daten['kreis_id'] : null;
        // Alte, nie beantwortete Entwuerfe verwerfen -- es soll immer nur EINE offene Rueckfrage geben.
        Eintrag::deleteAll(['user_id' => $user->id, 'status' => Eintrag::STATUS_ENTWURF]);
        [$eintrag, $fehler] = ZeitService::anlegen($user, (float)($daten['stunden'] ?? 0), (string)($daten['datum'] ?? date('Y-m-d')),
            $kreisId, trim((string)($daten['taetigkeit'] ?? '')), 'assistent');
        if ($eintrag === null) {
            return self::t('unklar', $sprache) . "\n\n" . implode("\n", array_map(fn($f) => '• ' . self::fehlerText($f, $sprache), $fehler))
                . "\n\n" . self::t('beispiel', $sprache);
        }
        if ($eintrag->status === Eintrag::STATUS_ENTWURF) {
            return self::kreisFrage($eintrag, $kreise, $sprache);
        }
        return self::bestaetigung($eintrag, $sprache);
    }

    private static function entwurfErgaenzen(Eintrag $entwurf, string $text, string $sprache): ?string
    {
        if (preg_match(self::ABBRUCH, $text)) {
            $entwurf->delete();
            return self::t('abgebrochen', $sprache);
        }
        $kreise = ZeitService::kreiseFuer($entwurf->user_id);
        $spaceId = self::kreisAusAntwort($text, $kreise);
        if ($spaceId === null) {
            // Laengere Nachricht ohne Kreis = vermutlich eine ganz andere Frage: normal antworten.
            return mb_strlen($text) > 60 ? null : self::kreisFrage($entwurf, $kreise, $sprache, true);
        }
        ZeitService::kreisSetzen($entwurf, $spaceId);
        return self::bestaetigung(Eintrag::findOne($entwurf->id), $sprache);
    }

    /** "3" (Nummer aus der Liste), "Kreis 7" / "Circle 7" / "Círculo 7" oder ein Namensbestandteil ("SOBA"). */
    private static function kreisAusAntwort(string $text, array $kreise): ?int
    {
        $ids = array_keys($kreise);
        if (preg_match('/^\s*(\d{1,2})\s*[.)]?\s*$/u', $text, $m) && isset($ids[(int)$m[1] - 1])) {
            return $ids[(int)$m[1] - 1];
        }
        $norm = fn(string $s) => preg_replace('/\b(circle|c[ií]rculo)\b/u', 'kreis', mb_strtolower($s));
        $antwort = $norm($text);
        if (preg_match('/kreis\s*(\d+)/u', $antwort, $m)) {
            foreach ($kreise as $id => $name) {
                if (preg_match('/^kreis\s*' . $m[1] . '\b/u', $norm($name))) {
                    return $id;
                }
            }
        }
        $treffer = [];
        foreach ($kreise as $id => $name) {
            foreach (preg_split('/[^\p{L}\p{N}]+/u', $norm($name)) as $wort) {
                if (mb_strlen($wort) >= 4 && $wort !== 'kreis' && preg_match('/\b' . preg_quote($wort, '/') . '\b/u', $antwort)) {
                    $treffer[$id] = true;
                }
            }
        }
        return count($treffer) === 1 ? array_key_first($treffer) : null;
    }

    /** KI zieht Stunden, Tag, Taetigkeit und (falls genannt) den Kreis aus dem freien Text. */
    private static function ausText(string $text, array $kreise): ?array
    {
        if (!class_exists(AiService::class)) {
            return null;
        }
        $liste = implode("\n", array_map(fn($id, $name) => "$id: $name", array_keys($kreise), $kreise));
        $heute = date('Y-m-d') . ' (' . ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'][(int)date('w')] . ')';
        $anweisung = "Du liest eine Nachricht eines Mitglieds an den N.E.X.U.S.-Assistenten und erkennst, ob die Person Arbeitszeit meldet, "
            . "die sie fuer N.E.X.U.S. geleistet hat. Antworte NUR mit JSON, ohne Erklaerung:\n"
            . '{"ist_eintrag": true|false, "stunden": Zahl, "datum": "JJJJ-MM-TT", "taetigkeit": "kurz, in der Sprache der Nachricht", "kreis_id": Zahl oder null}' . "\n"
            . "Regeln: ist_eintrag nur true, wenn die Person von geleisteter Arbeit berichtet (nicht bei Fragen oder Plaenen). "
            . "Halbe Stunde = 0.5, Viertelstunde = 0.25, 1h30 = 1.5. Heute ist $heute; 'gestern', Wochentage usw. relativ dazu, "
            . "ohne Angabe = heute. kreis_id nur, wenn ein Kreis klar genannt oder eindeutig gemeint ist, sonst null. "
            . "Die Kreise der Person:\n$liste";
        $roh = (new AiService())->frage("Nachricht:\n" . $text, $anweisung, 0.0);
        $roh = trim(preg_replace('/^```[a-z]*\s*|\s*```$/i', '', trim($roh)));
        $daten = json_decode($roh, true);
        return is_array($daten) ? $daten : null;
    }

    private static function kreisFrage(Eintrag $eintrag, array $kreise, string $sprache, bool $nochmal = false): string
    {
        $liste = '';
        $n = 1;
        foreach ($kreise as $name) {
            $liste .= $n++ . '. ' . $name . "\n";
        }
        return ($nochmal ? self::t('nicht_erkannt', $sprache) . "\n\n" : '')
            . sprintf(self::t('welcher_kreis', $sprache), $eintrag->stundenText(), ZeitService::datumText($eintrag->datum), $eintrag->beschreibung)
            . "\n\n" . $liste . "\n" . self::t('nummer', $sprache);
    }

    private static function bestaetigung(Eintrag $e, string $sprache): string
    {
        return sprintf(self::t('eingetragen', $sprache), $e->stundenText(), ZeitService::datumText($e->datum), $e->space->name ?? '–', $e->beschreibung)
            . "\n\n" . self::t('danach', $sprache) . ' ' . Benachrichtigung::link('/nexus-arbeitszeit/meine/index');
    }

    private static function uebersicht(User $user, string $sprache): string
    {
        $s = ZeitService::summen($user->id);
        $f = fn(float $h) => rtrim(rtrim(number_format($h, 2, ',', '.'), '0'), ',') ?: '0';
        return sprintf(self::t('uebersicht', $sprache), $f($s['gesamt']), $f($s['monat']), $f($s['offen']))
            . "\n\n" . Benachrichtigung::link('/nexus-arbeitszeit/meine/index');
    }

    private static function fehlerText(string $schluessel, string $sprache): string
    {
        return self::t($schluessel, $sprache);
    }

    private static function t(string $schluessel, string $sprache): string
    {
        $t = [
            'eingetragen' => [
                'de' => "✅ Eingetragen: **%s Std.** am %s – %s\n> %s",
                'en' => "✅ Recorded: **%s h** on %s – %s\n> %s",
                'es' => "✅ Anotado: **%s h** el %s – %s\n> %s"],
            'danach' => [
                'de' => 'Ein Admin gibt es frei, du bekommst dann eine Nachricht. Deine Übersicht:',
                'en' => 'An admin will approve it and you will get a message. Your overview:',
                'es' => 'Un administrador lo aprobará y recibirás un mensaje. Tu resumen:'],
            'welcher_kreis' => [
                'de' => "Danke! Ich trage **%s Std.** am %s ein:\n> %s\n\nFür welchen Kreis war das?",
                'en' => "Thanks! I'll record **%s h** on %s:\n> %s\n\nWhich circle was it for?",
                'es' => "¡Gracias! Anoto **%s h** el %s:\n> %s\n\n¿Para qué círculo fue?"],
            'nummer' => [
                'de' => 'Antworte einfach mit der **Nummer** (z. B. „3“) – oder mit „abbrechen“.',
                'en' => 'Just reply with the **number** (e.g. “3”) – or “cancel”.',
                'es' => 'Responde simplemente con el **número** (p. ej. «3») o con «cancelar».'],
            'nicht_erkannt' => [
                'de' => 'Den Kreis habe ich leider nicht erkannt.',
                'en' => "Sorry, I didn't recognise the circle.",
                'es' => 'Lo siento, no reconocí el círculo.'],
            'abgebrochen' => [
                'de' => 'Alles klar, ich habe nichts eingetragen.',
                'en' => "All right, I haven't recorded anything.",
                'es' => 'De acuerdo, no he anotado nada.'],
            'unklar' => [
                'de' => 'Das konnte ich leider nicht eintragen:',
                'en' => "I couldn't record that:",
                'es' => 'No pude anotarlo:'],
            'beispiel' => [
                'de' => 'Schreib es z. B. so: „Heute 2 Stunden am Newsletter für Kreis 7 gearbeitet.“',
                'en' => 'Write it like this, e.g.: “Worked 2 hours on the newsletter for Circle 7 today.”',
                'es' => 'Escríbelo así, p. ej.: «Hoy trabajé 2 horas en el boletín para el Círculo 7».'],
            'uebersicht' => [
                'de' => "⏱ Deine Arbeitsstunden:\n• freigegeben insgesamt: **%s Std.**\n• davon diesen Monat: **%s Std.**\n• wartet auf Freigabe: **%s Std.**",
                'en' => "⏱ Your working hours:\n• approved in total: **%s h**\n• of which this month: **%s h**\n• waiting for approval: **%s h**",
                'es' => "⏱ Tus horas de trabajo:\n• aprobadas en total: **%s h**\n• de ellas este mes: **%s h**\n• pendientes: **%s h**"],
            'fehler_stunden' => ['de' => 'die Stunden (zwischen 0,25 und 16)', 'en' => 'the hours (between 0.25 and 16)', 'es' => 'las horas (entre 0,25 y 16)'],
            'fehler_datum' => ['de' => 'der Tag (nicht in der Zukunft)', 'en' => 'the day (not in the future)', 'es' => 'el día (no en el futuro)'],
            'fehler_text' => ['de' => 'was du gemacht hast', 'en' => 'what you did', 'es' => 'qué hiciste'],
            'fehler_kreis' => ['de' => 'der Kreis (du musst Mitglied sein)', 'en' => 'the circle (you must be a member)', 'es' => 'el círculo (debes ser miembro)'],
        ];
        return $t[$schluessel][$sprache] ?? $t[$schluessel]['de'] ?? $schluessel;
    }
}
