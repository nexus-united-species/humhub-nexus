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
}
