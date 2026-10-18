<?php

namespace nexus\modules\wissen\services;

use cebe\markdown\GithubMarkdown;
use DOMDocument;
use DOMElement;
use DOMText;
use DOMXPath;
use nexus\modules\wissen\models\Artikel;
use Yii;
use yii\helpers\HtmlPurifier;

/**
 * Macht aus dem Markdown eines Artikels die Seite "wie bei Wikipedia":
 * - HTML (gereinigt mit HtmlPurifier -- die Texte kommen aus einem Ordner und von Admins)
 * - Inhaltsverzeichnis aus den Zwischenueberschriften
 * - Verweise auf andere Artikel-Dateien (relative .md-Links) werden zu Portal-Links
 * - Querverweise: Erwaehnt ein Text den Kurztitel eines anderen Artikels, wird die erste Stelle
 *   verlinkt (hoechstens MAX_QUERVERWEISE je Artikel, nie in Ueberschriften oder bestehenden Links)
 * - Quellen zaehlen (Links im Abschnitt "Quellen ...", "Sources ...", "Literatur ...")
 * Das Ergebnis wird zwischengespeichert; jede Aenderung an irgendeinem Artikel der Sammlung macht
 * den Speicher ungueltig (die Querverweise haengen von allen Titeln ab).
 */
class Darstellung
{
    private const MAX_QUERVERWEISE = 12;
    private const MIN_KURZTITEL = 4;
    /** Ueberschriften, unter denen die Belege stehen (Wortanfang, ohne Gross/klein) */
    private const QUELLEN = ['quelle', 'source', 'fuente', 'literatur', 'reference', 'referenz', 'beleg'];

    /** @return array{html: string, inhalt: array<int, array{id: string, text: string}>, quellen: int} */
    public static function artikel(Artikel $artikel, Sammlung $sammlung): array
    {
        $schluessel = ['nexus-wissen-darstellung', $artikel->id, $artikel->updated_at, self::stand($sammlung->kennung), Yii::$app->language];
        return Yii::$app->cache->getOrSet($schluessel, fn() => self::bauen($artikel->inhalt, $artikel, $sammlung), 86400);
    }

    /** Fuer die Versionsansicht: ohne Querverweise und Zwischenspeicher. */
    public static function nurText(string $markdown, Sammlung $sammlung): string
    {
        return self::bauen($markdown, null, $sammlung)['html'];
    }

    /** Aendert sich, sobald irgendein Artikel der Sammlung geaendert wird. */
    public static function stand(string $sammlung): string
    {
        return $sammlung . '|' . Artikel::inSammlung($sammlung)->max('updated_at') . '|' . Artikel::inSammlung($sammlung)->count();
    }

    private static function bauen(string $markdown, ?Artikel $artikel, Sammlung $sammlung): array
    {
        $html = HtmlPurifier::process((new GithubMarkdown())->parse($markdown));
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="nx-wurzel">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xp = new DOMXPath($doc);

        // Die H1 ist der Titel -- der steht schon im Seitenkopf
        foreach ($xp->query('//h1') as $h1) {
            $h1->parentNode->removeChild($h1);
        }

        $inhalt = [];
        $vergeben = [];
        foreach ($xp->query('//h2|//h3') as $h) {
            /** @var DOMElement $h */
            $id = self::anker($h->textContent, $vergeben);
            $h->setAttribute('id', $id);
            if ($h->nodeName === 'h2') {
                $inhalt[] = ['id' => $id, 'text' => trim($h->textContent)];
            }
        }

        $dateien = self::dateiZuSlug($sammlung->kennung);
        foreach ($xp->query('//a[@href]') as $a) {
            /** @var DOMElement $a */
            $href = $a->getAttribute('href');
            if (preg_match('#^https?://#i', $href)) {
                $a->setAttribute('target', '_blank');
                $a->setAttribute('rel', 'noopener');
                continue;
            }
            $datei = basename(explode('#', $href)[0]);
            if (str_ends_with(strtolower($datei), '.md') && isset($dateien[$datei])) {
                $a->setAttribute('href', $sammlung->url('artikel', ['a' => $dateien[$datei]]));
            } else {
                // Verweis auf etwas, das es im Portal nicht gibt: Text behalten, Link entfernen
                $a->parentNode->replaceChild($doc->createTextNode($a->textContent), $a);
            }
        }

        if ($artikel !== null) {
            self::querverweise($doc, $xp, $artikel, $sammlung);
        }

        $quellen = 0;
        foreach ($xp->query('//h2') as $h) {
            if (!self::istQuellenUeberschrift($h->textContent)) {
                continue;
            }
            for ($n = $h->nextSibling; $n !== null && $n->nodeName !== 'h2'; $n = $n->nextSibling) {
                if ($n instanceof DOMElement) {
                    $quellen += $xp->query('.//a[@href]', $n)->length;
                }
            }
        }

        $wurzel = $doc->getElementById('nx-wurzel') ?? $xp->query('//div')->item(0);
        $ergebnis = '';
        foreach ($wurzel->childNodes as $kind) {
            $ergebnis .= $doc->saveHTML($kind);
        }
        return ['html' => $ergebnis, 'inhalt' => $inhalt, 'quellen' => $quellen];
    }

    private static function querverweise(DOMDocument $doc, DOMXPath $xp, Artikel $artikel, Sammlung $sammlung): void
    {
        $ziele = [];
        foreach (self::kurztitel($sammlung->kennung) as $id => [$kurz, $slug]) {
            if ($id !== $artikel->id && mb_strlen($kurz) >= self::MIN_KURZTITEL) {
                $ziele[] = [$kurz, $slug];
            }
        }
        // Laengere zuerst: "Durchfall bei Kindern" vor "Durchfall"
        usort($ziele, fn($a, $b) => mb_strlen($b[0]) <=> mb_strlen($a[0]));
        $gesetzt = 0;
        $verlinkt = [];
        $knoten = [];
        foreach ($xp->query('//p//text()[not(ancestor::a)]|//li//text()[not(ancestor::a)]') as $t) {
            $knoten[] = $t;
        }
        foreach ($ziele as [$kurz, $slug]) {
            if ($gesetzt >= self::MAX_QUERVERWEISE || isset($verlinkt[$slug])) {
                continue;
            }
            $muster = '/(?<![\p{L}\p{N}])(' . preg_quote($kurz, '/') . ')(?![\p{L}\p{N}])/iu';
            foreach ($knoten as $i => $t) {
                /** @var DOMText $t */
                if ($t->parentNode === null || !preg_match($muster, $t->nodeValue, $m, PREG_OFFSET_CAPTURE)) {
                    continue;
                }
                $vor = substr($t->nodeValue, 0, $m[1][1]);
                $wort = $m[1][0];
                $nach = substr($t->nodeValue, $m[1][1] + strlen($wort));
                $a = $doc->createElement('a');
                $a->setAttribute('href', $sammlung->url('artikel', ['a' => $slug]));
                $a->setAttribute('class', 'nx-ws-querverweis');
                $a->appendChild($doc->createTextNode($wort));
                $davor = $doc->createTextNode($vor);
                $danach = $doc->createTextNode($nach);
                $eltern = $t->parentNode;
                $eltern->insertBefore($davor, $t);
                $eltern->insertBefore($a, $t);
                $eltern->insertBefore($danach, $t);
                $eltern->removeChild($t);
                // Die Reste duerfen weiter verlinkt werden, der Link selbst nicht
                $knoten[$i] = $davor;
                $knoten[] = $danach;
                $verlinkt[$slug] = true;
                $gesetzt++;
                break;
            }
        }
    }

    /** @return array<int, array{0: string, 1: string}> id => [kurztitel, slug] */
    private static function kurztitel(string $sammlung): array
    {
        return Yii::$app->cache->getOrSet(['nexus-wissen-kurztitel', self::stand($sammlung)], function () use ($sammlung) {
            $liste = [];
            foreach (Artikel::sichtbarIn($sammlung)->select(['id', 'kurztitel', 'slug'])->asArray()->all() as $z) {
                $liste[(int)$z['id']] = [(string)$z['kurztitel'], (string)$z['slug']];
            }
            return $liste;
        }, 86400);
    }

    /** @return array<string, string> Dateiname => slug */
    private static function dateiZuSlug(string $sammlung): array
    {
        return Yii::$app->cache->getOrSet(['nexus-wissen-dateien', self::stand($sammlung)], function () use ($sammlung) {
            return Artikel::sichtbarIn($sammlung)->select(['slug', 'datei'])->indexBy('datei')->column();
        }, 86400);
    }

    private static function istQuellenUeberschrift(string $text): bool
    {
        $text = mb_strtolower(trim($text));
        foreach (self::QUELLEN as $wort) {
            if (preg_match('/(^|[^\p{L}])' . $wort . '/u', $text)) {
                return true;
            }
        }
        return false;
    }

    private static function anker(string $text, array &$vergeben): string
    {
        $basis = Slug::aus($text) ?: 'abschnitt';
        $id = $basis;
        for ($i = 2; isset($vergeben[$id]); $i++) {
            $id = $basis . '-' . $i;
        }
        $vergeben[$id] = true;
        return $id;
    }

    /** Der erste richtige Absatz als Vorschau in Trefferlisten. */
    public static function anriss(string $markdown, int $laenge = 220): string
    {
        $text = preg_replace('/^#.*$/m', '', $markdown);
        foreach (preg_split('/\n\s*\n/', (string)$text) as $absatz) {
            $absatz = trim(preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $absatz));
            $absatz = trim(preg_replace('/[*_`>#-]+/', '', $absatz));
            if (mb_strlen($absatz) > 40) {
                return mb_strlen($absatz) > $laenge ? mb_substr($absatz, 0, $laenge - 1) . '…' : $absatz;
            }
        }
        return '';
    }
}
