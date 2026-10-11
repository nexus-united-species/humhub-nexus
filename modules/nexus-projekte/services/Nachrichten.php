<?php

namespace nexus\modules\projekte\services;

use humhub\modules\post\models\Post;
use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\models\Stimme;
use nexus\modules\projekte\Module;
use Throwable;
use Yii;

/**
 * Beitraege im Kreis und private Nachrichten (deutsch).
 *
 * Bewusst sparsam, damit niemand mit Meldungen ueberhaeuft wird:
 * - Kreis-Beitrag nur, wenn eine Kachel zur Abstimmung geht (dann ist der ganze Kreis gefragt)
 * - private Nachricht, wenn man verantwortlich wird oder mithelfen soll, bei einem Aenderungswunsch
 *   (an Verantwortliche und Leitung), wenn jemand eine Kachel uebernimmt (an die Leitung)
 * - Erinnerungen (Events::onTaeglich): einen Tag vor der Frist, einmal bei Ueberschreitung
 *   (Verantwortliche + Leitung, nicht oeffentlich), einen Tag vor Abstimmungsende an Beteiligte ohne
 *   Rueckmeldung
 *
 * Private Nachrichten laufen ueber nexus-arbeitszeit (eine fortlaufende Unterhaltung je Projekt).
 * Fehlt das Modul, entfallen sie; die Tafel und "Meine Aufgaben" zeigen trotzdem alles.
 */
class Nachrichten
{
    private const VERSAND = 'nexus\modules\arbeitszeit\services\Benachrichtigung';
    private const SYMBOL = '📋';

    /** Kreis-Beitrag, verfasst von dem (angemeldeten) Menschen, der die Kachel zur Abstimmung gegeben hat */
    public static function abstimmungImKreis(Karte $karte): bool
    {
        $projekt = $karte->projekt;
        $text = sprintf(
            "%s **%s – %s** steht zur Abstimmung.\n\nBitte schau es dir an und sag mit einem Klick, ob es so passt oder was du ändern würdest%s:\n%s",
            self::SYMBOL,
            $projekt->titel,
            $karte->titel,
            $karte->abstimmung_bis ? ' – bis ' . Yii::$app->formatter->asDate($karte->abstimmung_bis, 'php:D d.m.') : '',
            $karte->url(true)
        );
        try {
            $post = new Post($projekt->kreis);
            $post->message = $text;
            return $post->save();
        } catch (Throwable $e) {
            Yii::error('nexus-projekte: Beitrag nicht angelegt: ' . $e->getMessage(), 'nexus-projekte');
            return false;
        }
    }

    public static function zugewiesen(Karte $karte, User $an, string $rolle, User $von): void
    {
        if ((int)$an->id === (int)$von->id) {
            return;
        }
        $was = $rolle === 'verantwortlich' ? 'Du bist jetzt verantwortlich für' : 'Du hilfst jetzt mit bei';
        $frist = $karte->frist ? ' Frist: ' . Yii::$app->formatter->asDate($karte->frist, 'medium') . '.' : '';
        self::senden($an, $karte->projekt, sprintf("%s **%s** (eingetragen von %s).%s\n\n%s", $was, $karte->titel, $von->displayName, $frist, $karte->url(true)));
    }

    public static function uebernommen(Karte $karte, User $von): void
    {
        foreach ($karte->projekt->leitungen() as $leitung) {
            if ((int)$leitung->id !== (int)$von->id) {
                self::senden($leitung, $karte->projekt, sprintf("🙋 %s übernimmt **%s**.\n\n%s", $von->displayName, $karte->titel, $karte->url(true)));
            }
        }
    }

    public static function aenderungswunsch(Karte $karte, Stimme $stimme, User $von): void
    {
        $text = sprintf("✏️ %s hat einen Änderungswunsch zu **%s**:\n\n> %s\n\n%s",
            $von->displayName, $karte->titel, str_replace("\n", "\n> ", trim((string)$stimme->text)), $karte->url(true));
        foreach (self::verantwortlicheUndLeitung($karte) as $mensch) {
            if ((int)$mensch->id !== (int)$von->id) {
                self::senden($mensch, $karte->projekt, $text);
            }
        }
    }

    public static function fristMorgen(Karte $karte): void
    {
        $text = sprintf("⏰ Erinnerung: **%s** soll morgen fertig sein (%s). Brauchst du mehr Zeit oder Hilfe? Sag der Projektleitung Bescheid.\n\n%s",
            $karte->titel, Yii::$app->formatter->asDate($karte->frist, 'medium'), $karte->url(true));
        $empfaenger = $karte->verantwortlich ? [$karte->verantwortlich] : [];
        foreach (array_merge($empfaenger, $karte->helfer()) as $mensch) {
            self::senden($mensch, $karte->projekt, $text);
        }
    }

    public static function ueberfaellig(Karte $karte): void
    {
        $text = sprintf("⏳ **%s** war bis %s geplant und ist noch nicht fertig. Passt die Frist noch, oder braucht es Unterstützung?\n\n%s",
            $karte->titel, Yii::$app->formatter->asDate($karte->frist, 'medium'), $karte->url(true));
        foreach (self::verantwortlicheUndLeitung($karte) as $mensch) {
            self::senden($mensch, $karte->projekt, $text);
        }
    }

    /** @param User[] $menschen */
    public static function abstimmungEndetMorgen(Karte $karte, array $menschen): void
    {
        $text = sprintf("🗳️ Zu **%s** fehlt noch deine Rückmeldung – die Abstimmung endet morgen. Ein Klick genügt: „Passt so“ oder „Ich habe eine Änderung“.\n\n%s",
            $karte->titel, $karte->url(true));
        foreach ($menschen as $mensch) {
            self::senden($mensch, $karte->projekt, $text);
        }
    }

    /** @return User[] */
    private static function verantwortlicheUndLeitung(Karte $karte): array
    {
        $liste = [];
        foreach (array_merge([$karte->verantwortlich], $karte->projekt->leitungen()) as $m) {
            if ($m !== null) {
                $liste[$m->id] = $m;
            }
        }
        return array_values($liste);
    }

    private static function senden(User $an, Projekt $projekt, string $text): void
    {
        $modul = Module::instanz();
        if ($modul === null || !$modul->erinnern || !class_exists(self::VERSAND) || $an->status != User::STATUS_ENABLED) {
            return;
        }
        try {
            $versand = self::VERSAND;
            $versand::senden((int)$an->id, self::SYMBOL . ' Projekt: ' . $projekt->titel, $text);
        } catch (Throwable $e) {
            Yii::error('nexus-projekte: Nachricht nicht gesendet: ' . $e->getMessage(), 'nexus-projekte');
        }
    }
}
