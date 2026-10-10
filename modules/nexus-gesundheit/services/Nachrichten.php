<?php

namespace nexus\modules\gesundheit\services;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\gesundheit\models\Artikel;
use nexus\modules\gesundheit\models\Vorschlag;
use Throwable;
use Yii;

/**
 * Nachrichten an Admins und Vorschlagende -- ueber den Nachrichtenversand von nexus-arbeitszeit
 * (Portal-Nachricht des Assistenten in einer fortlaufenden Unterhaltung). Fehlt das Modul, werden
 * die Vorschlaege trotzdem gespeichert und erscheinen in der Vorschlagsliste.
 */
class Nachrichten
{
    public const TITEL_ADMIN = '🩺 Gesundheitswissen: Vorschläge';
    public const TITEL_PRUEFUNG = '🩺 Gesundheitswissen: Prüfungen fällig';
    public const TITEL_MENSCH = '🩺 Dein Vorschlag zum Gesundheitswissen';
    private const VERSAND = 'nexus\modules\arbeitszeit\services\Benachrichtigung';
    private const ADMINS = 'nexus\modules\arbeitszeit\services\ZeitService';

    public static function verfuegbar(): bool
    {
        return class_exists(self::VERSAND) && class_exists(self::ADMINS);
    }

    /** @return User[] */
    public static function admins(): array
    {
        $quelle = self::ADMINS;
        return self::verfuegbar() ? $quelle::admins() : [];
    }

    public static function neuerVorschlag(Vorschlag $v, Artikel $artikel, Space $kreis, User $von): void
    {
        $art = $v->art === 'ergaenzen' ? 'möchte etwas ergänzen' : 'möchte etwas ändern';
        $text = sprintf(
            "🩺 **%s** %s – Artikel **%s**:\n\n> %s\n\nZum Artikel: %s\nAlle Vorschläge: %s",
            $von->displayName,
            $art,
            $artikel->titel,
            str_replace("\n", "\n> ", trim($v->text)),
            self::voll($artikel->url($kreis)),
            self::voll($kreis->createUrl('/nexus-gesundheit/wissen/vorschlaege'))
        );
        foreach (self::admins() as $admin) {
            self::senden((int)$admin->id, self::TITEL_ADMIN, $text);
        }
    }

    public static function entschieden(Vorschlag $v, Artikel $artikel, Space $kreis): void
    {
        $ergebnis = $v->status === 'uebernommen' ? 'übernommen ✅' : 'nicht übernommen';
        $text = sprintf("Danke für deinen Vorschlag zum Artikel **%s** – er wurde %s.", $artikel->titel, $ergebnis);
        if (trim((string)$v->antwort) !== '') {
            $text .= "\n\n> " . str_replace("\n", "\n> ", trim((string)$v->antwort));
        }
        $text .= "\n\nZum Artikel: " . self::voll($artikel->url($kreis));
        self::senden((int)$v->created_by, self::TITEL_MENSCH, $text);
    }

    /** @param Artikel[] $faellig */
    public static function pruefungenFaellig(array $faellig, Space $kreis): void
    {
        $zeilen = ['🩺 Diese Artikel sind zur erneuten Prüfung fällig (Wiedervorlage der Redaktion):', ''];
        foreach ($faellig as $a) {
            $zeilen[] = '- ' . $a->titel . ' (fällig ' . Yii::$app->formatter->asDate($a->wiedervorlage, 'medium') . '): ' . self::voll($a->url($kreis));
        }
        foreach (self::admins() as $admin) {
            self::senden((int)$admin->id, self::TITEL_PRUEFUNG, implode("\n", $zeilen));
        }
    }

    private static function senden(int $an, string $titel, string $text): void
    {
        if (!self::verfuegbar()) {
            return;
        }
        try {
            $versand = self::VERSAND;
            $versand::senden($an, $titel, $text);
        } catch (Throwable $e) {
            Yii::error('nexus-gesundheit: Nachricht nicht gesendet: ' . $e->getMessage(), 'nexus-gesundheit');
        }
    }

    private static function voll(string $pfad): string
    {
        if (preg_match('#^https?://#', $pfad)) {
            return $pfad;
        }
        $pfad = preg_replace('#^.*?(/(?:s|u)/)#', '$1', $pfad);
        return rtrim((string)Yii::$app->settings->get('baseUrl'), '/') . '/' . ltrim($pfad, '/');
    }
}
