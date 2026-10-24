<?php

namespace nexus\modules\projekte\services;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\projekte\models\Projekt;
use nexus\modules\projekte\models\Team;
use nexus\modules\projekte\models\TeamAufgabe;
use nexus\modules\projekte\models\TeamMitglied;
use nexus\modules\projekte\models\TeamPost;
use nexus\modules\projekte\Module;
use RuntimeException;
use Throwable;
use Yii;

/**
 * Teams (25.10.2026): Arbeit, die Menschen dauerhaft gemeinsam tragen -- damit sie nicht immer wieder bei einer
 * einzelnen Person landet (Anlass: Josh bekam an einem Tag 51 Nachrichten). Ablauf:
 *
 * 1. Eine Kreisleitung schreibt ein Team aus: Auftrag, Aufgaben (regelmaessig/einmalig, Aufwand).
 * 2. Interessierte klicken "Da helfe ich mit" (Aufgaben, Zeit, ein Satz). Die Leitung spricht mit ihnen und
 *    nimmt sie dann auf (Josh: "man muss sich vorher unterhalten").
 * 3. Das Team traegt Zustaendigkeit und Vertretung selbst ein, schreibt seine Arbeitsweise auf, meldet
 *    Abwesenheit ("weg bis") und gibt Aufgaben beim Aufhoeren sichtbar zurueck.
 * 4. Team-Postfach: Mitglieder schreiben "an das Team", Josh/Leitungen geben eigene Unterhaltungen an ein Team
 *    weiter. Es sind normale Unterhaltungen des Nachrichten-Moduls -- Antworten erreichen den Absender wie immer.
 *    "Uebernehmen" verhindert doppelte Antworten; "braucht die Kreisleitung" holt die Leitung gezielt dazu.
 *
 * Wer darf ausschreiben: Kreisbesitz und Kreis-Admins (und Systemadmins); in den Kreisen aus der Einstellung
 * 'teamsNurAdmins' (Kreis 1) nur Systemadmins. Leiten (aufnehmen, Aufgaben/Status aendern): wer ausgeschrieben
 * hat, die Koordination und wer ausschreiben darf.
 */
class Teams
{
    private const VERSAND = 'nexus\modules\arbeitszeit\services\Benachrichtigung';
    private const MAIL_NACHRICHT = 'humhub\modules\mail\models\Message';
    private const MAIL_EINTRAG = 'humhub\modules\mail\models\MessageEntry';
    public const SYMBOL = '👥';

    // ── Rechte ───────────────────────────────────────────────────────────────

    public static function darfAusschreiben(Space $kreis, ?User $mensch): bool
    {
        if ($mensch === null) {
            return false;
        }
        $nurAdmins = array_map('intval', (array)(Module::instanz()->teamsNurAdmins ?? []));
        if (in_array((int)$kreis->id, $nurAdmins, true)) {
            return $mensch->isSystemAdmin();
        }
        return Rechte::istKreisAdmin($kreis, $mensch);
    }

    public static function darfLeiten(Team $team, ?User $mensch): bool
    {
        return $mensch !== null && ((int)$team->created_by === (int)$mensch->id || (int)$team->koordination_id === (int)$mensch->id
            || self::darfAusschreiben($team->kreis, $mensch));
    }

    public static function istMitglied(Team $team, ?User $mensch): bool
    {
        return $mensch !== null && in_array((int)$mensch->id, $team->mitgliedIds(), true);
    }

    public static function darfMitmachen(Team $team, ?User $mensch): bool
    {
        if ($mensch === null || $team->status === 'pausiert' || !Rechte::istMitglied($team->kreis, $mensch)) {
            return false;
        }
        $m = $team->mitgliedschaft((int)$mensch->id);
        return $m === null || in_array($m->status, ['abgesagt', 'beendet'], true);
    }

    /** Postfach sehen und bearbeiten: Teammitglieder und Leitung */
    public static function darfPostfach(Team $team, ?User $mensch): bool
    {
        return self::istMitglied($team, $mensch) || self::darfLeiten($team, $mensch);
    }

    public static function darfAnschreiben(Team $team, ?User $mensch): bool
    {
        return $mensch !== null && Rechte::istMitglied($team->kreis, $mensch) && self::mailDa();
    }

    public static function mailDa(): bool
    {
        return Yii::$app->hasModule('mail') && class_exists(self::MAIL_NACHRICHT);
    }

    // ── Ausschreiben ─────────────────────────────────────────────────────────

    /**
     * Team speichern; ein neues bekommt seine eigene Projekt-Tafel ("Team: <Titel>", Leitung = wer ausschreibt).
     * @param array<int, array{id?: int, titel: string, art: string, aufwand: string}> $aufgaben
     */
    public static function speichern(Team $team, array $aufgaben, User $ich): void
    {
        $neu = $team->isNewRecord;
        if ($neu) {
            $team->created_by = $ich->id;
        }
        if (!$team->save()) {
            throw new RuntimeException(implode(' ', $team->getErrorSummary(true)));
        }
        if ($neu && Module::istAn($team->kreis)) {
            $projekt = new Projekt(['space_id' => $team->space_id, 'titel' => Texte::t('team_tafel_praefix') . ' ' . $team->titel,
                'ziel' => $team->auftrag, 'status' => 'aktiv', 'created_by' => $ich->id]);
            if ($projekt->save(false)) {
                $projekt->setzeLeitung([(int)$ich->id]);
                $team->updateAttributes(['projekt_id' => $projekt->id]);
            }
        }
        self::aufgabenSpeichern($team, $aufgaben);
    }

    /** Bekannte Aufgaben behalten Zustaendigkeit und Vertretung; leere Zeilen fallen weg */
    private static function aufgabenSpeichern(Team $team, array $zeilen): void
    {
        $vorhanden = [];
        foreach (TeamAufgabe::find()->where(['team_id' => $team->id])->all() as $a) {
            $vorhanden[(int)$a->id] = $a;
        }
        $behalten = [];
        $i = 0;
        foreach (array_slice($zeilen, 0, TeamAufgabe::MAX) as $z) {
            $titel = mb_substr(trim((string)($z['titel'] ?? '')), 0, 190);
            if ($titel === '') {
                continue;
            }
            $id = (int)($z['id'] ?? 0);
            $a = $vorhanden[$id] ?? new TeamAufgabe(['team_id' => $team->id]);
            $a->titel = $titel;
            $a->art = in_array($z['art'] ?? '', TeamAufgabe::ARTEN, true) ? $z['art'] : 'regelmaessig';
            $a->aufwand = mb_substr(trim((string)($z['aufwand'] ?? '')), 0, 100) ?: null;
            $a->sortierung = $i++;
            $a->save(false);
            $behalten[] = (int)$a->id;
        }
        foreach ($vorhanden as $id => $a) {
            if (!in_array($id, $behalten, true)) {
                $a->delete();
            }
        }
    }

    // ── Mitmachen und Aufnahme ──────────────────────────────────────────────

    public static function mitmachen(Team $team, User $ich, array $aufgabenIds, string $zeit, string $nachricht): TeamMitglied
    {
        $m = $team->mitgliedschaft((int)$ich->id) ?? new TeamMitglied(['team_id' => $team->id, 'user_id' => $ich->id]);
        $gueltig = array_map('intval', TeamAufgabe::find()->select('id')->where(['team_id' => $team->id, 'id' => array_map('intval', $aufgabenIds)])->column());
        $m->status = 'angefragt';
        $m->aufgaben = implode(',', $gueltig) ?: null;
        $m->zeit = mb_substr(trim($zeit), 0, 190) ?: null;
        $m->nachricht = mb_substr(trim($nachricht), 0, 3000) ?: null;
        $m->created_at = date('Y-m-d H:i:s');
        $m->entschieden_von = $m->entschieden_am = $m->weg_bis = null;
        $m->save(false);

        $namen = $gueltig === [] ? '–' : implode(', ', TeamAufgabe::find()->select('titel')->where(['id' => $gueltig])->column());
        $text = sprintf("%s **%s** möchte im Team **%s** mithelfen.\n\nAufgaben: %s\nZeit: %s%s\n\nBitte sprecht kurz miteinander und nehmt %s dann auf der Teamseite auf (oder sagt freundlich ab):\n%s",
            self::SYMBOL, $ich->displayName, $team->titel, $namen, $m->zeit ?? '–',
            $m->nachricht ? "\n\n> " . str_replace("\n", "\n> ", $m->nachricht) : '', $ich->displayName, $team->url('ansicht', [], true));
        foreach (self::leitung($team) as $l) {
            if ((int)$l->id !== (int)$ich->id) {
                self::senden($l, $team, $text);
            }
        }
        return $m;
    }

    public static function entscheiden(Team $team, TeamMitglied $m, bool $aufnehmen, User $ich): void
    {
        $m->status = $aufnehmen ? 'dabei' : 'abgesagt';
        $m->entschieden_von = $ich->id;
        $m->entschieden_am = date('Y-m-d H:i:s');
        $m->save(false);
        $mensch = User::findOne($m->user_id);
        if ($aufnehmen) {
            // Gewuenschte, noch freie Aufgaben gleich zuordnen -- spart einen Schritt
            foreach (TeamAufgabe::find()->where(['team_id' => $team->id, 'id' => $m->aufgabenIds(), 'zustaendig_id' => null])->all() as $a) {
                $a->updateAttributes(['zustaendig_id' => $m->user_id]);
            }
            $projekt = $team->projekt_id ? Projekt::findOne($team->projekt_id) : null;
            $projekt?->setzeMitmacher((int)$m->user_id, true);
            $text = sprintf("%s Willkommen im Team **%s**! 🎉\n\nAuf der Teamseite siehst du eure Aufgaben, wer wen vertritt und eure Arbeitsweise. Dort ist auch euer gemeinsames Postfach:\n%s",
                self::SYMBOL, $team->titel, $team->url('ansicht', [], true));
        } else {
            $text = sprintf("%s Danke, dass du im Team **%s** mithelfen wolltest. Im Moment passt es leider nicht – %s meldet sich bei dir, wenn sich etwas ändert.",
                self::SYMBOL, $team->titel, $ich->displayName);
        }
        if ($mensch !== null) {
            self::senden($mensch, $team, $text);
        }
    }

    // ── Im Team ─────────────────────────────────────────────────────────────

    public static function aufgabeEintragen(TeamAufgabe $a, string $rolle, ?int $userId): void
    {
        $spalte = $rolle === 'vertretung' ? 'vertretung_id' : 'zustaendig_id';
        $a->updateAttributes([$spalte => $userId]);
    }

    /** Abwesend bis (einschliesslich) -- die Vertretungen der eigenen Aufgaben bekommen eine Nachricht */
    public static function weg(Team $team, TeamMitglied $m, ?string $bis): void
    {
        $m->updateAttributes(['weg_bis' => $bis]);
        if ($bis === null) {
            return;
        }
        $wer = User::findOne($m->user_id);
        foreach (TeamAufgabe::find()->where(['team_id' => $team->id, 'zustaendig_id' => $m->user_id])->all() as $a) {
            $vertretung = $a->vertretung_id ? User::findOne($a->vertretung_id) : null;
            if ($vertretung !== null) {
                self::senden($vertretung, $team, sprintf("%s %s ist bis %s nicht da. Du vertrittst bei: **%s**\n%s",
                    self::SYMBOL, $wer?->displayName ?? '?', Texte::datum($bis), $a->titel, $team->url('ansicht', [], true)));
            }
        }
    }

    /** Aufhoeren: Aufgaben werden wieder frei, die Leitung erfaehrt es */
    public static function aufhoeren(Team $team, TeamMitglied $m): void
    {
        $m->updateAttributes(['status' => 'beendet', 'weg_bis' => null]);
        TeamAufgabe::updateAll(['zustaendig_id' => null], ['team_id' => $team->id, 'zustaendig_id' => $m->user_id]);
        TeamAufgabe::updateAll(['vertretung_id' => null], ['team_id' => $team->id, 'vertretung_id' => $m->user_id]);
        if ((int)$team->koordination_id === (int)$m->user_id) {
            $team->updateAttributes(['koordination_id' => null]);
        }
        $wer = User::findOne($m->user_id);
        foreach (self::leitung($team) as $l) {
            if ((int)$l->id !== (int)$m->user_id) {
                self::senden($l, $team, sprintf("%s %s hört im Team **%s** auf. Die Aufgaben sind wieder frei:\n%s",
                    self::SYMBOL, $wer?->displayName ?? '?', $team->titel, $team->url('ansicht', [], true)));
            }
        }
    }

    /** Wer ausgeschrieben hat und die Koordination -- sie bekommen Anfragen und Rueckfragen */
    public static function leitung(Team $team): array
    {
        $ids = array_unique(array_filter([(int)$team->created_by, (int)$team->koordination_id]));
        return $ids === [] ? [] : User::find()->where(['id' => $ids, 'status' => User::STATUS_ENABLED])->all();
    }

    // ── Team-Postfach ───────────────────────────────────────────────────────

    /** "Nachricht an das Team": neue Unterhaltung mit allen Teammitgliedern */
    public static function anTeamSchreiben(Team $team, User $ich, string $betreff, string $text): TeamPost
    {
        $nachrichtKlasse = self::MAIL_NACHRICHT;
        $eintragKlasse = self::MAIL_EINTRAG;
        $betreff = mb_substr(trim($betreff), 0, 200);
        $vorher = Yii::$app->user->getIdentity(false);
        Yii::$app->user->setIdentity($ich);
        try {
            $m = new $nachrichtKlasse(['title' => self::SYMBOL . ' ' . $team->titel . ': ' . $betreff]);
            if (!$m->save()) {
                throw new RuntimeException(json_encode($m->errors));
            }
            $m->addRecepient($ich, true, false);
            foreach (self::empfaenger($team) as $u) {
                if ((int)$u->id !== (int)$ich->id) {
                    $m->addRecepient($u, false, false);
                }
            }
            $eintrag = $eintragKlasse::createForMessage($m, $ich, trim($text));
            if (!$eintrag->save()) {
                throw new RuntimeException(json_encode($eintrag->errors));
            }
            $eintrag->notify(true);
        } finally {
            Yii::$app->user->setIdentity($vorher);
        }
        return self::eintragen($team, (int)$m->id, $betreff, 'team', $ich);
    }

    /**
     * Eine eigene Unterhaltung an das Team uebergeben: die Teammitglieder kommen mit dem ganzen Verlauf dazu
     * (das Nachrichten-Modul zeigt allen "... ist der Unterhaltung beigetreten" -- offen, nicht heimlich).
     */
    public static function uebergeben(Team $team, int $messageId, User $ich, string $notiz, bool $verlassen): TeamPost
    {
        $nachrichtKlasse = self::MAIL_NACHRICHT;
        $eintragKlasse = self::MAIL_EINTRAG;
        $m = $nachrichtKlasse::findOne($messageId);
        if ($m === null || !$m->isParticipant($ich)) {
            throw new RuntimeException('Diese Unterhaltung gehört dir nicht.');
        }
        $vorher = Yii::$app->user->getIdentity(false);
        Yii::$app->user->setIdentity($ich);
        try {
            foreach (self::empfaenger($team) as $u) {
                if (!$m->isParticipant($u)) {
                    $m->addRecepient($u, false, true);
                }
            }
            if (trim($notiz) !== '') {
                $eintrag = $eintragKlasse::createForMessage($m, $ich, trim($notiz));
                if ($eintrag->save()) {
                    $eintrag->notify(true);
                }
            }
        } finally {
            Yii::$app->user->setIdentity($vorher);
        }
        $post = self::eintragen($team, (int)$m->id, (string)$m->title, 'uebergabe', $ich);
        if ($verlassen && count($m->users) > 1) {
            $m->leave($ich->id);
        }
        return $post;
    }

    /** "Ich uebernehme": nur wenn noch niemand dran ist -- verhindert doppelte Antworten */
    public static function uebernehmen(TeamPost $p, User $ich): bool
    {
        $geaendert = TeamPost::updateAll(['status' => 'uebernommen', 'uebernommen_von' => $ich->id, 'updated_at' => date('Y-m-d H:i:s')],
            ['id' => $p->id, 'status' => ['offen', 'kreisleitung']]);
        if ($geaendert === 0) {
            return false;
        }
        self::teilnehmen((int)$p->message_id, $ich);
        return true;
    }

    public static function setzeStatus(TeamPost $p, string $status, User $ich): void
    {
        $p->status = $status;
        if ($status === 'offen') {
            $p->uebernommen_von = null;
        }
        $p->updated_at = date('Y-m-d H:i:s');
        $p->save(false);
        if ($status === 'kreisleitung') {
            $team = Team::findOne($p->team_id);
            foreach (self::leitung($team) as $l) {
                self::teilnehmen((int)$p->message_id, $l);
                if ((int)$l->id !== (int)$ich->id) {
                    self::senden($l, $team, sprintf("%s Das Team **%s** braucht dich bei einer Nachricht: „%s“ (von %s)\n%s",
                        self::SYMBOL, $team->titel, $p->betreff, $ich->displayName, Projekt::voll($p->adresse())));
                }
            }
        }
    }

    /** Die letzten Nachrichten einer Unterhaltung als Text (fuer "Antwort mit Nova vorbereiten") */
    public static function verlaufText(TeamPost $p, int $anzahl = 6): string
    {
        $eintragKlasse = self::MAIL_EINTRAG;
        $teile = [];
        foreach (array_reverse($eintragKlasse::find()->where(['message_id' => $p->message_id, 'type' => 0])->orderBy(['id' => SORT_DESC])->limit($anzahl)->all()) as $e) {
            $wer = User::findOne($e->created_by);
            $text = class_exists('humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter')
                ? \humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter::process((string)$e->content)
                : strip_tags((string)$e->content);
            $teile[] = ($wer?->displayName ?? '?') . ":\n" . trim($text);
        }
        return implode("\n\n", $teile);
    }

    /** Teammitglieder, die gerade da sind -- wer "weg bis" eingetragen hat, bekommt keine neuen Unterhaltungen */
    private static function empfaenger(Team $team): array
    {
        $liste = [];
        foreach ($team->mitglieder() as $m) {
            if (!$m->istWeg() && $m->user !== null && $m->user->status == User::STATUS_ENABLED) {
                $liste[] = $m->user;
            }
        }
        if ($liste === []) {
            // Niemand da (oder noch niemand im Team): dann die Leitung, damit nichts ins Leere geht
            $liste = self::leitung($team);
        }
        return $liste;
    }

    private static function teilnehmen(int $messageId, User $u): void
    {
        $nachrichtKlasse = self::MAIL_NACHRICHT;
        $m = $nachrichtKlasse::findOne($messageId);
        if ($m !== null && !$m->isParticipant($u)) {
            $m->addRecepient($u, false, true);
        }
    }

    private static function eintragen(Team $team, int $messageId, string $betreff, string $quelle, User $ich): TeamPost
    {
        $p = TeamPost::findOne(['team_id' => $team->id, 'message_id' => $messageId]) ?? new TeamPost(['team_id' => $team->id, 'message_id' => $messageId]);
        $jetzt = date('Y-m-d H:i:s');
        $p->betreff = mb_substr($betreff, 0, 255) ?: '–';
        $p->quelle = $quelle;
        $p->status = 'offen';
        $p->uebernommen_von = null;
        $p->created_by = $p->created_by ?? $ich->id;
        $p->created_at = $p->created_at ?? $jetzt;
        $p->updated_at = $jetzt;
        $p->save(false);
        return $p;
    }

    private static function senden(User $an, Team $team, string $text): void
    {
        if (!class_exists(self::VERSAND) || $an->status != User::STATUS_ENABLED) {
            return;
        }
        try {
            $versand = self::VERSAND;
            $versand::senden((int)$an->id, self::SYMBOL . ' Team: ' . $team->titel, $text);
        } catch (Throwable $e) {
            Yii::error('nexus-projekte: Team-Nachricht nicht gesendet: ' . $e->getMessage(), 'nexus-projekte');
        }
    }
}
