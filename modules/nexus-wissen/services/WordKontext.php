<?php

namespace nexus\modules\wissen\services;

use DOMXPath;

/**
 * Was beim Lesen einer Word-Datei ueberall gebraucht wird. Pro Datei neu -- nichts bleibt zwischen
 * zwei Anfragen haengen (FrankenPHP-Worker).
 * @internal
 */
final class WordKontext
{
    public ?DOMXPath $xp = null;
    public int $bilder = 0;
    /** @var array<string, array{ebene: int, numId: string, ilvl: string, code: bool}> */
    private array $aufgeloest = [];

    public function __construct(
        public readonly array $links,
        private readonly array $stile,
        public readonly array $nummeriert,
    ) {
    }

    /**
     * Ebene (0 = keine Ueberschrift), Nummerierung und "Code?" einer Formatvorlage -- mit allem, was sie
     * von ihrer Basis erbt ("Meine Ueberschrift" basiert auf "Ueberschrift 2").
     * Ueberschrift 1 wird "##": "#" ist der Artikeltitel.
     * @return array{ebene: int, numId: string, ilvl: string, code: bool}
     */
    public function stil(string $id, int $tiefe = 0): array
    {
        $leer = ['ebene' => 0, 'numId' => '', 'ilvl' => '', 'code' => false];
        if ($id === '' || !isset($this->stile[$id]) || $tiefe > 10) {
            return $leer;
        }
        if (isset($this->aufgeloest[$id])) {
            return $this->aufgeloest[$id];
        }
        $s = $this->stile[$id];
        $basis = $this->stil($s['basis'], $tiefe + 1);
        $ebene = match (true) {
            $s['name'] === 'title' => 1,
            (bool)preg_match('/^heading (\d)$/', $s['name'], $m) => min(4, (int)$m[1] + 1),
            $s['gliederung'] !== '' && (int)$s['gliederung'] < 9 => min(4, (int)$s['gliederung'] + 2),
            default => $basis['ebene'],
        };
        $code = (bool)preg_match('/code|preformatted|quelltext|befehl|konsole|terminal/', $s['name']) || $basis['code'];
        return $this->aufgeloest[$id] = [
            'ebene' => $ebene,
            'numId' => $s['numId'] !== '' ? $s['numId'] : $basis['numId'],
            'ilvl' => $s['ilvl'] !== '' ? $s['ilvl'] : $basis['ilvl'],
            'code' => $code,
        ];
    }
}
