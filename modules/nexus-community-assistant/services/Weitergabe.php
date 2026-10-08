<?php

namespace nexus\modules\communityAssistant\services;

use humhub\modules\user\models\User;
use Throwable;
use Yii;

/**
 * "Wenn ich nicht weiter weiss, gebe ich es an einen Menschen aus dem Team weiter" (Josh,
 * 02.10.2026, in Novas Begruessung). Die KI haengt dafuer AssistentPersona::MARKE_MENSCH an ihre
 * Antwort; hier wird die Marke entfernt (das Mitglied sieht sie nie) und die Admins bekommen die
 * Frage in ihre fortlaufende Unterhaltung "🙋 Nova braucht einen Menschen".
 *
 * Die Admins sehen die Unterhaltung zwischen Mitglied und Nova NICHT (private Nachricht) -- deshalb
 * steht die Frage woertlich in der Meldung, mit Link zum Profil, um den Menschen anzuschreiben.
 */
class Weitergabe
{
    public const TITEL = '🙋 Nova braucht einen Menschen';

    /**
     * @return array{0: string, 1: bool} Antwort ohne Marke, und ob weitergegeben werden soll
     */
    public static function zerlegen(string $antwort): array
    {
        if (!str_contains($antwort, AssistentPersona::MARKE_MENSCH)) {
            return [$antwort, false];
        }
        return [trim(str_replace(AssistentPersona::MARKE_MENSCH, '', $antwort)), true];
    }

    public static function anMenschen(?User $mitglied, string $frage, string $antwort, string $wo): void
    {
        // Seit 07.10.2026 geht das an das Support-Team (Modul nexus-hilfe: Anfrage mit Nummer im
        // Kreis Support-Team, Nachricht an das Support-Team). Nur ohne das Modul wie frueher an die Admins.
        $support = 'nexus\modules\hilfe\services\Support';
        if (class_exists($support) && $support::verfuegbar()) {
            try {
                $support::anlegen($mitglied, 'nova', "Frage an Nova ({$wo}):\n" . trim($frage)
                    . "\n\nNovas Antwort:\n" . trim($antwort), 'nova', false);
                return;
            } catch (Throwable $e) {
                Yii::error('nexus-community-assistant: Weitergabe an den Support fehlgeschlagen, jetzt an die Admins: ' . $e->getMessage(), 'nexus-community-assistant');
            }
        }

        $benachrichtigung = 'nexus\modules\arbeitszeit\services\Benachrichtigung';
        $adminsQuelle = 'nexus\modules\arbeitszeit\services\ZeitService';
        if (!class_exists($benachrichtigung) || !class_exists($adminsQuelle)) {
            Yii::warning('nexus-community-assistant: Weitergabe nicht moeglich (Modul nexus-arbeitszeit fehlt).', 'nexus-community-assistant');
            return;
        }
        $name = $mitglied ? $mitglied->displayName . ' (@' . $mitglied->username . ')' : 'Unbekannt';
        $profil = $mitglied ? $benachrichtigung::link($mitglied->getUrl()) : '';
        $text = "🙋 **{$name}** hat Nova etwas gefragt, das ein Mensch klären sollte ({$wo}):\n\n"
            . '> ' . str_replace("\n", "\n> ", trim($frage)) . "\n\n"
            . "Nova hat geantwortet:\n\n> " . str_replace("\n", "\n> ", trim($antwort)) . "\n\n"
            . "Bitte meldet euch direkt bei der Person: {$profil}";
        try {
            foreach ($adminsQuelle::admins() as $admin) {
                $benachrichtigung::senden((int)$admin->id, self::TITEL, $text);
            }
        } catch (Throwable $e) {
            Yii::error('nexus-community-assistant: Weitergabe fehlgeschlagen: ' . $e->getMessage(), 'nexus-community-assistant');
        }
    }
}
