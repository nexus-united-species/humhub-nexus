<?php

namespace nexus\modules\wissen\services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Macht aus einer hochgeladenen Datei einen Artikel-Entwurf (Markdown), den die Redaktion vor dem
 * Speichern ansieht und korrigiert.
 *
 * - .md / .txt: Text wird uebernommen; ein Kopf (Front Matter) liefert Titel und Schlagworte.
 * - .docx (Word): Ueberschriften, Absaetze, Aufzaehlungen, fett/kursiv, Links und Tabellen.
 *   Bilder kann der Text nicht tragen: An ihrer Stelle steht ein Hinweis zum Pruefen -- bei Word-
 *   Dateien standen schon App-Namen nur als Bild drin und fehlten nach der Umwandlung unbemerkt.
 * PDF bewusst nicht: Scans liefern keinen Text, und Seitenumbrueche zerreissen Saetze.
 */
class Hochladen
{
    public const ENDUNGEN = ['md', 'txt', 'docx'];
    public const MAX_BYTES = 10 * 1024 * 1024;
    private const MAX_XML_BYTES = 30 * 1024 * 1024;
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    public const BILD_HINWEIS = '> ⚠️ **Hier war ein Bild.** Bilder werden nicht übernommen – bitte prüfen, ob dort wichtiger Text (z. B. ein Name) stand, und ihn hier eintragen.';

    /**
     * @return array{titel: string, inhalt: string, schlagworte: string[], suchwoerter: string[], bilder: int}
     * @throws RuntimeException mit einer Meldung fuer den Menschen
     */
    public static function lesen(string $pfad, string $dateiname): array
    {
        $endung = strtolower(pathinfo($dateiname, PATHINFO_EXTENSION));
        if (!in_array($endung, self::ENDUNGEN, true)) {
            throw new RuntimeException('format');
        }
        if (filesize($pfad) > self::MAX_BYTES) {
            throw new RuntimeException('zu_gross');
        }
        $ersatzTitel = Sammlung::nameAusCode(pathinfo($dateiname, PATHINFO_FILENAME));
        if ($endung === 'docx') {
            [$inhalt, $bilder] = self::word($pfad);
            $meta = [];
        } else {
            $roh = (string)file_get_contents($pfad);
            if (!mb_check_encoding($roh, 'UTF-8')) {
                $roh = mb_convert_encoding($roh, 'UTF-8', 'Windows-1252');
            }
            [$meta, $inhalt] = Importer::frontMatter(str_replace("\r\n", "\n", $roh));
            $bilder = 0;
        }
        $inhalt = trim((string)preg_replace("/\n{3,}/", "\n\n", $inhalt));
        if ($inhalt === '') {
            throw new RuntimeException('leer');
        }
        $titel = trim((string)($meta['titel'] ?? '')) ?: (preg_match('/^#\s+(.+)$/m', $inhalt, $m) ? trim($m[1]) : $ersatzTitel);
        return [
            'titel' => mb_substr($titel, 0, 255),
            'inhalt' => $inhalt,
            'schlagworte' => self::liste($meta['schlagworte'] ?? ''),
            'suchwoerter' => self::liste($meta['suchwoerter'] ?? ''),
            'bilder' => $bilder,
        ];
    }

    /** @return array{0: string, 1: int} Markdown und Zahl der Bilder */
    private static function word(string $pfad): array
    {
        $zip = new ZipArchive();
        if ($zip->open($pfad) !== true) {
            throw new RuntimeException('kaputt');
        }
        try {
            $dokument = self::xml($zip, 'word/document.xml');
            if ($dokument === null) {
                throw new RuntimeException('kaputt');
            }
            $links = self::links($zip);
            $ueberschriften = self::ueberschriften($zip);
            $nummeriert = self::nummerierte($zip);
        } finally {
            $zip->close();
        }
        $xp = new DOMXPath($dokument);
        $xp->registerNamespace('w', self::W);
        $xp->registerNamespace('r', self::R);
        $body = $xp->query('/w:document/w:body')->item(0);
        if (!$body instanceof DOMElement) {
            throw new RuntimeException('kaputt');
        }
        $bilder = 0;
        $bloecke = [];
        foreach ($body->childNodes as $knoten) {
            if (!$knoten instanceof DOMElement) {
                continue;
            }
            if ($knoten->localName === 'p') {
                $bloecke[] = self::absatz($knoten, $xp, $links, $ueberschriften, $nummeriert, $bilder);
            } elseif ($knoten->localName === 'tbl') {
                $bloecke[] = "\n" . self::tabelle($knoten, $xp, $links, $bilder) . "\n";
            }
        }
        // Aufzaehlungspunkte stehen ohne Leerzeile untereinander, alles andere als eigener Absatz
        $text = '';
        foreach ($bloecke as $b) {
            if ($b === null) {
                continue;
            }
            $istPunkt = (bool)preg_match('/^\s*(-|\d+\.) /', $b);
            $text .= ($text === '' ? '' : ($istPunkt && preg_match('/(^|\n)\s*(-|\d+\.) [^\n]*$/', $text) ? "\n" : "\n\n")) . $b;
        }
        return [$text, $bilder];
    }

    private static function absatz(DOMElement $p, DOMXPath $xp, array $links, array $ueberschriften, array $nummeriert, int &$bilder): ?string
    {
        $text = self::laeufe($p, $xp, $links, $bilder);
        $stil = (string)$xp->evaluate('string(w:pPr/w:pStyle/@w:val)', $p);
        $ebene = $ueberschriften[$stil] ?? 0;
        if (trim($text) === '') {
            return null;
        }
        if ($ebene > 0) {
            return str_repeat('#', min($ebene, 4)) . ' ' . trim(str_replace(['**', '*'], '', $text));
        }
        $numId = (string)$xp->evaluate('string(w:pPr/w:numPr/w:numId/@w:val)', $p);
        if ($numId !== '' && $numId !== '0') {
            $ilvl = (int)$xp->evaluate('string(w:pPr/w:numPr/w:ilvl/@w:val)', $p);
            $zeichen = isset($nummeriert[$numId . '|' . $ilvl]) ? '1.' : '-';
            return str_repeat('  ', min($ilvl, 4)) . $zeichen . ' ' . trim($text);
        }
        return trim($text);
    }

    private static function laeufe(DOMElement $eltern, DOMXPath $xp, array $links, int &$bilder): string
    {
        $text = '';
        foreach ($xp->query('w:r | w:hyperlink | w:ins | w:smartTag | w:fldSimple', $eltern) as $teil) {
            if (!$teil instanceof DOMElement) {
                continue;
            }
            if ($teil->localName !== 'r') {
                $innen = self::laeufe($teil, $xp, $links, $bilder);
                $ziel = $teil->localName === 'hyperlink' ? ($links[$teil->getAttributeNS(self::R, 'id')] ?? '') : '';
                $text .= $ziel !== '' && trim($innen) !== '' && preg_match('#^(https?://|mailto:)#i', $ziel)
                    ? '[' . trim(str_replace(['[', ']'], '', $innen)) . '](' . str_replace([' ', ')'], ['%20', '%29'], $ziel) . ')'
                    : $innen;
                continue;
            }
            if ($xp->query('w:drawing | w:pict | w:object', $teil)->length > 0) {
                $bilder++;
                $text .= "\n\n" . self::BILD_HINWEIS . "\n\n";
                continue;
            }
            $stueck = '';
            foreach ($teil->childNodes as $k) {
                if (!$k instanceof DOMElement) {
                    continue;
                }
                $stueck .= match ($k->localName) {
                    't' => $k->textContent,
                    'tab' => ' ',
                    'br', 'cr' => "\n",
                    default => '',
                };
            }
            if (trim($stueck) === '') {
                $text .= $stueck;
                continue;
            }
            $fett = self::an($xp, 'w:rPr/w:b', $teil);
            $kursiv = self::an($xp, 'w:rPr/w:i', $teil);
            $marke = ($fett ? '**' : '') . ($kursiv ? '*' : '');
            // Leerzeichen gehoeren vor/hinter die Markierung, sonst erkennt Markdown sie nicht
            preg_match('/^(\s*)(.*?)(\s*)$/su', $stueck, $m);
            $text .= $m[1] . $marke . $m[2] . strrev($marke) . $m[3];
        }
        // Word teilt fetten Text oft in mehrere Stuecke: "**Halb****satz**" -> "**Halbsatz**",
        // "**zwei** **Woerter**" -> "**zwei Woerter**"
        return (string)preg_replace('/(?<!\*)\*\*([ \t]*)\*\*(?!\*)/u', '$1', $text);
    }

    private static function an(DOMXPath $xp, string $pfad, DOMElement $r): bool
    {
        $k = $xp->query($pfad, $r)->item(0);
        if (!$k instanceof DOMElement) {
            return false;
        }
        $wert = $k->getAttributeNS(self::W, 'val');
        return !in_array($wert, ['0', 'false', 'none'], true);
    }

    private static function tabelle(DOMElement $tbl, DOMXPath $xp, array $links, int &$bilder): string
    {
        $zeilen = [];
        foreach ($xp->query('w:tr', $tbl) as $tr) {
            $zellen = [];
            foreach ($xp->query('w:tc', $tr) as $tc) {
                $teile = [];
                foreach ($xp->query('w:p', $tc) as $p) {
                    $teile[] = trim(self::laeufe($p, $xp, $links, $bilder));
                }
                $zellen[] = str_replace(['|', "\n"], ['\\|', ' '], trim(implode(' ', array_filter($teile))));
            }
            $zeilen[] = $zellen;
        }
        if ($zeilen === []) {
            return '';
        }
        $breite = max(array_map('count', $zeilen));
        $md = [];
        foreach ($zeilen as $i => $z) {
            $md[] = '| ' . implode(' | ', array_pad($z, $breite, '')) . ' |';
            if ($i === 0) {
                $md[] = '|' . str_repeat(' --- |', $breite);
            }
        }
        return implode("\n", $md);
    }

    private static function xml(ZipArchive $zip, string $name): ?DOMDocument
    {
        $info = $zip->statName($name);
        if ($info === false || $info['size'] > self::MAX_XML_BYTES) {
            return null;
        }
        $doc = new DOMDocument();
        $alt = libxml_use_internal_errors(true);
        $ok = $doc->loadXML((string)$zip->getFromName($name), LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($alt);
        return $ok ? $doc : null;
    }

    /** @return array<string, string> Beziehungs-ID => Link-Ziel */
    private static function links(ZipArchive $zip): array
    {
        $doc = self::xml($zip, 'word/_rels/document.xml.rels');
        $liste = [];
        if ($doc !== null) {
            foreach ($doc->getElementsByTagName('Relationship') as $rel) {
                if (str_ends_with((string)$rel->getAttribute('Type'), '/hyperlink')) {
                    $liste[(string)$rel->getAttribute('Id')] = (string)$rel->getAttribute('Target');
                }
            }
        }
        return $liste;
    }

    /** @return array<string, int> Stil-ID => Ueberschriftsebene ("Heading2", "berschrift2", "Title" ...) */
    private static function ueberschriften(ZipArchive $zip): array
    {
        $liste = [];
        $doc = self::xml($zip, 'word/styles.xml');
        if ($doc !== null) {
            $xp = new DOMXPath($doc);
            $xp->registerNamespace('w', self::W);
            foreach ($xp->query('//w:style[@w:type="paragraph"]') as $stil) {
                $name = strtolower((string)$xp->evaluate('string(w:name/@w:val)', $stil));
                $id = $stil->getAttributeNS(self::W, 'styleId');
                if ($name === 'title') {
                    $liste[$id] = 1;
                } elseif (preg_match('/^heading (\d)$/', $name, $m)) {
                    // Ueberschrift 1 wird "##": "#" ist der Artikeltitel
                    $liste[$id] = min(4, (int)$m[1] + 1);
                }
            }
        }
        return $liste;
    }

    /** @return array<string, true> "numId|Ebene" fuer nummerierte (nicht Punkt-) Listen */
    private static function nummerierte(ZipArchive $zip): array
    {
        $doc = self::xml($zip, 'word/numbering.xml');
        if ($doc === null) {
            return [];
        }
        $xp = new DOMXPath($doc);
        $xp->registerNamespace('w', self::W);
        $abstrakt = [];
        foreach ($xp->query('//w:abstractNum') as $a) {
            foreach ($xp->query('w:lvl', $a) as $lvl) {
                $format = (string)$xp->evaluate('string(w:numFmt/@w:val)', $lvl);
                if ($format !== '' && !in_array($format, ['bullet', 'none'], true)) {
                    $abstrakt[$a->getAttributeNS(self::W, 'abstractNumId') . '|' . $lvl->getAttributeNS(self::W, 'ilvl')] = true;
                }
            }
        }
        $liste = [];
        foreach ($xp->query('//w:num') as $num) {
            $aid = (string)$xp->evaluate('string(w:abstractNumId/@w:val)', $num);
            for ($ebene = 0; $ebene <= 8; $ebene++) {
                if (isset($abstrakt[$aid . '|' . $ebene])) {
                    $liste[$num->getAttributeNS(self::W, 'numId') . '|' . $ebene] = true;
                }
            }
        }
        return $liste;
    }

    /** "[a, b]", "a, b" -> ['a', 'b'] */
    private static function liste($wert): array
    {
        $teile = is_array($wert) ? $wert : explode(',', trim((string)$wert, '[] '));
        return array_values(array_filter(array_map(fn($w) => trim((string)$w, " \t\"'"), $teile), fn($w) => $w !== ''));
    }
}
