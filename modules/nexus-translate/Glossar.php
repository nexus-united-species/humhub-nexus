<?php

namespace humhub\modules\nexusTranslate;

/**
 * Zentrales N.E.X.U.S.-Glossar (Josh, 26.09.2026). Wird JEDER Uebersetzung mitgegeben
 * (Module::uebersetzen) -- Beitraege, Kommentare, Wiki, Termine, Kreisnamen, "Translate"-Knopf.
 * Vorher galt es nur fuer Kreisnamen; in Beitraegen stand dann z. B. "en Kreis 2" statt "en Círculo 2".
 *
 * Pflege: Zeile ergaenzen/aendern und VERSION hochzaehlen. Die Versionsnummer steckt in der
 * Pruefsumme jeder gespeicherten Uebersetzung (UebersetzungsSpeicher::pruefsumme) -- aendert sie
 * sich, gelten alle automatischen Uebersetzungen als veraltet und entstehen beim naechsten Lesen
 * neu (oder sofort mit werkzeuge_vorab_uebersetzen.php). Eine neue Sprache (z. B. Russisch) =
 * je Zeile eine Spalte mehr und ein Eintrag in NAMEN.
 * Die Begriffe folgen den schon gepflegten Kreisnamen ("Círculo 3 -- Plano - AETHER & Economía").
 */
class Glossar
{
    public const VERSION = 1;

    public const NAMEN = ['de' => 'German', 'en' => 'English', 'es' => 'Spanish'];

    /** Feste Entsprechungen, je Zeile derselbe Begriff in allen Sprachen. */
    public const BEGRIFFE = [
        ['de' => 'Kreis', 'en' => 'Circle', 'es' => 'Círculo'],
        ['de' => 'Kreise', 'en' => 'Circles', 'es' => 'Círculos'],
        ['de' => 'Bauplan', 'en' => 'Blueprint', 'es' => 'Plano'],
        ['de' => 'Menschheitsfamilie', 'en' => 'Human Family', 'es' => 'Familia Humana'],
        ['de' => 'Gemeinschaft', 'en' => 'community', 'es' => 'comunidad'],
        ['de' => 'Gemeinschaften', 'en' => 'communities', 'es' => 'comunidades'],
        ['de' => 'Unterstützer', 'en' => 'Supporters', 'es' => 'Colaboradores'],
        ['de' => 'Entwicklungsrat', 'en' => 'Development Council', 'es' => 'Consejo de Desarrollo'],
        ['de' => 'Hauptgruppe', 'en' => 'Main Group', 'es' => 'Grupo principal'],
        ['de' => 'Ortsgruppe', 'en' => 'local group', 'es' => 'grupo local'],
    ];

    /** Eigennamen, die in keiner Sprache uebersetzt werden. */
    public const UNVERAENDERT = ['N.E.X.U.S.', 'NEXUS', 'OneApp', 'AETHER', 'VITA', 'TERRA', 'AURA', 'DAO', 'Mesh', 'Jitsi', 'HumHub'];

    /** Anweisung fuer das Uebersetzungsmodell, passend zur Zielsprache. */
    public static function anweisung(string $ziel): string
    {
        if (!isset(self::NAMEN[$ziel])) {
            return '';
        }
        $zeilen = [];
        foreach (self::BEGRIFFE as $zeile) {
            if (!isset($zeile[$ziel])) {
                continue;
            }
            $andere = array_unique(array_values(array_diff_key($zeile, [$ziel => true])));
            $zeilen[] = '"' . implode('" / "', $andere) . '" -> "' . $zeile[$ziel] . '"';
        }
        return 'Use this fixed N.E.X.U.S. glossary (always use the ' . self::NAMEN[$ziel] . ' term on the right, '
            . 'also inside names and titles): ' . implode('; ', $zeilen) . '. '
            // Sonst uebernimmt das Modell die Grossschreibung der Liste: "nuestra Comunidad" mitten im Satz.
            . 'Adapt capitalization, articles and plural to the sentence as normal grammar requires. '
            . 'Never translate these names: ' . implode(', ', self::UNVERAENDERT) . '.';
    }
}
