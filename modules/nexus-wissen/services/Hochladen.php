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
 * - .docx (Word): Ueberschriften, Absaetze, Aufzaehlungen, fett/kursiv, Links, Tabellen und Code
 *   (Absaetze oder Woerter in Schreibmaschinenschrift wie Consolas/Courier).
 *   Ueberschriften und Listen werden auch erkannt, wenn sie aus eigenen Formatvorlagen kommen
 *   (Gliederungsebene, "basiert auf", Nummerierung in der Formatvorlage).
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
    /** Schriften fuer Befehle und Programmcode -- so gesetzter Text wird zu Markdown-Code */
    private const CODE_SCHRIFTEN = ['courier', 'consolas', 'lucida console', 'source code', 'dejavu sans mono', 'liberation mono',
        'menlo', 'monaco', 'cascadia', 'fira code', 'fira mono', 'jetbrains mono', 'ubuntu mono', 'roboto mono', 'noto mono', 'andale mono'];
    /** Markiert einen Code-Absatz zwischen absatz() und word() -- kommt in echtem Text nicht vor */
    private const CODE = "\x01CODE\x01";

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
            $k = new WordKontext(self::links($zip), self::stile($zip), self::nummerierte($zip));
        } finally {
            $zip->close();
        }
        $k->xp = new DOMXPath($dokument);
        $k->xp->registerNamespace('w', self::W);
        $k->xp->registerNamespace('r', self::R);
        $body = $k->xp->query('/w:document/w:body')->item(0);
        if (!$body instanceof DOMElement) {
            throw new RuntimeException('kaputt');
        }
        $bloecke = [];
        self::bloecke($body, $k, $bloecke);
        return [self::zusammensetzen($bloecke), $k->bilder];
    }

    /** Absaetze und Tabellen in Reihenfolge -- auch innerhalb von Inhaltssteuerelementen (w:sdt) */
    private static function bloecke(DOMElement $eltern, WordKontext $k, array &$bloecke): void
    {
        foreach ($eltern->childNodes as $knoten) {
            if (!$knoten instanceof DOMElement) {
                continue;
            }
            match ($knoten->localName) {
                'p' => $bloecke[] = self::absatz($knoten, $k),
                'tbl' => $bloecke[] = "\n" . self::tabelle($knoten, $k) . "\n",
                'sdt', 'sdtContent', 'customXml', 'ins' => self::bloecke($knoten, $k, $bloecke),
                default => null,
            };
        }
    }

    /** Aufzaehlungspunkte ohne Leerzeile untereinander, Code-Absaetze in einen Block, sonst Absaetze */
    private static function zusammensetzen(array $bloecke): string
    {
        $text = '';
        $code = [];
        $anhaengen = function (string $b) use (&$text): void {
            $istPunkt = (bool)preg_match('/^\s*(-|\d+\.) /', $b);
            $text .= ($text === '' ? '' : ($istPunkt && preg_match('/(^|\n)\s*(-|\d+\.) [^\n]*$/', $text) ? "\n" : "\n\n")) . $b;
        };
        foreach ($bloecke as $b) {
            if ($b === null) {
                continue;
            }
            if (str_starts_with($b, self::CODE)) {
                $code[] = substr($b, strlen(self::CODE));
                continue;
            }
            if ($code !== []) {
                $anhaengen("```\n" . implode("\n", $code) . "\n```");
                $code = [];
            }
            $anhaengen($b);
        }
        if ($code !== []) {
            $anhaengen("```\n" . implode("\n", $code) . "\n```");
        }
        return $text;
    }

    private static function absatz(DOMElement $p, WordKontext $k): ?string
    {
        $xp = $k->xp;
        $stil = $k->stil((string)$xp->evaluate('string(w:pPr/w:pStyle/@w:val)', $p));
        if ($stil['code'] || self::nurCodeSchrift($p, $k)) {
            $roh = rtrim(self::rohtext($p, $xp), "\n");
            return trim($roh) === '' ? null : self::CODE . $roh;
        }
        $text = self::laeufe($p, $k);
        if (trim($text) === '') {
            return null;
        }
        // Gliederungsebene direkt am Absatz geht der Formatvorlage vor
        $gliederung = (string)$xp->evaluate('string(w:pPr/w:outlineLvl/@w:val)', $p);
        $ebene = $gliederung !== '' && (int)$gliederung < 9 ? min(4, (int)$gliederung + 2) : $stil['ebene'];
        if ($ebene > 0) {
            return str_repeat('#', min($ebene, 4)) . ' ' . trim(str_replace(['**', '*', '`'], '', $text));
        }
        $numId = (string)$xp->evaluate('string(w:pPr/w:numPr/w:numId/@w:val)', $p);
        $ilvl = (string)$xp->evaluate('string(w:pPr/w:numPr/w:ilvl/@w:val)', $p);
        if ($numId === '') {
            $numId = $stil['numId'];
        }
        if ($ilvl === '') {
            $ilvl = $stil['ilvl'];
        }
        if ($numId !== '' && $numId !== '0') {
            $zeichen = isset($k->nummeriert[$numId . '|' . (int)$ilvl]) ? '1.' : '-';
            return str_repeat('   ', min((int)$ilvl, 4)) . $zeichen . ' ' . trim($text);
        }
        return trim($text);
    }

    private static function laeufe(DOMElement $eltern, WordKontext $k): string
    {
        $xp = $k->xp;
        $text = '';
        foreach ($xp->query('w:r | w:hyperlink | w:ins | w:smartTag | w:fldSimple | w:sdt/w:sdtContent', $eltern) as $teil) {
            if (!$teil instanceof DOMElement) {
                continue;
            }
            if ($teil->localName !== 'r') {
                $innen = self::laeufe($teil, $k);
                $ziel = $teil->localName === 'hyperlink' ? ($k->links[$teil->getAttributeNS(self::R, 'id')] ?? '') : '';
                $text .= $ziel !== '' && trim($innen) !== '' && preg_match('#^(https?://|mailto:)#i', $ziel)
                    ? '[' . trim(str_replace(['[', ']'], '', $innen)) . '](' . str_replace([' ', ')'], ['%20', '%29'], $ziel) . ')'
                    : $innen;
                continue;
            }
            if ($xp->query('w:drawing | w:pict | w:object', $teil)->length > 0) {
                $k->bilder++;
                $text .= "\n\n" . self::BILD_HINWEIS . "\n\n";
                continue;
            }
            $stueck = self::lauftext($teil);
            if (trim($stueck) === '') {
                $text .= $stueck;
                continue;
            }
            preg_match('/^(\s*)(.*?)(\s*)$/su', $stueck, $m);
            if (self::istCodeSchrift($teil, $xp)) {
                // Befehl mitten im Satz: `sudo apt update`
                $text .= $m[1] . '`' . str_replace('`', "'", $m[2]) . '`' . $m[3];
                continue;
            }
            $fett = self::an($xp, 'w:rPr/w:b', $teil);
            $kursiv = self::an($xp, 'w:rPr/w:i', $teil);
            $marke = ($fett ? '**' : '') . ($kursiv ? '*' : '');
            // Leerzeichen gehoeren vor/hinter die Markierung, sonst erkennt Markdown sie nicht
            $text .= $m[1] . $marke . $m[2] . strrev($marke) . $m[3];
        }
        // Word teilt Text oft in mehrere Stuecke: "**Halb****satz**" -> "**Halbsatz**",
        // "**zwei** **Woerter**" -> "**zwei Woerter**", "`sudo``apt`" -> "`sudoapt`"
        $text = (string)preg_replace('/(?<!\*)\*\*([ \t]*)\*\*(?!\*)/u', '$1', $text);
        $text = (string)preg_replace('/`([ \t]*)`/u', '$1', $text);
        // Tabs im Fliesstext: ein Leerzeichen -- am Zeilenanfang machte Markdown sonst einen Code-Block daraus
        return str_replace("\t", ' ', $text);
    }

    /** Text eines Laufs. Bindestriche, die Word als eigenes Zeichen speichert, gehoeren dazu ("ISO-Image"). */
    private static function lauftext(DOMElement $r): string
    {
        $stueck = '';
        foreach ($r->childNodes as $k) {
            if (!$k instanceof DOMElement) {
                continue;
            }
            $stueck .= match ($k->localName) {
                't' => $k->textContent,
                'tab' => "\t",
                'br', 'cr' => "\n",
                'noBreakHyphen' => '-',
                'softHyphen' => '',
                default => '',
            };
        }
        return str_replace(["\u{00A0}", "\u{2011}"], [' ', '-'], $stueck);
    }

    /** Ein Code-Absatz unveraendert: Tabs und Zeilenumbrueche bleiben, keine Markdown-Zeichen. */
    private static function rohtext(DOMElement $p, DOMXPath $xp): string
    {
        $text = '';
        foreach ($xp->query('.//w:r', $p) as $r) {
            $text .= self::lauftext($r);
        }
        return str_replace("\t", '    ', $text);
    }

    private static function istCodeSchrift(DOMElement $r, DOMXPath $xp): bool
    {
        $schrift = strtolower((string)$xp->evaluate('string(w:rPr/w:rFonts/@w:ascii)', $r)
            ?: (string)$xp->evaluate('string(w:rPr/w:rFonts/@w:hAnsi)', $r));
        if ($schrift === '') {
            return false;
        }
        foreach (self::CODE_SCHRIFTEN as $s) {
            if (str_contains($schrift, $s)) {
                return true;
            }
        }
        return false;
    }

    /** Ein Absatz, dessen gesamter Text in Schreibmaschinenschrift steht, ist ein Befehl / Code. */
    private static function nurCodeSchrift(DOMElement $p, WordKontext $k): bool
    {
        $mitText = 0;
        foreach ($k->xp->query('.//w:r', $p) as $r) {
            if (trim(self::lauftext($r)) === '') {
                continue;
            }
            if (!self::istCodeSchrift($r, $k->xp)) {
                return false;
            }
            $mitText++;
        }
        return $mitText > 0;
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

    private static function tabelle(DOMElement $tbl, WordKontext $k): string
    {
        $zeilen = [];
        foreach ($k->xp->query('w:tr', $tbl) as $tr) {
            $zellen = [];
            foreach ($k->xp->query('w:tc | w:sdt/w:sdtContent/w:tc', $tr) as $tc) {
                $teile = [];
                foreach ($k->xp->query('.//w:p', $tc) as $p) {
                    $teile[] = trim(str_replace("\t", ' ', self::laeufe($p, $k)));
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

    /**
     * Absatz-Formatvorlagen: Name, "basiert auf", Gliederungsebene, Nummerierung.
     * @return array<string, array{name: string, basis: string, gliederung: string, numId: string, ilvl: string}>
     */
    private static function stile(ZipArchive $zip): array
    {
        $liste = [];
        $doc = self::xml($zip, 'word/styles.xml');
        if ($doc === null) {
            return [];
        }
        $xp = new DOMXPath($doc);
        $xp->registerNamespace('w', self::W);
        foreach ($xp->query('//w:style[@w:type="paragraph"]') as $stil) {
            $liste[$stil->getAttributeNS(self::W, 'styleId')] = [
                'name' => strtolower((string)$xp->evaluate('string(w:name/@w:val)', $stil)),
                'basis' => (string)$xp->evaluate('string(w:basedOn/@w:val)', $stil),
                'gliederung' => (string)$xp->evaluate('string(w:pPr/w:outlineLvl/@w:val)', $stil),
                'numId' => (string)$xp->evaluate('string(w:pPr/w:numPr/w:numId/@w:val)', $stil),
                'ilvl' => (string)$xp->evaluate('string(w:pPr/w:numPr/w:ilvl/@w:val)', $stil),
            ];
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
