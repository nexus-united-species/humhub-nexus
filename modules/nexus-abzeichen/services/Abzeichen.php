<?php

namespace nexus\modules\abzeichen\services;

use cebe\markdown\GithubMarkdown;
use yii\helpers\HtmlPurifier;

/**
 * Ein Abzeichen zum Bekennen. Einstellungen (Texte als String oder ['de' => ..., 'en' => ...]):
 *   name       Name, z. B. "Genesis-Pakt"
 *   kurz       ein Satz, worum es geht
 *   text       ausfuehrlicher Text (Markdown) -- oder
 *   textDatei  Pfad zu einer Markdown-Datei auf dem Server (bzw. ['de' => Pfad, 'en' => Pfad])
 *   farbe      Rahmenfarbe (#RRGGBB), Vorgabe Gold
 *   symbol     Font-Awesome-Name fuer das Siegel, Vorgabe "star"
 */
class Abzeichen
{
    private const VORGABE_FARBE = '#D4AF37';
    private const VORGABE_SYMBOL = 'star';

    public function __construct(public readonly string $kennung, private readonly array $e)
    {
    }

    public function name(): string
    {
        return Texte::waehle($this->e['name'] ?? null) ?: $this->kennung;
    }

    public function kurz(): string
    {
        return Texte::waehle($this->e['kurz'] ?? null);
    }

    public function farbe(): string
    {
        $f = (string)($this->e['farbe'] ?? '');
        return preg_match('/^#[0-9a-fA-F]{6}$/', $f) ? $f : self::VORGABE_FARBE;
    }

    public function symbol(): string
    {
        $s = (string)($this->e['symbol'] ?? '');
        return preg_match('/^[a-z0-9-]+$/', $s) ? $s : self::VORGABE_SYMBOL;
    }

    /** Ausfuehrlicher Text als gereinigtes HTML */
    public function textHtml(): string
    {
        $markdown = Texte::waehle($this->e['text'] ?? null);
        if ($markdown === '') {
            $datei = Texte::waehle($this->e['textDatei'] ?? null);
            $markdown = $datei !== '' && is_file($datei) ? (string)file_get_contents($datei) : '';
        }
        return $markdown === '' ? '' : HtmlPurifier::process((new GithubMarkdown())->parse($markdown));
    }

    /**
     * Text fuer die Abzeichen-Seite aufbereitet: Titel (erste Ueberschrift) getrennt, Abschnitte je
     * Zwischenueberschrift, "### I. Name – Untertitel" + Zitat als Versprechen-Karte, ein Absatz nur
     * in Kursiv als Schlusssatz. Arbeitet auf dem bereits gereinigten HTML und fuegt nur Rahmen hinzu.
     * @return array{titel: string, html: string}
     */
    public function seite(): array
    {
        $html = $this->textHtml();
        $titel = $this->name();
        if (preg_match('#<h1>(.*?)</h1>#s', $html, $m)) {
            $titel = trim(strip_tags($m[1]));
            $html = str_replace($m[0], '', $html);
        }
        $html = preg_replace_callback('#<h3>(.*?)</h3>\s*<blockquote>(.*?)</blockquote>#s', static function ($m) {
            $kopf = trim($m[1]);
            $nr = '';
            if (preg_match('#^([IVXLC]+|\d+)\.\s*(.*)$#s', $kopf, $n)) {
                $nr = '<span class="nx-abz-nr">' . $n[1] . '</span>';
                $kopf = $n[2];
            }
            $teile = preg_split('#\s+[–—-]\s+#u', $kopf, 2);
            $unter = isset($teile[1]) ? '<span class="nx-abz-unter">' . $teile[1] . '</span>' : '';
            return '<article class="nx-abz-versprechen">' . $nr . '<h3>' . $teile[0] . $unter . '</h3><blockquote>' . $m[2] . '</blockquote></article>';
        }, $html);
        $html = preg_replace('#(?:<article class="nx-abz-versprechen">.*?</article>\s*)+#s', '<div class="nx-abz-versprechen-liste">$0</div>', $html);
        $html = preg_replace('#<p><em>([^<]*)</em></p>#', '<p class="nx-abz-schluss">$1</p>', $html);
        $teile = preg_split('#(?=<h2>)#', $html);
        $aus = '';
        foreach ($teile as $teil) {
            if (trim($teil) !== '') {
                $aus .= str_starts_with($teil, '<h2>') ? '<section class="nx-abz-abschnitt">' . $teil . '</section>' : $teil;
            }
        }
        return ['titel' => $titel, 'html' => $aus];
    }
}
