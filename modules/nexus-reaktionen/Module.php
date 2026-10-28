<?php

namespace nexus\modules\reaktionen;

/**
 * Emoji-Reaktionen (28.10.2026, Wunsch aus der Gemeinschaft: "wie bei Facebook und Telegram, die Emojis im Schreibfeld sind
 * keine schoenen Reaktionen"). HumHub kennt nur den Daumen ("Gefaellt mir"). Dieses Modul legt daneben eine
 * kleine Auswahl, ohne etwas am HumHub-Kern oder am Like-Modul zu aendern:
 *
 * - Der Daumen bleibt wie er ist (Like-Modul, Zaehler, Benachrichtigung).
 * - Jeder Mensch hat je Beitrag/Kommentar hoechstens EINE Reaktion; dieselbe nochmal anklicken nimmt sie zurueck.
 * - Reaktionen loesen bewusst keine Benachrichtigung und keine E-Mail aus (Mailmenge, siehe CLAUDE.md).
 */
class Module extends \humhub\components\Module
{
    /** @var string[] Auswahl in dieser Reihenfolge; der Daumen ist HumHubs eigenes "Gefaellt mir". */
    public $emojis = ['❤️', '😂', '😮', '😢', '🙏', '🎉'];

    /** @var int Namen je Emoji im Hinweistext */
    public $maxNamen = 8;

    /** @var int Objekte je Abfrage der Seite */
    public $maxObjekte = 100;

    public function getName()
    {
        return 'N.E.X.U.S. Reactions';
    }

    public function getDescription()
    {
        return 'Emoji reactions below posts and comments, next to the like link.';
    }
}
