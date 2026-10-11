<?php

namespace nexus\modules\wissen\services;

use humhub\modules\user\models\User;
use nexus\modules\wissen\models\Artikel;
use nexus\modules\wissen\models\Vorschlag;
use Throwable;
use Yii;

/**
 * Private Nachrichten (deutsch) an die Bearbeitenden und an Vorschlagende -- ueber den Nachrichtenversand von
 * nexus-arbeitszeit (fortlaufende Unterhaltung je Thema). Fehlt das Modul, werden die Vorschlaege
 * trotzdem gespeichert und erscheinen in der Vorschlagsliste der Sammlung.
 */
class Nachrichten
{
    private const VERSAND = 'nexus\modules\arbeitszeit\services\Benachrichtigung';

    public static function verfuegbar(): bool
    {
        return class_exists(self::VERSAND);
    }

    public static function neuerVorschlag(Vorschlag $v, Artikel $artikel, Sammlung $sammlung, User $von): void
    {
        $art = $v->art === 'ergaenzen' ? 'möchte etwas ergänzen' : 'möchte etwas ändern';
        $text = sprintf(
            "%s **%s** %s – Artikel **%s**:\n\n> %s\n\nZum Artikel: %s\nAlle Vorschläge: %s",
            $sammlung->emoji(),
            $von->displayName,
            $art,
            $artikel->titel,
            str_replace("\n", "\n> ", trim($v->text)),
            $artikel->url($sammlung, true),
            $sammlung->url('vorschlaege', [], true)
        );
        foreach ($sammlung->bearbeitende() as $mensch) {
            if ((int)$mensch->id !== (int)$von->id) {
                self::senden((int)$mensch->id, self::titel($sammlung, 'Vorschläge'), $text);
            }
        }
    }

    public static function entschieden(Vorschlag $v, Artikel $artikel, Sammlung $sammlung): void
    {
        $ergebnis = $v->status === 'uebernommen' ? 'übernommen ✅' : 'nicht übernommen';
        $text = sprintf("Danke für deinen Vorschlag zum Artikel **%s** – er wurde %s.", $artikel->titel, $ergebnis);
        if (trim((string)$v->antwort) !== '') {
            $text .= "\n\n> " . str_replace("\n", "\n> ", trim((string)$v->antwort));
        }
        $text .= "\n\nZum Artikel: " . $artikel->url($sammlung, true);
        self::senden((int)$v->created_by, self::titel($sammlung, 'Dein Vorschlag'), $text);
    }

    /** @param Artikel[] $faellig */
    public static function pruefungenFaellig(array $faellig, Sammlung $sammlung): void
    {
        $zeilen = [$sammlung->emoji() . ' Diese Artikel sind zur erneuten Prüfung fällig:', ''];
        foreach ($faellig as $a) {
            $zeilen[] = '- ' . $a->titel . ' (fällig ' . Yii::$app->formatter->asDate($a->wiedervorlage, 'medium') . '): ' . $a->url($sammlung, true);
        }
        foreach ($sammlung->bearbeitende() as $mensch) {
            self::senden((int)$mensch->id, self::titel($sammlung, 'Prüfungen fällig'), implode("\n", $zeilen));
        }
    }

    private static function titel(Sammlung $sammlung, string $worum): string
    {
        return $sammlung->emoji() . ' ' . $sammlung->name('de') . ': ' . $worum;
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
            Yii::error('nexus-wissen: Nachricht nicht gesendet: ' . $e->getMessage(), 'nexus-wissen');
        }
    }
}
