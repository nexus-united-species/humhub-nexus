<?php

namespace nexus\modules\mitgliedsanfrage;

use humhub\modules\space\models\Membership;
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
    /** So frisch muss eine Kreis-Mitgliedschaft sein, um als "gerade aus der Einladung" zu gelten */
    private const NEU_SEKUNDEN = 900;

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
                self::zumErstenKreis($mensch);
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
     * Beim allerersten Betreten (= gerade gemeldet): Wer ueber einen Einladungslink kam, landet in
     * dem Kreis, in den er eingeladen wurde -- sonst im Willkommen-Kreis (Einstellung
     * "willkommenKreis"), wo die Anleitungen fuer den Anfang stehen. Die Anmeldung hat ihre
     * Weiterleitung schon gesetzt (AuthController::login), hier wird nur das Ziel ersetzt.
     * Das Ziel wird auch als "Ruecksprung" gemerkt: Die Seite mit den Nutzungsbedingungen leitet
     * danach dorthin -- sonst landete man auf einer Seite, die sich der Browser zufaellig noch aus
     * einer frueheren Sitzung gemerkt hatte (Test 14.10.2026: fremde Unterhaltung, Fehlermeldung).
     */
    private static function zumErstenKreis(User $mensch): void
    {
        $kreis = self::eingeladenerKreis($mensch);
        if ($kreis === null) {
            $id = (int)Yii::$app->getModule('nexus-mitgliedsanfrage')->willkommenKreis;
            $kreis = $id > 0 ? Space::findOne(['id' => $id]) : null;
        }
        if ($kreis === null || Yii::$app->request->getIsAjax()) {
            return;
        }
        Yii::$app->user->setReturnUrl($kreis->getUrl());
        Yii::$app->response->redirect($kreis->getUrl());
    }

    /**
     * Kreis, in den HumHub den Menschen beim Anlegen des Kontos aus einer Einladung aufgenommen hat
     * (User::setUpApproved) -- erkennbar daran, dass es kein Standard-Kreis fuer alle ist und die
     * Mitgliedschaft gerade erst entstanden ist.
     */
    private static function eingeladenerKreis(User $mensch): ?Space
    {
        $id = Yii::$app->db->createCommand(
            'SELECT m.space_id FROM space_membership m JOIN space s ON s.id = m.space_id
             WHERE m.user_id = :u AND m.status = :mitglied AND COALESCE(s.auto_add_new_members, 0) = 0
               AND m.created_at >= :seit ORDER BY m.created_at DESC LIMIT 1',
            [':u' => $mensch->id, ':mitglied' => Membership::STATUS_MEMBER, ':seit' => date('Y-m-d H:i:s', time() - self::NEU_SEKUNDEN)]
        )->queryScalar();
        return $id ? Space::findOne(['id' => (int)$id]) : null;
    }

    /**
     * Einladungslink eines Kreises fuer Gaeste auf unsere Einladungsseite umlenken (dort zuerst die
     * E-Mail-Adresse, siehe EinladungController). Angemeldete behandelt HumHub selbst.
     */
    public static function onEinladungslink($event): void
    {
        try {
            if ($event->action->id !== 'by-link' || !Yii::$app->user->isGuest) {
                return;
            }
            $anfrage = Yii::$app->request;
            if (!$anfrage->get('token') || !$anfrage->get('spaceId')) {
                return;
            }
            $event->isValid = false;
            Yii::$app->response->redirect(['/nexus-mitgliedsanfrage/einladung/index',
                'token' => (string)$anfrage->get('token'), 'spaceId' => (int)$anfrage->get('spaceId')]);
        } catch (Throwable $e) {
            Yii::error('nexus-mitgliedsanfrage (Einladungslink): ' . $e->getMessage(), 'nexus-mitgliedsanfrage');
        }
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
