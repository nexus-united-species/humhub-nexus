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

    // Seit 19.10.2026 ohne Betrag "5 €" und ohne Gutschrift-Versprechen: die Foerderseite sagt
    // ausdruecklich, dass ein Beitrag keine Token oder spaeteren Ansprueche begruendet.
    private const SPENDENAUFRUF_TEXT = <<<'TEXT'
💛 N.E.X.U.S. mittragen – gemeinsam statt allein

N.E.X.U.S. entsteht nicht aus Kapital, sondern aus Menschen, die gemeinsam etwas Neues aufbauen wollen.

Trotzdem braucht die Umsetzung reale Mittel: Entwicklung der OneApp, Infrastruktur und Dienste, Werkzeuge, Dokumentation und laufende Projektarbeit.

Ein herzliches Danke an alle, die schon etwas beigetragen haben. Den größten Teil der bisherigen Kosten hat Josh bisher selbst getragen.

Wir sind inzwischen über 100 Menschen hier im Portal. Wenn jede und jeder etwas beiträgt, sind zumindest die laufenden Kosten gedeckt – nicht, weil Einzelne viel leisten müssen, sondern weil viele gemeinsam ein Stück Verantwortung tragen.

Ob 10, 20 oder 50 € – monatlich oder einmalig, ganz nach deinem Ermessen. Jeder Beitrag zählt.

Alles dazu findest du auf unserer neuen Förderseite – mit verschiedenen Zahlungswegen, auch per Banküberweisung:

👉 {spendenLink}

Dort siehst du auch, wie viel schon zusammengekommen ist und wofür das Geld eingesetzt wird.
{finanzen}{zusatz}
N.E.X.U.S. ist ein Gemeinschaftsprojekt. Danke an alle, die es möglich machen – mit Zeit, Wissen, Herz, Vertrauen oder finanzieller Unterstützung. 💛
TEXT;

    private const SPENDENAUFRUF_ZUSATZ = <<<'TEXT'

Lieber über Ko-fi? Das geht weiterhin: 👉 {link}
(Der Zugang zur Unterstützer-Bibliothek läuft über Ko-fi.)

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
        return $this->fallsFaelligPosten('donation_reminder', self::SPENDENAUFRUF_INTERVALL_TAGE, self::spendenText(), self::spendenKreise());
    }

    public static function spendenText(): string
    {
        $modul = \nexus\modules\communityAssistant\Module::instanz();
        $zusatz = trim((string)$modul->spendenLinkZusatz) !== ''
            ? str_replace('{link}', trim((string)$modul->spendenLinkZusatz), self::SPENDENAUFRUF_ZUSATZ)
            : "\n";
        return strtr(self::SPENDENAUFRUF_TEXT, [
            '{spendenLink}' => (string)$modul->spendenLink,
            '{finanzen}' => self::finanzenAbsatz(),
            '{zusatz}' => $zusatz,
        ]);
    }

    /**
     * Hinweis auf die Finanzierungs-Ampel im Portal (Modul nexus-finanzen, seit 29.10.2026). Nur wenn das
     * Modul laeuft UND die Ampel fuer alle sichtbar ist: sonst bekaeme jedes Mitglied hinter dem Link eine
     * Fehlerseite (solange "Fuer alle sichtbar" aus ist, sehen sie nur die Finanzverantwortlichen).
     */
    private static function finanzenAbsatz(): string
    {
        $finanzen = Yii::$app->getModule('nexus-finanzen');
        if ($finanzen === null || !method_exists($finanzen, 'fuerAlle') || !$finanzen->fuerAlle()) {
            return '';
        }
        $url = rtrim((string)Yii::$app->settings->get('baseUrl'), '/') . '/nexus-finanzen/ampel/index';
        return "
📊 Und hier im Portal, unter **Finanzierung**, siehst du jeden Monat, was N.E.X.U.S. braucht, was davon schon bezahlt ist und was noch offen ist – offen und nachvollziehbar für alle:

👉 {$url}
";
    }

    /**
     * Seit 19.10.2026 eigene Liste (Moduleinstellung "spendenKreise"), damit der Aufruf nur in
     * ausgewaehlten Kreisen erscheint, die Selbstvorstellung aber weiter in allen.
     * @return int[]|null null = dieselben Kreise wie die Selbstvorstellung
     */
    public static function spendenKreise(): ?array
    {
        $kreise = array_values(array_filter(array_map('intval', (array)\nexus\modules\communityAssistant\Module::instanz()->spendenKreise)));
        return $kreise ?: null;
    }

    public function selbstvorstellungFallsFaellig(): bool
    {
        return $this->fallsFaelligPosten('self_intro_reminder', self::SELBSTVORSTELLUNG_INTERVALL_TAGE, self::SELBSTVORSTELLUNG_TEXT);
    }

    private function fallsFaelligPosten(string $name, int $intervallTage, string $text, ?array $kreise = null): bool
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

        $spaceIds = $kreise ?? $this->zielSpaceIds();
        if (empty($spaceIds)) {
            Yii::warning('nexus-community-assistant: PERIODIC_REMINDER_SPACE_IDS nicht konfiguriert -- ueberspringe ' . $name);
            return false;
        }

        $poster = new PosterService($this->assistantUserId);
        foreach ($spaceIds as $spaceId) {
            try {
                $poster->postInSpace($spaceId, Gespraeche::mitHinweisAutomatisch($text));
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
