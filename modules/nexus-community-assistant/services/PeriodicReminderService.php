<?php

namespace nexus\modules\communityAssistant\services;

use nexus\modules\communityAssistant\models\ReminderState;
use Yii;

/**
 * Spendenaufruf (alle 10 Tage) und Selbstvorstellung (alle 30 Tage) --
 * dieselben Texte und Intervalle, die im Telegram-Bot bereits laufen (siehe
 * bot.py DONATION_TEXT_DE / SELF_INTRO_TEXT_DE). Bewusst nur auf Deutsch:
 * HumHub ist bislang einsprachig, anders als die mehrsprachigen
 * Telegram-Gruppen.
 *
 * Zielgruppe: alle Space-IDs aus PERIODIC_REMINDER_SPACE_IDS (env,
 * kommagetrennt) -- nichts im Code hartkodiert.
 */
class PeriodicReminderService
{
    private const SPENDENAUFRUF_INTERVALL_TAGE = 10;
    private const SELBSTVORSTELLUNG_INTERVALL_TAGE = 30;

    private const SPENDENAUFRUF_TEXT = <<<'TEXT'
💛 N.E.X.U.S. mittragen – schon 5 € im Monat helfen

N.E.X.U.S. entsteht nicht aus Kapital, sondern aus Menschen, die gemeinsam etwas Neues aufbauen wollen.

Trotzdem braucht die Umsetzung reale Mittel: Entwicklung der OneApp, KI-gestützte Programmierung, Tokens, Tests, Infrastruktur, Tools und laufende technische Arbeit.

Über unsere Spendenplattform sind bereits erste Unterstützungen eingegangen. Dafür ein herzliches Danke an alle, die schon etwas beigetragen haben. Den größten Teil der bisherigen Kosten hat Josh bisher selbst getragen.

Besonders hilfreich sind wiederkehrende kleine Beiträge.

Wenn viele aus der Gemeinschaft monatlich nur 5 oder 10 € geben, entsteht daraus eine stabile Entwicklungsbasis. Nicht, weil Einzelne viel leisten müssen — sondern weil viele gemeinsam ein Stück Verantwortung tragen.

N.E.X.U.S. ist ein Gemeinschaftsprojekt.
Und Gemeinschaft bedeutet auch: Wir bauen nicht nur ideell mit, sondern tragen das Fundament, soweit es jedem möglich ist.

Wer N.E.X.U.S. unterstützen möchte, kann das hier tun:

👉 {spendenLink}

Wichtig ist mir dabei: Eure Unterstützung fällt in kein schwarzes Loch.

Wir bauen ein System des fairen Ausgleichs, und genau das wenden wir auch jetzt schon an. Jeder Euro, den ihr als Infrastruktur-Unterstützung beisteuert, wird von mir lückenlos in unserem Spendeneingang dokumentiert und direkt 1:1 als "Vita" (bzw. "Aura") in unserem Ätherprotokoll später hinterlegt.

Das bedeutet: Eure eingebrachte Energie wird im Netzwerk der OneApp als euer initiales Guthaben verbucht. Ihr leistet keine Spende ins Nichts, sondern einen vorgezogenen Energie-Ausgleich. Ihr sichert euch eure ersten Ressourcen im neuen System, noch bevor es offiziell startet. Wenn die Marktplatzfunktion und das Aether-Wertesystem integriert ist, könnt ihr es wiederverwenden.

Danke an alle, die dieses Projekt möglich machen — mit Zeit, Wissen, Herz, Vertrauen oder finanzieller Unterstützung. 💛
TEXT;

    private const SELBSTVORSTELLUNG_TEXT = <<<'TEXT'
👋 Kurz vorgestellt: Nova, euer N.E.X.U.S. KI-Assistent

Alle paar Wochen melde ich mich kurz, falls du mich noch nicht kennst oder einfach mal dran erinnert werden willst, was ich hier eigentlich tue. 💛

Ich bin Nova, die KI-Assistenz dieser Gemeinschaft, und helfe euch bei:

📖 Orientierung
Fragt mich einfach direkt (mit @-Erwähnung in einem Beitrag oder Kommentar) zu Charta, OneApp, AETHER, Governance und mehr.

❓ Fragen & Austausch
Erwähnt mich (@Nova) unter einem Beitrag oder Kommentar mit eurer Frage — ich antworte direkt darunter. Oder schreibt mir einfach eine private Nachricht.

🙋 Weitergabe
Wenn ich etwas nicht beantworten kann oder ein Mensch gefragt ist, gebe ich eure Nachricht an das Team weiter.

Ich ersetze keine Menschen und keine Entscheidungen — ich bin nur da, um euch den Alltag hier ein bisschen leichter zu machen.

Schön, dass du Teil von N.E.X.U.S. bist. 🚀
TEXT;

    public function __construct(
        private readonly int $assistantUserId,
    ) {
    }

    public function spendenaufrufFallsFaellig(): bool
    {
        // Ohne Spendenseite (Moduleinstellung "spendenLink") kein Spendenaufruf.
        $link = (string)\nexus\modules\communityAssistant\Module::instanz()->spendenLink;
        if ($link === '') {
            return false;
        }
        $text = str_replace('{spendenLink}', $link, self::SPENDENAUFRUF_TEXT);
        return $this->fallsFaelligPosten('donation_reminder', self::SPENDENAUFRUF_INTERVALL_TAGE, $text);
    }

    public function selbstvorstellungFallsFaellig(): bool
    {
        return $this->fallsFaelligPosten('self_intro_reminder', self::SELBSTVORSTELLUNG_INTERVALL_TAGE, self::SELBSTVORSTELLUNG_TEXT);
    }

    private function fallsFaelligPosten(string $name, int $intervallTage, string $text): bool
    {
        $jetzt = new \DateTimeImmutable();
        $letzterLauf = ReminderState::letzterLauf($name);

        if ($letzterLauf === null) {
            // Allererster Lauf ueberhaupt: NICHT sofort posten (sonst
            // erscheint der Aufruf ungewollt in dem Moment, in dem der
            // Zeitplan zum ersten Mal aktiv wird) -- stattdessen den
            // Rhythmus seeden, der erste echte Post folgt dann nach einem
            // vollen Intervall. Gleiches Muster wie beim Telegram-Bot.
            ReminderState::laufMarkieren($name, $jetzt);
            return false;
        }

        if ($jetzt->getTimestamp() - $letzterLauf->getTimestamp() < $intervallTage * 86400) {
            return false;
        }

        $spaceIds = $this->zielSpaceIds();
        if (empty($spaceIds)) {
            Yii::warning('nexus-community-assistant: PERIODIC_REMINDER_SPACE_IDS nicht konfiguriert -- ueberspringe ' . $name);
            return false;
        }

        $poster = new PosterService($this->assistantUserId);
        foreach ($spaceIds as $spaceId) {
            try {
                $poster->postInSpace($spaceId, $text);
            } catch (\Throwable $e) {
                Yii::error("nexus-community-assistant: {$name} -> Space {$spaceId} fehlgeschlagen: " . $e->getMessage());
            }
        }

        ReminderState::laufMarkieren($name, $jetzt);
        return true;
    }

    /**
     * @return int[]
     */
    private function zielSpaceIds(): array
    {
        $roh = getenv('PERIODIC_REMINDER_SPACE_IDS');
        if (empty($roh)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $roh))));
    }
}
