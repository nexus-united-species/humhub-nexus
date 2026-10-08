<?php

namespace nexus\modules\supporterBridge\services;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\supporterBridge\models\Supporter;
use nexus\modules\supporterBridge\models\SupporterEvent;
use Yii;

/**
 * Kernlogik: Ko-fi-Zahlungsereignis -> Unterstuetzer-Datensatz -> ggf.
 * Space-Zugang. Wird sowohl vom WebhookController (echte Ko-fi-Anfragen)
 * als auch von Testskripten aufgerufen -- ein einziger Codepfad, keine
 * doppelte Logik.
 */
class KofiWebhookService
{
    /**
     * Mindestbetrag fuer die AUTOMATISCHE Freischaltung. Seit 28.09.2026 (Josh, Variante a):
     * JEDE monatliche Unterstuetzung oeffnet die Bibliothek, egal wie hoch -- vorher 20 EUR,
     * wodurch vier zahlende Menschen (10-15 EUR/Monat) draussen blieben. Einmalige Spenden
     * oeffnen sie weiterhin NICHT (siehe unten). 1 EUR statt 0 nur als Schutz gegen leere
     * oder Null-Betraege. Die fruehere Server-Einstellung MIN_MONTHLY_SUPPORT_EUR (=20 in
     * docker-compose.yml) wird bewusst nicht mehr gelesen.
     */
    public const MINDESTBETRAG = 1.0;

    public function __construct(
        private readonly int $supporterSpaceId,
        private readonly float $mindestMonatsbetrag = self::MINDESTBETRAG,
    ) {
    }

    /**
     * @param array $payload bereits dekodiertes Ko-fi-JSON (das "data"-Feld)
     * @return array{status:int, message:string}
     */
    public function verarbeiten(array $payload): array
    {
        $messageId = (string)($payload['message_id'] ?? '');
        if ($messageId === '') {
            return ['status' => 400, 'message' => 'message_id fehlt'];
        }

        // Dopplungssperre ueber einen DB-Unique-Constraint, nicht ueber ein
        // vorheriges SELECT: ein reines "erst nachsehen, dann entscheiden"
        // hat ein Zeitfenster, in dem zwei fast gleichzeitige Zustellungen
        // (Ko-fi liefert bei Netzwerkproblemen durchaus doppelt zu) beide
        // die Pruefung passieren koennten, bevor eine von beiden etwas
        // geschrieben hat. Der INSERT selbst ist die Sperre: schlaegt er
        // wegen des PRIMARY KEY fehl, war die message_id schon da --
        // atomar, unabhaengig von der Reihenfolge zweier Anfragen.
        if (!$this->alsErstmaligMarkieren($messageId)) {
            SupporterEvent::protokollieren(SupporterEvent::DUPLICATE_IGNORED, null, $messageId);
            return ['status' => 200, 'message' => 'Bereits verarbeitet (Duplikat)'];
        }

        $email = strtolower(trim((string)($payload['email'] ?? '')));
        $isSubscription = (bool)($payload['is_subscription_payment'] ?? false);
        $supportType = $isSubscription ? Supporter::SUPPORT_TYPE_SUBSCRIPTION : Supporter::SUPPORT_TYPE_ONE_TIME;
        $amount = isset($payload['amount']) ? (float)$payload['amount'] : null;
        $currency = $payload['currency'] ?? null;
        $tierName = $payload['tier_name'] ?? null;

        SupporterEvent::protokollieren(SupporterEvent::PAYMENT_RECEIVED, null, $messageId);

        if ($email === '') {
            return ['status' => 200, 'message' => 'Keine E-Mail im Ereignis -- ignoriert'];
        }

        $supporter = Supporter::findOne(['ko_fi_email' => $email]) ?? new Supporter([
            'ko_fi_email' => $email,
            'status' => Supporter::STATUS_UNMATCHED,
            'support_type' => $supportType,
        ]);

        $supporter->ko_fi_message_id = $messageId;
        $supporter->support_type = $supportType;
        $supporter->amount = $amount;
        $supporter->currency = $currency;
        if ($tierName) {
            $supporter->membership_tier = $tierName;
        }
        $supporter->last_payment_at = date('Y-m-d H:i:s');

        $user = $this->passendenNutzerFinden($email);
        // Von Hand verknuepft (Ko-fi-Adresse weicht von der Portal-Adresse ab, z. B. Ilse Pforr,
        // eine Unterstuetzerin am 28.09.2026): die bestehende Zuordnung behalten. Ohne das loeste
        // die naechste Monatszahlung die Verknuepfung wieder, das Ablaufdatum wuerde nicht
        // verlaengert und der Zugang nach einem Monat automatisch entzogen.
        if ($user === null && $supporter->humhub_user_id) {
            $user = User::findOne(['id' => $supporter->humhub_user_id, 'status' => User::STATUS_ENABLED]);
        }

        if ($user === null) {
            $supporter->humhub_user_id = null;
            if ($supporter->status === Supporter::STATUS_UNMATCHED || $supporter->isNewRecord) {
                $supporter->status = Supporter::STATUS_UNMATCHED;
            }
            // sonst: Status (z.B. MANUAL/LIFETIME von vorher) unangetastet lassen
            $supporter->save(false);
            SupporterEvent::protokollieren(SupporterEvent::UNMATCHED_PAYMENT, null, $messageId);
            return ['status' => 200, 'message' => 'Kein eindeutiges HumHub-Konto gefunden -- als UNMATCHED gespeichert'];
        }

        $warNeuZugeordnet = $supporter->humhub_user_id === null;
        $supporter->humhub_user_id = $user->id;
        if ($supporter->started_at === null) {
            $supporter->started_at = date('Y-m-d H:i:s');
        }

        if ($warNeuZugeordnet) {
            SupporterEvent::protokollieren(SupporterEvent::USER_MATCHED, $user->id, $messageId);
        }

        // MANUAL/LIFETIME sind bewusste Admin-Entscheidungen, unabhaengig
        // von Ko-fi -- ein Webhook darf diesen Status nicht ueberschreiben.
        $statusIstAdminGesetzt = in_array($supporter->status, [Supporter::STATUS_MANUAL, Supporter::STATUS_LIFETIME], true);

        if ($isSubscription) {
            $supporter->expires_at = $this->naechsteAbrechnung(new \DateTimeImmutable())->format('Y-m-d H:i:s');
            if (!$statusIstAdminGesetzt) {
                // Nur ab dem Mindestbetrag automatisch freischalten (Vorgabe
                // Josh 09.09.2026). Darunter genau wie eine einmalige
                // Unterstuetzung behandeln: speichern, aber Admin entscheidet.
                $supporter->status = $this->erreichtMindestbetrag($amount, $currency)
                    ? Supporter::STATUS_ACTIVE
                    : Supporter::STATUS_PENDING_REVIEW;
            }
        } else {
            // Einmalige Unterstuetzung: bewusst KEINE automatische
            // Freischaltung (Vorgabe Abschnitt 5) -- nur speichern und
            // fuer eine Admin-Entscheidung markieren, sofern nicht schon
            // ein hoeherwertiger Status besteht.
            if (!$statusIstAdminGesetzt
                && !in_array($supporter->status, [Supporter::STATUS_ACTIVE, Supporter::STATUS_GRACE_PERIOD], true)) {
                $supporter->status = Supporter::STATUS_PENDING_REVIEW;
            }
        }

        $supporter->save(false);

        if ($supporter->hatAktivenAnspruch()) {
            $this->zugangGewaehren($user->id);
        } elseif ($supporter->status === Supporter::STATUS_PENDING_REVIEW) {
            $anlass = $isSubscription
                ? 'Wiederkehrende Zahlung unter Mindestbetrag oder in Fremdwaehrung'
                : 'Einmalige Unterstuetzung';
            (new NotificationService())->entscheidungNoetig($anlass, $email, $amount, $currency);
        }

        return ['status' => 200, 'message' => 'Verarbeitet: ' . $supporter->status];
    }

    /**
     * Fuegt den Nutzer dem Unterstuetzer-Space hinzu -- Space::addMember()
     * ist von Haus aus idempotent (siehe SpaceModelMembership::addMember):
     * ein bereits aktives Mitglied wird unveraendert gelassen, keine Rolle
     * wird ueberschrieben. Wir protokollieren SPACE_ACCESS_GRANTED nur,
     * wenn vorher wirklich noch keine Mitgliedschaft bestand.
     */
    public function zugangGewaehren(int $userId): bool
    {
        $space = Space::findOne(['id' => $this->supporterSpaceId]);
        if ($space === null) {
            Yii::error('nexus-supporter-bridge: Unterstuetzer-Space id=' . $this->supporterSpaceId . ' nicht gefunden');
            return false;
        }

        $warSchonMitglied = $space->isMember($userId);
        $erfolg = $space->addMember($userId);

        if ($erfolg && !$warSchonMitglied) {
            SupporterEvent::protokollieren(SupporterEvent::SPACE_ACCESS_GRANTED, $userId, null);
        }

        return $erfolg;
    }

    public function zugangEntziehen(int $userId, ?string $grund = null): bool
    {
        $space = Space::findOne(['id' => $this->supporterSpaceId]);
        if ($space === null) {
            return false;
        }

        $erfolg = $space->removeMember($userId);
        if ($erfolg) {
            SupporterEvent::protokollieren(SupporterEvent::ACCESS_REVOKED, $userId, $grund);
        }

        return $erfolg;
    }

    /**
     * Sucht per Groß-/Kleinschreibungs-unabhaengigem E-Mail-Abgleich nach
     * GENAU EINEM aktiven HumHub-Konto. Bei keinem oder mehreren Treffern
     * wird bewusst NICHT geraten (Vorgabe Abschnitt 3).
     */
    private function passendenNutzerFinden(string $email): ?User
    {
        $treffer = User::find()
            ->andWhere(['status' => User::STATUS_ENABLED])
            ->andWhere(new \yii\db\Expression('LOWER(email) = :email', [':email' => $email]))
            ->all();

        return count($treffer) === 1 ? $treffer[0] : null;
    }

    /**
     * Atomarer, DB-erzwungener Dopplungsschutz. Gibt true zurueck, wenn
     * diese message_id hiermit zum ERSTEN Mal als verarbeitet markiert
     * wurde (also: bitte weiterverarbeiten). Gibt false zurueck, wenn sie
     * bereits vorhanden war (egal ob durch einen frueheren Aufruf oder
     * durch eine parallel laufende Anfrage, die minimal frueher dran war).
     */
    private function alsErstmaligMarkieren(string $messageId): bool
    {
        try {
            Yii::$app->db->createCommand()->insert('nexus_supporter_processed_message', [
                'message_id' => $messageId,
                'created_at' => date('Y-m-d H:i:s'),
            ])->execute();
            return true;
        } catch (\yii\db\IntegrityException) {
            return false;
        }
    }

    /**
     * Prueft, ob eine wiederkehrende Zahlung den konfigurierten
     * Mindestbetrag erreicht. Bewusst nur bei Euro-Zahlungen automatisch
     * vergleichbar -- bei einer anderen Waehrung wuerde ein Vergleich ohne
     * Umrechnung raten statt pruefen; solche Faelle landen deshalb genau
     * wie ein zu niedriger Betrag zur Entscheidung beim Admin, statt
     * automatisch freigeschaltet zu werden.
     */
    private function erreichtMindestbetrag(?float $amount, ?string $currency): bool
    {
        return $amount !== null
            && strtoupper((string)$currency) === 'EUR'
            && $amount >= $this->mindestMonatsbetrag;
    }

    /**
     * Naechster erwarteter Abrechnungszeitpunkt auf echter
     * Kalendermonatsbasis, nicht pauschal "+30 Tage" (das wuerde bei einer
     * Zahlung am 31. eines Monats jeden Monat frueher "faellig" werden).
     * PHPs eingebautes "+1 month" hat dabei eine bekannte Falle: der 31.
     * Januar + 1 Monat ergibt den 3. Maerz (Ueberlauf), nicht den 28./29.
     * Februar. Deshalb wird hier auf den letzten Tag des Zielmonats
     * begrenzt -- genau wie es echte Abrechnungssysteme handhaben.
     */
    private function naechsteAbrechnung(\DateTimeImmutable $ausgangsdatum): \DateTimeImmutable
    {
        $jahr = (int)$ausgangsdatum->format('Y');
        $monat = (int)$ausgangsdatum->format('n') + 1;
        $tag = (int)$ausgangsdatum->format('j');

        if ($monat > 12) {
            $monat -= 12;
            $jahr++;
        }

        $letzterTagZielmonat = (int)(new \DateTimeImmutable(sprintf('%04d-%02d-01', $jahr, $monat)))->format('t');
        $tag = min($tag, $letzterTagZielmonat);

        return $ausgangsdatum->setDate($jahr, $monat, $tag);
    }
}
