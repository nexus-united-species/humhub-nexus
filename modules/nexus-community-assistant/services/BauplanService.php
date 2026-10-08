<?php

namespace nexus\modules\communityAssistant\services;

/**
 * Der volle Bauplan ist ~400 Seiten (~850 KB als Markdown) -- viel zu
 * gross, um ihn bei JEDER Frage komplett mitzuschicken (teuer, langsam,
 * und Josh wollte ausdrücklich keine vollwertige Second-Brain-Anbindung,
 * siehe Rueckmeldung 09.09.2026: "2. finde ich zu umfangreich").
 *
 * Deshalb ein einfacher Mittelweg statt echtem Retrieval mit Embeddings:
 * - Eine kleine, immer mitgegebene Uebersicht (Vorwort, Kurzfassung,
 *   Glossar -- zusammen ~45 KB).
 * - Die 11 inhaltlichen Kapitel werden NUR bei Bedarf dazugeladen: ein
 *   simpler Woerter-Abgleich zwischen der gestellten Frage und jedem
 *   Kapiteltext waehlt die 1-2 am besten passenden Kapitel aus.
 *
 * Das ist bewusst kein "echtes" RAG (keine Embeddings, kein Vektor-Index)
 * -- bei nur 11 Kapiteln reicht ein einfacher Treffervergleich, und es
 * bleibt so einfach wartbar wie der Rest des Moduls.
 */
class BauplanService
{
    private const UEBERSICHT_DATEIEN = [
        'kapitel_00_vorwort.md',
        'kapitel_00b_abstract_summary.md',
        'anhang_glossar.md',
    ];

    /** @var array<string, string> Dateiname => kurze Themenbeschreibung fuers Matching */
    private const KAPITEL = [
        'kapitel_01.md' => 'Systemkrise, warum die heutige Welt zerbricht, Ausgangslage, Problemanalyse',
        'kapitel_02.md' => 'Gesellschaftsarchitektur, Werte, Menschenbild, Grundprinzipien, Ethik',
        'kapitel_03.md' => 'Physische Infrastruktur, Internet, Mesh-Netzwerk, Allmende, Technik-Souveraenitaet',
        'kapitel_04.md' => 'Digitales Betriebssystem, OneApp-Technologie, Software-Architektur, Datenhoheit',
        'kapitel_05.md' => 'Oekonomie, AETHER-Protokoll, VITA, TERRA, AURA, Waehrung, Wertschoepfung, Demurrage',
        'kapitel_06.md' => 'Governance, Liquid Democracy, Konsent, Abstimmungen, Schwarmintelligenz, Zellen',
        'kapitel_07.md' => 'Lebensbereiche, Wohnen, Gesundheit, Bildung, Gerechtigkeit, soziale Sicherung',
        'kapitel_08.md' => 'Sicherheit, Verteidigung, Schutz des Netzwerks, Bedrohungsanalyse',
        'kapitel_09.md' => 'Roadmap, Stufenplan, Umsetzung, aktueller Stand der OneApp',
        'kapitel_10.md' => 'Risikoanalyse, warum das Projekt nicht scheitern wird, Gegenargumente',
        'kapitel_11.md' => 'Mitmachen, Gruendung, erste Kohorte, Aufruf zum Handeln, Pioniere werden',
    ];

    private const MAX_ZEICHEN_JE_KAPITEL = 20000;
    private const ANZAHL_KAPITEL = 2;

    public static function uebersicht(): string
    {
        $ordner = self::ordner();
        $teile = [];

        foreach (self::UEBERSICHT_DATEIEN as $datei) {
            $inhalt = self::datei($ordner, $datei);
            if ($inhalt !== null) {
                $teile[] = "=== BAUPLAN: {$datei} ===\n{$inhalt}";
            }
        }

        return implode("\n\n", $teile);
    }

    /**
     * Waehlt die zur Frage passendsten Bauplan-Kapitel aus und gibt deren
     * (ggf. gekuerzten) Text zurueck. Rein wortbasiert -- kein echtes
     * Sprachverstehen, aber fuer die ueberschaubare Anzahl von elf klar
     * getrennten Kapiteln ausreichend treffsicher.
     */
    public static function relevanteKapitel(string $frage, int $anzahl = self::ANZAHL_KAPITEL): string
    {
        $stichworte = self::stichworteAus($frage);
        if (empty($stichworte)) {
            return '';
        }

        $treffer = [];
        foreach (self::KAPITEL as $datei => $themenbeschreibung) {
            $punkte = 0;
            $themenWorte = self::stichworteAus($themenbeschreibung);
            foreach ($stichworte as $wort) {
                if (in_array($wort, $themenWorte, true)) {
                    $punkte += 3; // Treffer in der Themenbeschreibung zaehlt mehr
                }
            }
            if ($punkte > 0) {
                $treffer[$datei] = $punkte;
            }
        }

        if (empty($treffer)) {
            return '';
        }

        arsort($treffer);
        $ausgewaehlt = array_slice(array_keys($treffer), 0, $anzahl);

        $ordner = self::ordner();
        $teile = [];
        foreach ($ausgewaehlt as $datei) {
            $inhalt = self::datei($ordner, $datei);
            if ($inhalt === null) {
                continue;
            }
            if (mb_strlen($inhalt) > self::MAX_ZEICHEN_JE_KAPITEL) {
                $inhalt = mb_substr($inhalt, 0, self::MAX_ZEICHEN_JE_KAPITEL)
                    . "\n[... Kapitel gekuerzt, Rest ausgelassen ...]";
            }
            $teile[] = "=== BAUPLAN: {$datei} ===\n{$inhalt}";
        }

        return implode("\n\n", $teile);
    }

    /**
     * @return string[]
     */
    private static function stichworteAus(string $text): array
    {
        $text = mb_strtolower($text);
        $woerter = preg_split('/[^\p{L}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Kurze Fuellwoerter bringen beim Abgleich nichts und wuerden nur
        // zufaellige Treffer erzeugen.
        return array_values(array_filter($woerter, fn($w) => mb_strlen($w) >= 4));
    }

    private static function ordner(): string
    {
        return dirname(__DIR__) . '/knowledge/bauplan';
    }

    private static function datei(string $ordner, string $name): ?string
    {
        $pfad = $ordner . '/' . $name;
        if (!is_file($pfad)) {
            return null;
        }

        $roh = (string)file_get_contents($pfad);
        $inhalt = mb_check_encoding($roh, 'UTF-8') ? $roh : @mb_convert_encoding($roh, 'UTF-8', 'Windows-1252');

        return trim((string)$inhalt) ?: null;
    }
}
