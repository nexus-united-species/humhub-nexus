<?php

namespace nexus\modules\supporterBridge\services;

use humhub\modules\user\models\User;
use Yii;

/**
 * Schickt eine kurze E-Mail an alle Systemadministratoren, wenn im
 * Unterstuetzer-Bereich eine manuelle Entscheidung noetig wird -- damit
 * niemand die Admin-Oberflaeche von Hand durchsuchen muss (Josh' Wunsch
 * vom 09.09.2026). Bewusst per E-Mail statt HumHub-Benachrichtigung oder
 * Telegram: nutzt HumHubs bereits eingerichteten Mailversand, braucht
 * keine neuen Zugangsdaten und erreicht auch ausserhalb von HumHub.
 *
 * Empfaenger sind ALLE aktiven Systemadministratoren, nicht eine fest
 * hinterlegte Adresse -- kommt so automatisch mit, falls je ein zweiter
 * Admin dazukommt, und steht nirgends als Geheimnis im Code.
 */
class NotificationService
{
    public function entscheidungNoetig(string $anlass, string $kofiEmail, ?float $betrag, ?string $waehrung): void
    {
        $admins = $this->systemAdmins();
        if (empty($admins)) {
            return;
        }

        $betragText = $betrag !== null ? number_format($betrag, 2, ',', '.') . ' ' . ($waehrung ?? '') : 'unbekannt';
        $text = "Bei der N.E.X.U.S. Unterstuetzer-Bibliothek wartet ein Vorgang auf eine Entscheidung.\n\n"
            . "Grund: $anlass\n"
            . "Ko-fi-E-Mail: $kofiEmail\n"
            . "Betrag: $betragText\n\n"
            . 'Admin-Bereich: ' . rtrim((string)Yii::$app->settings->get('baseUrl'), '/')
            . '/nexus-supporter-bridge/admin/index' . "\n";

        foreach ($admins as $admin) {
            if (empty($admin->email)) {
                continue;
            }

            try {
                Yii::$app->mailer->compose()
                    ->setTo($admin->email)
                    ->setSubject('N.E.X.U.S. Unterstuetzer-Bibliothek: Entscheidung noetig')
                    ->setTextBody($text)
                    ->send();
            } catch (\Throwable $e) {
                // Ein fehlgeschlagener Mailversand darf den eigentlichen
                // Vorgang (Zahlung verarbeiten / Ablauf pruefen) nicht
                // abbrechen -- nur protokollieren.
                Yii::error('nexus-supporter-bridge: Benachrichtigung fehlgeschlagen: ' . $e->getMessage());
            }
        }
    }

    /**
     * @return User[]
     */
    private function systemAdmins(): array
    {
        return User::find()
            ->innerJoin('group_user', 'group_user.user_id = user.id')
            ->innerJoin('group', 'group.id = group_user.group_id')
            ->andWhere(['user.status' => User::STATUS_ENABLED, 'group.is_admin_group' => true])
            ->all();
    }
}
