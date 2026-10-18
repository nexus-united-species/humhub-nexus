<?php

namespace nexus\modules\wissen\services;

use DOMXPath;
use RuntimeException;
use ZipArchive;

/**
 * Was beim Lesen einer Word-Datei ueberall gebraucht wird. Pro Datei neu -- nichts bleibt zwischen
 * zwei Anfragen haengen (FrankenPHP-Worker).
 * @internal
 */
final class WordKontext
{
    public ?DOMXPath $xp = null;
    /** Bilder, die NICHT uebernommen werden konnten (gelber Hinweis im Text) */
    public int $bilder = 0;
    /** Bilder, die uebernommen wurden */
    public int $bilderOk = 0;
    /** @var array<string, array{ebene: int, numId: string, ilvl: string, code: bool}> */
    private array $aufgeloest = [];

    /**
     * @param array<string, string> $links      Beziehungs-ID => Link-Ziel
     * @param array<string, string> $bildZiele  Beziehungs-ID => Pfad im Word-Archiv ("word/media/image1.png")
     */
    public function __construct(
        public readonly array $links,
        private readonly array $stile,
        public readonly array $nummeriert,
        private readonly array $bildZiele = [],
        private readonly ?ZipArchive $zip = null,
        private readonly ?Sammlung $sammlung = null,
    ) {
    }

    /**
     * Ein eingebettetes Bild in die Sammlung uebernehmen.
     * @return string|null Markdown fuer das Bild, null wenn es nicht geht (Format wie EMF, fehlende Datei,
     *                     keine Sammlung) -- dann zaehlt es als nicht uebernommen
     */
    public function bild(string $beziehung, string $beschreibung): ?string
    {
        $ziel = $this->bildZiele[$beziehung] ?? null;
        if ($ziel === null || $this->zip === null || $this->sammlung === null) {
            return null;
        }
        $bytes = $this->zip->getFromName($ziel);
        $info = $this->zip->statName($ziel);
        if ($bytes === false || $info === false || $info['size'] > Bilder::MAX_BYTES) {
            return null;
        }
        try {
            $name = Bilder::speichern($this->sammlung, $bytes);
        } catch (RuntimeException $e) {
            return null;
        }
        if ($name === null) {
            return null;
        }
        $this->bilderOk++;
        return Bilder::markdown($name, $beschreibung);
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
