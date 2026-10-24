<?php

namespace nexus\modules\communityAssistant\services;

use humhub\modules\space\models\Membership;
use humhub\modules\user\models\User;
use nexus\modules\communityAssistant\models\Gespraech;
use nexus\modules\communityAssistant\models\GespraechNachricht;
use nexus\modules\communityAssistant\models\Merkpunkt;
use Throwable;
use Yii;

/**
 * Eine Nachricht in einem Nova-Gespraech beantworten: Anweisung bauen, Wissensbereiche des Portals
 * durchsuchen, Cockpit fragen (dort Claude + Suche in den freigegebenen Dokumenten), Antwort speichern.
 *
 * Gedaechtnis: Nova haengt [[MERKEN: ...]] an, wenn das Mitglied etwas Bleibendes ueber sich erzaehlt.
 * Die Marke wird entfernt und als Merkpunkt gespeichert -- das Mitglied sieht und loescht ihn selbst.
 */
class GespraechAntwort
{
    private const MARKE_MERKEN = '/\[\[MERKEN:\s*(.+?)\]\]\s*/u';
    private const MARKE_QUELLEN = '/\[\[QUELLEN:\s*(.*?)\]\]\s*/su';
    private const WISSEN_TREFFER = 4;
    private const WISSEN_ZEICHEN = 2500;
    /** Mindestpunkte der Portal-Suche: bei langen Fragen reicht sonst ein einzelnes zufaellig passendes Wort */
    private const WISSEN_MIN_PUNKTE = 12;
    /** Ohne Angabe der KI: so viele Dokument-Treffer als Quellen zeigen */
    private const QUELLEN_OHNE_ANGABE = 5;

    /**
     * Unter der Antwort stehen nur die Quellen, die Nova selbst als benutzt nennt ([[QUELLEN: A | B]]) --
     * vorher stand dort jeder Suchtreffer, auch unpassende. Unbekannte Titel fallen weg.
     * @return array{0: string, 1: array}
     */
    private static function genutzteQuellen(string $antwort, array $kandidaten): array
    {
        $genannt = null;
        $antwort = trim(preg_replace_callback(self::MARKE_QUELLEN, function ($m) use (&$genannt) {
            $genannt = array_filter(array_map('trim', explode('|', $m[1])));
            return '';
        }, $antwort));
        if ($genannt === null) {
            $dokumente = array_values(array_filter($kandidaten, fn($q) => ($q['art'] ?? '') !== 'wissen'));
            return [$antwort, array_slice($dokumente, 0, self::QUELLEN_OHNE_ANGABE)];
        }
        $nachTitel = [];
        foreach ($kandidaten as $q) {
            $nachTitel[mb_strtolower(trim((string)$q['titel']))] = $q;
        }
        $liste = [];
        foreach ($genannt as $titel) {
            $q = $nachTitel[mb_strtolower($titel)] ?? null;
            if ($q !== null && !in_array($q, $liste, true)) {
                $liste[] = $q;
            }
        }
        return [$antwort, $liste];
    }

    /**
     * @return string|null Fehlermeldung fuer das Mitglied, null bei Erfolg
     */
    public static function senden(Gespraech $gespraech, string $text, User $mensch): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        if (mb_strlen($text) > Gespraeche::MAX_NACHRICHT) {
            return sprintf(Gespraeche::t('zu_lang'), Gespraeche::MAX_NACHRICHT);
        }
        $sperre = Gespraeche::sperre($mensch);
        if ($sperre !== null) {
            return $sperre;
        }
        $jetzt = date('Y-m-d H:i:s');
        (new GespraechNachricht(['gespraech_id' => $gespraech->id, 'rolle' => 'user', 'inhalt' => $text, 'created_at' => $jetzt]))->save(false);
        if ($gespraech->titel === '') {
            $gespraech->titel = mb_substr(preg_replace('/\s+/u', ' ', $text), 0, 80);
        }
        $gespraech->updated_at = $jetzt;
        $gespraech->save(false);

        try {
            $ergebnis = self::fragen($gespraech, $text, $mensch);
        } catch (Throwable $e) {
            Yii::error('nexus-community-assistant: Nova-Gespraech ' . $gespraech->id . ': ' . $e->getMessage());
            return Gespraeche::t('stoerung');
        }

        $antwort = (string)$ergebnis['antwort'];
        $merken = [];
        $antwort = trim(preg_replace_callback(self::MARKE_MERKEN, function ($m) use (&$merken) {
            $merken[] = trim($m[1]);
            return '';
        }, $antwort));
        foreach ($merken as $punkt) {
            self::merken($mensch, $punkt);
        }
        [$antwort, $quellen] = self::genutzteQuellen($antwort, array_merge($ergebnis['quellen'] ?? [], $ergebnis['wissen'] ?? []));
        (new GespraechNachricht([
            'gespraech_id' => $gespraech->id, 'rolle' => 'assistant', 'inhalt' => $antwort !== '' ? $antwort : '…',
            'quellen' => $quellen !== [] ? json_encode($quellen, JSON_UNESCAPED_UNICODE) : null,
            'kosten' => (float)($ergebnis['kosten_eur'] ?? 0), 'created_at' => date('Y-m-d H:i:s'),
        ]))->save(false);
        Gespraeche::zaehlen($mensch, (float)($ergebnis['kosten_eur'] ?? 0));
        $gespraech->updateAttributes(['updated_at' => date('Y-m-d H:i:s')]);
        return null;
    }

    /** @return array{antwort: string, quellen: array, wissen: array, kosten_eur: float} */
    private static function fragen(Gespraech $gespraech, string $frage, User $mensch): array
    {
        $verlauf = [];
        $nachrichten = GespraechNachricht::find()->where(['gespraech_id' => $gespraech->id])
            ->orderBy(['id' => SORT_DESC])->limit(Gespraeche::VERLAUF)->all();
        foreach (array_reverse($nachrichten) as $n) {
            $rolle = $n->rolle === 'assistant' ? 'assistant' : 'user';
            // Die KI-Schnittstelle erwartet abwechselnde Rollen -- nach einer Stoerung stehen
            // zwei Fragen hintereinander, die werden zusammengefasst
            if ($verlauf !== [] && $verlauf[count($verlauf) - 1]['role'] === $rolle) {
                $verlauf[count($verlauf) - 1]['content'] .= "\n\n" . $n->inhalt;
            } else {
                $verlauf[] = ['role' => $rolle, 'content' => (string)$n->inhalt];
            }
        }
        while ($verlauf !== [] && $verlauf[0]['role'] !== 'user') {
            array_shift($verlauf);
        }
        [$wissenText, $wissenQuellen] = self::wissensbereiche($frage, $mensch);
        $ergebnis = CockpitClient::antwort($verlauf, self::anweisung($gespraech, $mensch), $wissenText, Gespraeche::modell());
        $ergebnis['wissen'] = $wissenQuellen;
        return $ergebnis;
    }

    /** Der gleichbleibende Teil der Anweisung (wird im Cockpit zwischengespeichert) */
    public static function anweisung(Gespraech $gespraech, User $mensch): string
    {
        $art = Gespraeche::ARTEN[$gespraech->art] ?? Gespraeche::ARTEN['frage'];
        // Kreise, die jemand leitet (Besitz oder Kreis-Admin), stehen dabei -- Nova soll Kreisleitungen beim
        // Fuehren ihres Kreises helfen (Josh, 24.10.2026)
        $kreise = [];
        foreach (Membership::find()->joinWith('space')->where(['space_membership.user_id' => $mensch->id, 'space_membership.status' => Membership::STATUS_MEMBER])
                     ->select(['space.name', 'space.created_by', 'space_membership.group_id'])->asArray()->all() as $z) {
            $leitet = (int)$z['created_by'] === (int)$mensch->id || $z['group_id'] === 'admin';
            $kreise[] = $z['name'] . ($leitet ? ' (du leitest diesen Kreis)' : '');
        }
        $merk = Merkpunkt::find()->where(['user_id' => $mensch->id])->orderBy('id')->select('text')->column();
        $sprache = substr((string)($mensch->language ?: Yii::$app->language), 0, 2);

        $teile = [
            'Du bist Nova (KI), die KI-Begleiterin der N.E.X.U.S.-Gemeinschaft auf ihrer Community-Plattform. '
            . 'Du fuehrst ein persoenliches Gespraech mit einem Mitglied. Du gibst dich nie als Mensch aus. Nenne die '
            . 'Plattform nie "HumHub", sondern "unsere Plattform". Sprache: Antworte in der Sprache, in der das Mitglied '
            . "schreibt (Portal-Sprache des Mitglieds: {$sprache}). Per Du, warm, klar, ohne Floskeln und ohne leere "
            . 'Marketingsprache. Schreibe "N.E.X.U.S." immer mit Punkten.',
            "=== GESPRAECHSART ===\n" . $art[1],
            "=== GRENZEN ===\n"
            . "- Keine medizinische, rechtliche oder finanzielle Beratung im Einzelfall -- verweise auf Fachleute. "
            . "Allgemeines Gesundheitswissen gibt es im Kreis 9 (Gesundheitswissen).\n"
            . "- Trenne klar: Was steht in den N.E.X.U.S.-Dokumenten, was ist deine eigene Einschaetzung, was ist "
            . "(noch) nicht entschieden. Erfinde keine Beschluesse, Zahlen oder Termine.\n"
            . "- Sprich respektvoll ueber Menschen ausserhalb und innerhalb der Gemeinschaft, auch ueber Kritiker.\n"
            . "- Bei Konto-Problemen, Konflikten zwischen Mitgliedern oder Wuenschen ans Team: verweise freundlich "
            . "auf das Support-Team bzw. die Kreis-Admins.\n"
            . "- Leitet das Mitglied einen Kreis, hilf ihm auch beim Fuehren: Treffen vorbereiten, Einladungen und Beitraege "
            . "formulieren, Projekte planen (Projekt-Tafel im Kreis), Gruppendynamik und Gruendungsschritte vor Ort.",
            "=== FORM ===\nMarkdown mit kurzen Absaetzen, Aufzaehlungen wo hilfreich. So lang wie noetig, so kurz wie "
            . 'moeglich. Ein Link nur, wenn du ihn aus den Unterlagen kennst.',
            "=== GEDAECHTNIS ===\nErzaehlt das Mitglied etwas Bleibendes ueber sich, das dir in spaeteren Gespraechen "
            . 'hilft (Rolle, Kreise, laufende Vorhaben, wie es angesprochen werden moechte), haenge am Ende eine eigene '
            . "Zeile an: [[MERKEN: kurzer Satz in der dritten Person]]. Hoechstens eine je Antwort, nichts doppelt. "
            . 'NIE merken: Gesundheit, Religion, politische Haltung, Finanzen, Adressen, Angaben ueber andere Menschen. '
            . 'Das Mitglied sieht diese Eintraege und kann sie loeschen.',
            "=== QUELLENANGABE ===\nHast du Treffer aus der Suche oder aus den Wissensbereichen verwendet, schreibe ganz "
            . 'am Ende eine eigene Zeile: [[QUELLEN: Titel 1 | Titel 2]] -- mit den Titeln genau so, wie sie bei den '
            . 'Treffern in eckigen Klammern stehen, und nur mit denen, die du wirklich benutzt hast. Das Mitglied sieht '
            . 'diese Zeile nicht, die Quellen erscheinen darunter als Liste.',
            "=== DAS MITGLIED ===\nName: {$mensch->displayName}\nKreise: " . ($kreise !== [] ? implode(', ', $kreise) : '–')
            . "\nWas du dir gemerkt hast:\n" . ($merk !== [] ? '- ' . implode("\n- ", $merk) : '(noch nichts)'),
        ];
        $handbuch = dirname(__DIR__) . '/knowledge/Portal_Handbuch.md';
        if (is_file($handbuch)) {
            $teile[] = "=== BEDIENUNG DER PLATTFORM (fuer Fragen wie \"Wo finde ich ...?\") ===\n" . trim((string)file_get_contents($handbuch));
        }
        return implode("\n\n", $teile);
    }

    /**
     * Treffer aus den Wissensbereichen des Portals (Modul nexus-wissen), die das Mitglied lesen darf.
     * @return array{0: string, 1: array}
     */
    private static function wissensbereiche(string $frage, User $mensch): array
    {
        $modul = 'nexus\modules\wissen\Module';
        if (!Yii::$app->hasModule('nexus-wissen') || !class_exists($modul) || ($wissen = $modul::instanz()) === null) {
            return ['', []];
        }
        $treffer = [];
        try {
            foreach ($wissen->alleSammlungen() as $sammlung) {
                if (!$sammlung->darfLesen($mensch)) {
                    continue;
                }
                foreach (array_slice(\nexus\modules\wissen\services\Suche::suche($sammlung, $frage), 0, 2) as $t) {
                    if ($t['punkte'] < self::WISSEN_MIN_PUNKTE) {
                        continue;
                    }
                    $treffer[] = [$t['punkte'], $sammlung, $t['artikel']];
                }
            }
        } catch (Throwable $e) {
            Yii::warning('nexus-community-assistant: Wissensbereiche nicht durchsuchbar: ' . $e->getMessage());
            return ['', []];
        }
        usort($treffer, fn($a, $b) => $b[0] <=> $a[0]);
        $text = [];
        $quellen = [];
        foreach (array_slice($treffer, 0, self::WISSEN_TREFFER) as [, $sammlung, $artikel]) {
            $voll = \nexus\modules\wissen\models\Artikel::findOne($artikel['id']);
            if ($voll === null) {
                continue;
            }
            $url = $sammlung->url('artikel', ['a' => $artikel['slug']], true);
            $text[] = "[{$sammlung->name()}: {$artikel['titel']}] ({$url})\n" . mb_substr((string)$voll->inhalt, 0, self::WISSEN_ZEICHEN);
            $quellen[] = ['titel' => $sammlung->name() . ': ' . $artikel['titel'], 'art' => 'wissen', 'url' => $url];
        }
        if ($text === []) {
            return ['', []];
        }
        return ["=== PASSENDE ARTIKEL AUS DEN WISSENSBEREICHEN DES PORTALS (nur nutzen, wenn sie zur Frage passen; "
            . "dann Titel und Link nennen) ===\n\n" . implode("\n\n---\n\n", $text), $quellen];
    }

    private static function merken(User $mensch, string $punkt): void
    {
        $punkt = mb_substr(trim($punkt), 0, Merkpunkt::MAX_LAENGE);
        if ($punkt === '' || Merkpunkt::find()->where(['user_id' => $mensch->id, 'text' => $punkt])->exists()) {
            return;
        }
        // Voll: der aelteste Eintrag macht Platz
        $alle = Merkpunkt::find()->where(['user_id' => $mensch->id])->orderBy('id')->all();
        while (count($alle) >= Merkpunkt::MAX_JE_MENSCH) {
            array_shift($alle)->delete();
        }
        (new Merkpunkt(['user_id' => $mensch->id, 'text' => $punkt, 'created_at' => date('Y-m-d H:i:s')]))->save(false);
    }
}
