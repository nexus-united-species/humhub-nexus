<?php

namespace nexus\modules\mitgliedsanfrage;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\arbeitszeit\services\Benachrichtigung;
use nexus\modules\arbeitszeit\services\ZeitService;
use Throwable;
use Yii;

class Events
{
    public const TITEL = '👋 Neue Mitglieder';
    public const ANGABE = 'nexus_anfrage';
    private const MAX_ZEICHEN = 1000;

    /** Reihenfolge und Ueberschriften der Antworten in der Nachricht an die Admins. */
    private const FRAGEN = [
        'aufmerksam' => 'Wie auf N.E.X.U.S. aufmerksam geworden?',
        'erwartung' => 'Was erhofft sich dieser Mensch von N.E.X.U.S.?',
        'faehigkeiten' => 'Fähigkeiten oder Beruf',
        'einbringen' => 'Was könnte dieser Mensch in die Gemeinschaft einbringen?',
    ];

    /** Nach dem Login: Hat Authentik Antworten mitgegeben, die wir noch nicht gemeldet haben? */
    public static function onLogin($event): void
    {
        try {
            $mensch = Yii::$app->user->getIdentity();
            $dienst = Yii::$app->user->getCurrentAuthClient();
            if (!($mensch instanceof User) || $dienst === null) {
                return;
            }
            $angaben = $dienst->getUserAttributes();
            if (self::melden($mensch, $angaben[self::ANGABE] ?? null)) {
                self::zumWillkommenKreis();
            }
        } catch (Throwable $e) {
            // Die Meldung ist Beiwerk -- ein Fehler hier darf nie eine Anmeldung verhindern.
            Yii::error('nexus-mitgliedsanfrage: ' . $e->getMessage(), 'nexus-mitgliedsanfrage');
        }
    }

    /**
     * Schreibt den Admins die Antworten -- genau einmal je Mensch.
     *
     * @param mixed $antworten Angabe "nexus_anfrage" von Authentik (fremde Daten, wird geprueft)
     * @return bool true, wenn jetzt gemeldet wurde
     */
    public static function melden(User $mensch, $antworten): bool
    {
        if (!is_array($antworten)) {
            return false;
        }
        $sauber = [];
        foreach (array_keys(self::FRAGEN) as $schluessel) {
            $wert = $antworten[$schluessel] ?? '';
            $sauber[$schluessel] = is_string($wert) ? trim(mb_substr($wert, 0, self::MAX_ZEICHEN)) : '';
        }
        if (implode('', $sauber) === '') {
            return false;
        }

        // Erst merken, dann melden: Die eindeutige Konto-Nummer verhindert, dass zwei gleichzeitige
        // Anmeldungen (zwei Geraete) zwei Nachrichten ausloesen.
        $db = Yii::$app->db;
        $neu = $db->createCommand(
            'INSERT IGNORE INTO nexus_mitgliedsanfrage (user_id, antworten, created_at) VALUES (:u, :a, :t)',
            [':u' => $mensch->id, ':a' => json_encode($sauber, JSON_UNESCAPED_UNICODE), ':t' => date('Y-m-d H:i:s')]
        )->execute();
        if ($neu === 0) {
            return false;
        }

        $text = self::nachricht($mensch, $sauber);
        foreach (ZeitService::admins() as $admin) {
            Benachrichtigung::senden((int)$admin->id, self::TITEL, $text);
        }
        return true;
    }

    /**
     * Beim allerersten Betreten (= gerade gemeldet) in den Willkommen-Kreis (Einstellung
     * "willkommenKreis") statt auf die Uebersicht: Dort stehen die Anleitungen fuer den Anfang. Die
     * Anmeldung hat ihre Weiterleitung schon gesetzt (AuthController::login), hier wird nur das Ziel
     * ersetzt. Ohne Einstellung bleibt es beim normalen Ziel.
     */
    private static function zumWillkommenKreis(): void
    {
        $id = (int)Yii::$app->getModule('nexus-mitgliedsanfrage')->willkommenKreis;
        $kreis = $id > 0 ? Space::findOne(['id' => $id]) : null;
        if ($kreis === null || Yii::$app->request->getIsAjax()) {
            return;
        }
        Yii::$app->response->redirect($kreis->getUrl());
    }

    /** @param array<string, string> $antworten */
    private static function nachricht(User $mensch, array $antworten): string
    {
        $zeilen = ['👋 **Neues Mitglied: ' . $mensch->displayName . '** (@' . $mensch->username . ')', ''];
        foreach (self::FRAGEN as $schluessel => $frage) {
            $antwort = $antworten[$schluessel] !== '' ? $antworten[$schluessel] : '(keine Angabe)';
            $zeilen[] = '**' . $frage . '**';
            $zeilen[] = '> ' . str_replace("\n", "\n> ", str_replace("\r", '', $antwort));
            $zeilen[] = '';
        }
        $zeilen[] = 'Charta bestätigt. Zum Profil: ' . Benachrichtigung::link($mensch->getUrl());
        return implode("\n", $zeilen);
    }
}
