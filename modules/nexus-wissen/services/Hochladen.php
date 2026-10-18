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
 *   Bilder (PNG, JPEG, GIF, WebP) werden in die Sammlung uebernommen (services/Bilder.php). Was nicht
 *   geht (EMF-Zeichnungen, Diagramme), bekommt einen Hinweis zum Pruefen -- bei Word-Dateien standen
 *   schon App-Namen nur als Bild drin und fehlten nach der Umwandlung unbemerkt.
 * PDF bewusst nicht: Scans liefern keinen Text, und Seitenumbrueche zerreissen Saetze.
 */
class Hochladen
{
    public const ENDUNGEN = ['md', 'txt', 'docx'];
    /** Word-Dateien mit Bildern werden schnell gross */
    public const MAX_BYTES = 30 * 1024 * 1024;
    private const MAX_XML_BYTES = 30 * 1024 * 1024;
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const A = 'http://schemas.openxmlformats.org/drawingml/2006/main';
    private const WP = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';
    private const V = 'urn:schemas-microsoft-com:vml';
    public const BILD_HINWEIS = '> ⚠️ **Hier war ein Bild, das nicht übernommen werden konnte** (z. B. eine Zeichnung oder ein Diagramm aus Word). Bitte prüfen: Fehlt etwas Wichtiges, das Bild über den Bild-Knopf neu einfügen – oder den Inhalt hier als Text eintragen.';
    /** Schriften fuer Befehle und Programmcode -- so gesetzter Text wird zu Markdown-Code */
    private const CODE_SCHRIFTEN = ['courier', 'consolas', 'lucida console', 'source code', 'dejavu sans mono', 'liberation mono',
        'menlo', 'monaco', 'cascadia', 'fira code', 'fira mono', 'jetbrains mono', 'ubuntu mono', 'roboto mono', 'noto mono', 'andale mono'];
    /** Markiert einen Code-Absatz zwischen absatz() und word() -- kommt in echtem Text nicht vor */
    private const CODE = "\x01CODE\x01";

    /**
     * @param Sammlung|null $sammlung wohin Bilder aus Word-Dateien gespeichert werden (ohne: nur Hinweis im Text)
     * @return array{titel: string, inhalt: string, schlagworte: string[], suchwoerter: string[], bilder: int, bilderOk: int}
     *         bilder = nicht uebernommene Bilder, bilderOk = uebernommene
     * @throws RuntimeException mit einer Meldung fuer den Menschen
     */
    public static function lesen(string $pfad, string $dateiname, ?Sammlung $sammlung = null): array
    {
        $bilderOk = 0;
        $endung = strtolower(pathinfo($dateiname, PATHINFO_EXTENSION));
        if (!in_array($endung, self::ENDUNGEN, true)) {
            throw new RuntimeException('format');
        }
        if (filesize($pfad) > self::MAX_BYTES) {
            throw new RuntimeException('zu_gross');
        }
        $ersatzTitel = Sammlung::nameAusCode(pathinfo($dateiname, PATHINFO_FILENAME));
        if ($endung === 'docx') {
            [$inhalt, $bilder, $bilderOk] = self::word($pfad, $sammlung);
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
            'bilderOk' => $bilderOk,
        ];
    }

    /** @return array{0: string, 1: int, 2: int} Markdown, nicht uebernommene und uebernommene Bilder */
    private static function word(string $pfad, ?Sammlung $sammlung): array
    {
        $zip = new ZipArchive();
        if ($zip->open($pfad) !== true) {
            throw new RuntimeException('kaputt');
        }
        // Offen bis zum Ende: eingebettete Bilder werden beim Durchgehen aus dem Archiv gelesen
        try {
            $dokument = self::xml($zip, 'word/document.xml');
            if ($dokument === null) {
                throw new RuntimeException('kaputt');
            }
            $k = new WordKontext(self::links($zip), self::stile($zip), self::nummerierte($zip), self::bildZiele($zip), $zip, $sammlung);
            $k->xp = new DOMXPath($dokument);
            $k->xp->registerNamespace('w', self::W);
            $k->xp->registerNamespace('r', self::R);
            $k->xp->registerNamespace('a', self::A);
            $k->xp->registerNamespace('wp', self::WP);
            $k->xp->registerNamespace('v', self::V);
            $body = $k->xp->query('/w:document/w:body')->item(0);
            if (!$body instanceof DOMElement) {
                throw new RuntimeException('kaputt');
            }
            $bloecke = [];
            self::bloecke($body, $k, $bloecke);
            return [self::zusammensetzen($bloecke), $k->bilder, $k->bilderOk];
        } finally {
            $zip->close();
        }
    }

    /** @return array<string, string> Beziehungs-ID => Pfad des Bildes im Archiv ("word/media/image1.png") */
    private static function bildZiele(ZipArchive $zip): array
    {
        $doc = self::xml($zip, 'word/_rels/document.xml.rels');
        $liste = [];
        if ($doc === null) {
            return [];
        }
        foreach ($doc->getElementsByTagName('Relationship') as $rel) {
            $ziel = (string)$rel->getAttribute('Target');
            if (!str_ends_with((string)$rel->getAttribute('Type'), '/image') || $rel->getAttribute('TargetMode') === 'External') {
                continue;
            }
            // Relativ zu word/ ("media/image1.png") oder absolut im Archiv ("/word/media/image1.png")
            $pfad = str_starts_with($ziel, '/') ? ltrim($ziel, '/') : 'word/' . $ziel;
            $teile = [];
            foreach (explode('/', $pfad) as $t) {
                if ($t === '..') {
                    array_pop($teile);
                } elseif ($t !== '' && $t !== '.') {
                    $teile[] = $t;
                }
            }
            $liste[(string)$rel->getAttribute('Id')] = implode('/', $teile);
        }
        return $liste;
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
            // Neuere Word-Fassungen packen Bilder in mc:AlternateContent -- deshalb ueberall im Lauf suchen
            if ($xp->query('.//w:drawing | .//w:pict | .//w:object', $teil)->length > 0) {
                $text .= self::bilderImLauf($teil, $k);
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

    /**
     * Bilder eines Laufs uebernehmen. Moderne Bilder (a:blip) zuerst; nur wenn es keine gibt, die alte
     * Form (v:imagedata) -- Word legt oft beide fuer dasselbe Bild an (Choice/Fallback).
     * Was nicht geht (z. B. EMF-Zeichnungen, Diagramme), bekommt den gelben Hinweis.
     */
    private static function bilderImLauf(DOMElement $r, WordKontext $k): string
    {
        $xp = $k->xp;
        $ids = [];
        foreach ($xp->query('.//a:blip/@r:embed', $r) as $attr) {
            $ids[$attr->nodeValue] = true;
        }
        if ($ids === []) {
            foreach ($xp->query('.//v:imagedata/@r:id', $r) as $attr) {
                $ids[$attr->nodeValue] = true;
            }
        }
        $beschreibung = trim((string)$xp->evaluate('string(.//wp:docPr/@descr)', $r))
            ?: trim((string)$xp->evaluate('string(.//wp:docPr/@title)', $r));
        $teile = [];
        foreach (array_keys($ids) as $id) {
            $md = $k->bild((string)$id, $beschreibung);
            if ($md !== null) {
                $teile[] = $md;
            }
        }
        if ($teile === []) {
            $k->bilder++;
            return "\n\n" . self::BILD_HINWEIS . "\n\n";
        }
        return "\n\n" . implode("\n\n", $teile) . "\n\n";
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
