<?php

namespace nexus\modules\protectedLibrary\services;

use DOMDocument;
use DOMXPath;
use nexus\modules\protectedLibrary\models\Book;
use nexus\modules\protectedLibrary\models\Chapter;
use RuntimeException;
use ZipArchive;

/**
 * Liest eine EPUB-Datei (ein ZIP-Archiv mit XHTML-Kapiteln) ein und
 * speichert Titel/Kapitel als eigene Datensaetze -- die Original-EPUB
 * selbst wird an keiner Stelle gespeichert oder verlinkt. Bilder werden
 * direkt als Data-URIs in den Kapiteltext eingebettet, damit es keine
 * eigenstaendig abrufbare Bilddatei gibt, die man verlinken/herunterladen
 * koennte.
 *
 * Bewusst ein eigener, kleiner Parser statt einer Composer-Bibliothek --
 * EPUB ist im Kern nur ZIP + XML, das braucht keine grosse Abhaengigkeit.
 */
class EpubImportService
{
    private const XHTML_NS = 'http://www.w3.org/1999/xhtml';
    private const OPF_NS = 'http://www.idpf.org/2007/opf';
    private const DC_NS = 'http://purl.org/dc/elements/1.1/';
    private const CONTAINER_NS = 'urn:oasis:names:tc:opendocument:xmlns:container';

    public function importieren(
        string $epubPfad,
        ?string $titelUeberschreiben = null,
        ?string $autorUeberschreiben = null,
        string $sprache = Book::SPRACHE_DE,
        string $mediaType = Book::TYP_ROMAN
    ): Book {
        if (!is_file($epubPfad)) {
            throw new RuntimeException("Datei nicht gefunden: {$epubPfad}");
        }

        $zip = new ZipArchive();
        if ($zip->open($epubPfad) !== true) {
            throw new RuntimeException("EPUB konnte nicht geoeffnet werden: {$epubPfad}");
        }

        try {
            $opfPfad = $this->opfPfadFinden($zip);
            $opfBasis = dirname($opfPfad);
            if ($opfBasis === '.') {
                $opfBasis = '';
            }

            $opfXml = $this->xmlLaden($zip, $opfPfad, "OPF-Datei ({$opfPfad})");
            [$titel, $autor] = $this->metadatenLesen($opfXml);
            [$manifest, $spineIds] = $this->manifestUndSpineLesen($opfXml);

            $buch = new Book([
                'title' => $titelUeberschreiben ?: ($titel ?: basename($epubPfad)),
                'author' => $autorUeberschreiben ?: $autor,
                'language' => $sprache,
                'media_type' => $mediaType,
                'active' => true,
            ]);
            if (!$buch->save()) {
                throw new RuntimeException('Buch konnte nicht angelegt werden: ' . json_encode($buch->errors));
            }

            $reihenfolge = 0;
            foreach ($spineIds as $itemId) {
                if (!isset($manifest[$itemId])) {
                    continue;
                }
                $item = $manifest[$itemId];

                if (!str_contains($item['mediaType'], 'html')) {
                    continue;
                }
                if (str_contains($item['properties'] ?? '', 'nav')) {
                    continue; // die EPUB-eigene Navigationsdatei -- wir bauen eine eigene Kapitelnavigation
                }

                $inhaltsPfad = $this->pfadKombinieren($opfBasis, $item['href']);
                $xhtmlRoh = $this->zipDateiLesen($zip, $inhaltsPfad);
                if ($xhtmlRoh === null) {
                    continue;
                }

                $inhaltsOrdner = dirname($inhaltsPfad);
                if ($inhaltsOrdner === '.') {
                    $inhaltsOrdner = '';
                }

                [$bodyHtml, $kapitelTitel] = $this->kapitelAufbereiten($xhtmlRoh, $zip, $inhaltsOrdner, $reihenfolge + 1, $item['href'], $buch->title);
                if (trim($bodyHtml) === '') {
                    continue;
                }

                (new Chapter([
                    'book_id' => $buch->id,
                    'sort_order' => $reihenfolge,
                    'title' => $kapitelTitel,
                    'html_content' => $bodyHtml,
                ]))->save(false);

                $reihenfolge++;
            }

            if ($reihenfolge === 0) {
                $buch->delete();
                throw new RuntimeException('Keine lesbaren Kapitel im EPUB gefunden.');
            }

            return $buch;
        } finally {
            $zip->close();
        }
    }

    private function opfPfadFinden(ZipArchive $zip): string
    {
        $containerXml = $this->zipDateiLesen($zip, 'META-INF/container.xml');
        if ($containerXml === null) {
            throw new RuntimeException('META-INF/container.xml fehlt -- keine gueltige EPUB-Datei.');
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($containerXml);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('c', self::CONTAINER_NS);
        $rootfile = $xpath->query('//c:rootfile')->item(0);

        if ($rootfile === null || !$rootfile->hasAttribute('full-path')) {
            throw new RuntimeException('Konnte den Pfad zur OPF-Datei nicht ermitteln.');
        }

        return $rootfile->getAttribute('full-path');
    }

    private function xmlLaden(ZipArchive $zip, string $pfad, string $bezeichnung): DOMDocument
    {
        $inhalt = $this->zipDateiLesen($zip, $pfad);
        if ($inhalt === null) {
            throw new RuntimeException("{$bezeichnung} nicht im Archiv gefunden.");
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $erfolg = $dom->loadXML($inhalt);
        libxml_clear_errors();

        if (!$erfolg) {
            throw new RuntimeException("{$bezeichnung} konnte nicht als XML gelesen werden.");
        }

        return $dom;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function metadatenLesen(DOMDocument $opf): array
    {
        $xpath = new DOMXPath($opf);
        $xpath->registerNamespace('opf', self::OPF_NS);
        $xpath->registerNamespace('dc', self::DC_NS);

        $titel = $xpath->query('//opf:metadata/dc:title')->item(0)?->textContent;
        $autor = $xpath->query('//opf:metadata/dc:creator')->item(0)?->textContent;

        return [$titel !== null ? trim($titel) : null, $autor !== null ? trim($autor) : null];
    }

    /**
     * @return array{0: array<string, array{href: string, mediaType: string, properties: string}>, 1: string[]}
     */
    private function manifestUndSpineLesen(DOMDocument $opf): array
    {
        $xpath = new DOMXPath($opf);
        $xpath->registerNamespace('opf', self::OPF_NS);

        $manifest = [];
        foreach ($xpath->query('//opf:manifest/opf:item') as $item) {
            $id = $item->getAttribute('id');
            $manifest[$id] = [
                'href' => $item->getAttribute('href'),
                'mediaType' => $item->getAttribute('media-type'),
                'properties' => $item->getAttribute('properties'),
            ];
        }

        $spineIds = [];
        foreach ($xpath->query('//opf:spine/opf:itemref') as $itemref) {
            $spineIds[] = $itemref->getAttribute('idref');
        }

        return [$manifest, $spineIds];
    }

    private function pfadKombinieren(string $basis, string $relativ): string
    {
        $roh = $basis !== '' ? "{$basis}/{$relativ}" : $relativ;

        // "../"-Segmente aufloesen (kommt in EPUBs mit verschachtelten
        // Ordnern gelegentlich vor).
        $teile = [];
        foreach (explode('/', $roh) as $segment) {
            if ($segment === '..') {
                array_pop($teile);
            } elseif ($segment !== '.' && $segment !== '') {
                $teile[] = $segment;
            }
        }

        return implode('/', $teile);
    }

    private function titelAusDateiname(string $href): ?string
    {
        $basis = pathinfo($href, PATHINFO_FILENAME); // z.B. "1_title-page"
        $basis = preg_replace('/^\d+_/', '', $basis); // fuehrende Nummer weg -> "title-page"
        $basis = str_replace(['-', '_'], ' ', $basis ?? '');
        $basis = trim($basis);

        return $basis !== '' ? mb_convert_case($basis, MB_CASE_TITLE) : null;
    }

    private function zipDateiLesen(ZipArchive $zip, string $pfad): ?string
    {
        $inhalt = $zip->getFromName($pfad);
        return $inhalt !== false ? $inhalt : null;
    }

    /**
     * @return array{0: string, 1: string} [koerperHtml, kapitelTitel]
     */
    private function kapitelAufbereiten(
        string $xhtmlRoh,
        ZipArchive $zip,
        string $inhaltsOrdner,
        int $kapitelNummer,
        string $dateiHref,
        string $buchTitel
    ): array {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $erfolg = $dom->loadXML($xhtmlRoh);
        if (!$erfolg) {
            // Fallback fuer nicht ganz sauberes XHTML: als HTML statt XML lesen.
            $dom = new DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8"?>' . $xhtmlRoh, LIBXML_NOWARNING | LIBXML_NOERROR);
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('x', self::XHTML_NS);

        $body = $xpath->query('//x:body')->item(0) ?? $xpath->query('//body')->item(0);
        if ($body === null) {
            return ['', "Kapitel {$kapitelNummer}"];
        }

        // Kapiteltitel: bevorzugt der <title>-Tag im Dateikopf (bei Reedsy-
        // Exporten zuverlaessig der volle Kapitelname, z.B. "KAPITEL 1: DER
        // ALGORITHMUS DER WUT"). NICHT die erste Ueberschrift im Text nehmen
        // -- manche Kapitel haben zwei h1-Elemente (erst eine reine
        // Kapitelnummer wie "4", dann erst den eigentlichen Titel), das
        // wuerde faelschlich nur die Nummer liefern.
        $titel = trim((string)($xpath->query('//x:title')->item(0)?->textContent
            ?? $xpath->query('//title')->item(0)?->textContent
            ?? ''));

        if ($titel === '') {
            $ueberschrift = $xpath->query('.//x:h1[not(contains(@class, "number"))]|.//x:h2|.//x:h3'
                . '|.//h1[not(contains(@class, "number"))]|.//h2|.//h3', $body)->item(0);
            $titel = $ueberschrift !== null ? trim($ueberschrift->textContent) : '';
        }

        // Titelseite/Impressum haben meist keinen eigenen <title>-Tag und
        // erben dann den Buchtitel -- das wuerde in der Kapitelliste wie
        // eine Dopplung aussehen. In dem Fall stattdessen einen Namen aus
        // dem Dateinamen ableiten (z.B. "1_title-page.xhtml" -> "Title Page").
        if ($titel === '' || $titel === $buchTitel) {
            $titel = $this->titelAusDateiname($dateiHref) ?: "Kapitel {$kapitelNummer}";
        }

        // Die Kapitelueberschrift im Text selbst entfernen -- unsere eigene
        // Leseansicht zeigt den Titel bereits separat, sonst stuende er
        // doppelt da (einmal als reine Nummer, einmal als Volltext).
        foreach ($xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " chapter-heading ")]', $body) as $ueberschriftDiv) {
            $ueberschriftDiv->parentNode->removeChild($ueberschriftDiv);
        }

        $innerHtml = '';
        foreach ($body->childNodes as $kind) {
            $innerHtml .= $dom->saveXML($kind);
        }

        $innerHtml = $this->bilderEinbetten($innerHtml, $zip, $inhaltsOrdner);
        $innerHtml = $this->linksEntschaerfen($innerHtml);

        return [$innerHtml, $titel];
    }

    /**
     * Ersetzt jede img-src-Referenz durch die eingebettete Bilddatei als
     * Data-URI -- es gibt danach keine separat abrufbare Bild-URL mehr,
     * die man verlinken oder direkt herunterladen koennte.
     */
    private function bilderEinbetten(string $html, ZipArchive $zip, string $inhaltsOrdner): string
    {
        return (string)preg_replace_callback(
            '/(<img[^>]+src=")([^"]+)(")/i',
            function (array $treffer) use ($zip, $inhaltsOrdner) {
                $bildPfad = $this->pfadKombinieren($inhaltsOrdner, $treffer[2]);
                $bildBytes = $this->zipDateiLesen($zip, $bildPfad);
                if ($bildBytes === null) {
                    return $treffer[0];
                }

                $mimeTyp = match (strtolower(pathinfo($bildPfad, PATHINFO_EXTENSION))) {
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    default => 'image/jpeg',
                };

                $dataUri = 'data:' . $mimeTyp . ';base64,' . base64_encode($bildBytes);
                return $treffer[1] . $dataUri . $treffer[3];
            },
            $html
        );
    }

    /**
     * Entfernt Links, die aus dem Lesebereich herausfuehren wuerden
     * (z.B. Reedsy-Werbelinks) -- ersetzt sie durch reinen Text, damit
     * niemand versehentlich auf eine externe Seite gelangt.
     */
    private function linksEntschaerfen(string $html): string
    {
        return (string)preg_replace('/<a\b[^>]*>(.*?)<\/a>/is', '$1', $html);
    }
}
