<?php

namespace nexus\modules\projekte\services;

use humhub\modules\post\models\Post;
use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Karte;
use nexus\modules\projekte\models\Option;
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
        $bis = $karte->abstimmung_bis ? ' – bis ' . Yii::$app->formatter->asDate($karte->abstimmung_bis, 'php:D d.m.') : '';
        if ($karte->istAuswahl()) {
            $anzahl = (int)Option::find()->where(['karte_id' => $karte->id])->count();
            $text = sprintf(
                "🖼️ **Bitte wähle mit: %s – %s**\n\n%d Entwürfe stehen zur Auswahl. Gib zu jedem Entwurf deine Stimme%s: Zustimmung, Bedenken oder schwerwiegender Einwand (Bedenken und Einwand bitte kurz begründen). Gewählt wird der Entwurf ohne schwerwiegenden Einwand mit den meisten Zustimmungen.\n%s",
                $projekt->titel, $karte->titel, $anzahl, $bis, $karte->url(true)
            );
        } elseif ($karte->istVorschlag()) {
            $bitte = match (Entscheidung::regelFuer($karte)) {
                'konsent' => 'Bitte nimm Stellung%s: zustimmen, Zustimmung mit Bedenken oder schwerwiegender Einwand (mit Begründung). Angenommen ist der Vorschlag, wenn niemand einen schwerwiegenden Einwand hat.',
                'mehrheit' => 'Bitte stimm ab%s: Ja, Nein oder Enthaltung.',
                default => 'Bitte sag mit einem Klick, ob es so passt oder was du ändern würdest%s.',
            };
            $text = sprintf("💡 **Vorschlag zur Entscheidung: %s – %s**\n\n%s\n\n%s\n%s",
                $projekt->titel, $karte->titel, trim((string)$karte->zusammenfassung), sprintf($bitte, $bis), $karte->url(true));
        } else {
            $text = sprintf(
                "%s **%s – %s** steht zur Abstimmung.\n\nBitte schau es dir an und sag mit einem Klick, ob es so passt oder was du ändern würdest%s:\n%s",
                self::SYMBOL, $projekt->titel, $karte->titel, $bis, $karte->url(true)
            );
        }
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

    /** Aenderungswunsch, Bedenken oder Einwand mit Begruendung: an Verantwortliche und Leitung */
    public static function rueckmeldungMitText(Karte $karte, Stimme $stimme, User $von): void
    {
        $was = [
            'aenderung' => '✏️ %s hat einen Änderungswunsch zu **%s**:',
            'bedenken' => '⚠️ %s stimmt **%s** mit Bedenken zu:',
            'einwand' => '✋ %s hat einen schwerwiegenden Einwand gegen **%s**:',
        ][$stimme->art] ?? '💬 %s zu **%s**:';
        $text = sprintf($was . "\n\n> %s\n\n%s",
            $von->displayName, $karte->titel, str_replace("\n", "\n> ", trim((string)$stimme->text)), $karte->url(true));
        foreach (self::verantwortlicheUndLeitung($karte) as $mensch) {
            if ((int)$mensch->id !== (int)$von->id) {
                self::senden($mensch, $karte->projekt, $text);
            }
        }
    }

    /** Bild-Auswahl: Bedenken oder Einwand zu einem Entwurf -- an Verantwortliche und Leitung */
    public static function entwurfRueckmeldung(Karte $karte, string $entwurf, Stimme $stimme, User $von): void
    {
        $was = $stimme->art === 'einwand' ? '✋ %s hat einen schwerwiegenden Einwand gegen %s in **%s**:' : '⚠️ %s hat Bedenken zu %s in **%s**:';
        $text = sprintf($was . "\n\n> %s\n\n%s",
            $von->displayName, $entwurf, $karte->titel, str_replace("\n", "\n> ", trim((string)$stimme->text)), $karte->url(true));
        foreach (self::verantwortlicheUndLeitung($karte) as $mensch) {
            if ((int)$mensch->id !== (int)$von->id) {
                self::senden($mensch, $karte->projekt, $text);
            }
        }
    }

    /** Bild-Auswahl entschieden: an alle, die abgestimmt oder einen Entwurf eingestellt haben */
    public static function auswahlErgebnis(Karte $karte, User $von): void
    {
        $ids = array_map('intval', Stimme::find()->select('user_id')->where(['karte_id' => $karte->id, 'runde' => $karte->runde])->column());
        $ids = array_merge($ids, array_map('intval', Option::find()->select('created_by')->where(['karte_id' => $karte->id])->column()));
        $ids[] = (int)$karte->verantwortlich_id;
        $ids = array_diff(array_unique(array_filter($ids)), [(int)$von->id]);
        $text = sprintf("🖼️ Für **%s** ist entschieden: %s.\n\n%s", $karte->titel, (string)$karte->ergebnis_text, $karte->url(true));
        foreach (User::find()->where(['id' => $ids])->all() as $mensch) {
            self::senden($mensch, $karte->projekt, $text);
        }
    }

    /** Neue Fassung waehrend der Runde: wer schon Stellung genommen hat, wird um eine neue gebeten */
    public static function neueRunde(Karte $karte, User $von): void
    {
        $ids = array_map('intval', Stimme::find()->select('user_id')->where(['karte_id' => $karte->id, 'runde' => $karte->runde - 1])->column());
        $ids = array_diff($ids, [(int)$von->id]);
        if ($ids === []) {
            return;
        }
        $text = sprintf("🔄 **%s** wurde geändert (von %s). Deine bisherige Stellungnahme galt der alten Fassung – bitte schau es dir noch einmal an und nimm neu Stellung.\n\n%s",
            $karte->titel, $von->displayName, $karte->url(true));
        foreach (User::find()->where(['id' => $ids])->all() as $mensch) {
            self::senden($mensch, $karte->projekt, $text);
        }
    }

    /** Ergebnis festgehalten: an alle, die Stellung genommen haben, und wer den Vorschlag eingebracht hat */
    public static function ergebnis(Karte $karte, User $von): void
    {
        $ids = array_map('intval', Stimme::find()->select('user_id')->where(['karte_id' => $karte->id, 'runde' => $karte->runde])->column());
        $ids[] = (int)$karte->verantwortlich_id;
        $ids[] = (int)$karte->created_by;
        $ids = array_diff(array_unique(array_filter($ids)), [(int)$von->id]);
        if ($ids === []) {
            return;
        }
        $wort = ['angenommen' => '✅ angenommen', 'abgelehnt' => '❌ abgelehnt', 'zurueckgezogen' => '↩️ zurückgezogen'][$karte->ergebnis] ?? (string)$karte->ergebnis;
        $weiter = $karte->ergebnis === 'angenommen' && $karte->projekt->weitergabe
            ? "\nAls Nächstes wird er vorgelegt bei: " . $karte->projekt->weitergabe . '.' : '';
        $stand = $karte->ergebnis_text ? ' (' . $karte->ergebnis_text . ')' : '';
        $text = sprintf("Der Vorschlag **%s** ist %s%s.%s\n\n%s", $karte->titel, $wort, $stand, $weiter, $karte->url(true));
        foreach (User::find()->where(['id' => $ids])->all() as $mensch) {
            self::senden($mensch, $karte->projekt, $text);
        }
    }

    /** Runde vorbei: die Leitung wird einmal gebeten, das Ergebnis festzuhalten */
    public static function bitteAuswerten(Karte $karte): void
    {
        $text = sprintf("🗳️ Die Runde zu **%s** ist zu Ende (%s). Bitte werte sie aus und halte das Ergebnis fest.\n\n%s",
            $karte->titel, Entscheidung::auswertung($karte)['text'], $karte->url(true));
        foreach ($karte->projekt->leitungen() as $mensch) {
            self::senden($mensch, $karte->projekt, $text);
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
        $klick = Entscheidung::regelFuer($karte) === 'einfach' ? 'Ein Klick genügt: „Passt so“ oder „Ich habe eine Änderung“.' : 'Ein Klick genügt.';
        $text = sprintf("🗳️ Zu **%s** fehlt noch deine Rückmeldung – die Abstimmung endet morgen. %s\n\n%s",
            $karte->titel, $klick, $karte->url(true));
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
