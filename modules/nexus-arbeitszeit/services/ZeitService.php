<?php

namespace nexus\modules\arbeitszeit\services;

use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\arbeitszeit\models\Eintrag;

/**
 * Eintragen, Freigeben, Ruecksprache, Summen -- eine Stelle fuer Formular UND Assistent.
 */
class ZeitService
{
    /** Kreise, in denen der Mensch Mitglied ist (nur die darf er waehlen), in Uebersichts-Reihenfolge. */
    public static function kreiseFuer(int $userId): array
    {
        $ids = Membership::find()->where(['user_id' => $userId, 'status' => Membership::STATUS_MEMBER])->select('space_id')->column();
        $kreise = [];
        foreach (Space::find()->where(['id' => $ids, 'status' => Space::STATUS_ENABLED])->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all() as $space) {
            $kreise[(int)$space->id] = $space->name;
        }
        return $kreise;
    }

    /**
     * Legt einen Eintrag an. Mit $spaceId = null entsteht ein ENTWURF (nur ueber den Assistenten,
     * der dann nach dem Kreis fragt). Gibt [Eintrag|null, Fehlerschluessel[]] zurueck.
     */
    public static function anlegen(User $user, $stunden, string $datum, ?int $spaceId, string $beschreibung, string $quelle): array
    {
        $eintrag = new Eintrag([
            'user_id' => $user->id,
            'stunden' => is_string($stunden) ? str_replace(',', '.', trim($stunden)) : $stunden,
            'datum' => $datum,
            'space_id' => $spaceId,
            'beschreibung' => $beschreibung,
            'quelle' => $quelle,
            'status' => $spaceId === null ? Eintrag::STATUS_ENTWURF : Eintrag::STATUS_OFFEN,
        ]);
        $fehler = [];
        if (!$eintrag->validate()) {
            foreach (array_keys($eintrag->errors) as $feld) {
                $fehler[] = ['stunden' => 'fehler_stunden', 'datum' => 'fehler_datum', 'beschreibung' => 'fehler_text'][$feld] ?? 'fehler_text';
            }
        }
        if ($spaceId !== null && !array_key_exists($spaceId, self::kreiseFuer($user->id))) {
            $fehler[] = 'fehler_kreis';
        }
        if ($fehler !== []) {
            return [null, array_values(array_unique($fehler))];
        }
        $eintrag->save(false);
        NextcloudBericht::vormerken();
        if ($eintrag->status === Eintrag::STATUS_OFFEN) {
            self::adminsBenachrichtigen($eintrag);
        }
        return [$eintrag, []];
    }

    /** Entwurf des Assistenten bekommt seinen Kreis und geht zur Freigabe. */
    public static function kreisSetzen(Eintrag $eintrag, int $spaceId): bool
    {
        if (!array_key_exists($spaceId, self::kreiseFuer($eintrag->user_id))) {
            return false;
        }
        $eintrag->space_id = $spaceId;
        $eintrag->status = Eintrag::STATUS_OFFEN;
        $eintrag->save(false);
        NextcloudBericht::vormerken();
        self::adminsBenachrichtigen($eintrag);
        return true;
    }

    public static function freigeben(Eintrag $eintrag, User $admin): void
    {
        self::pruefen($eintrag, $admin, Eintrag::STATUS_FREIGEGEBEN, null);
        $sprache = self::spracheVon($eintrag->user);
        Benachrichtigung::senden($eintrag->user_id, Benachrichtigung::TITEL_MITGLIED, sprintf(
            ['de' => "✅ Deine Arbeitsstunden wurden freigegeben: **%s Std.** am %s – %s.\n\nDanke für deinen Einsatz für N.E.X.U.S.! 💛",
             'en' => "✅ Your working hours were approved: **%s h** on %s – %s.\n\nThank you for your work for N.E.X.U.S.! 💛",
             'es' => "✅ Tus horas de trabajo fueron aprobadas: **%s h** el %s – %s.\n\n¡Gracias por tu trabajo para N.E.X.U.S.! 💛"][$sprache],
            $eintrag->stundenText(), self::datumText($eintrag->datum), self::kurz($eintrag->beschreibung)
        ));
    }

    public static function ruecksprache(Eintrag $eintrag, User $admin, string $grund): void
    {
        $grund = mb_substr(trim($grund), 0, 500);
        self::pruefen($eintrag, $admin, Eintrag::STATUS_RUECKSPRACHE, $grund !== '' ? $grund : null);
        $sprache = self::spracheVon($eintrag->user);
        $text = sprintf(
            ['de' => "💬 Zu deinem Eintrag **%s Std.** am %s (%s) hat %s eine Rückfrage",
             'en' => "💬 %4\$s has a question about your entry **%1\$s h** on %2\$s (%3\$s)",
             'es' => "💬 %4\$s tiene una consulta sobre tu entrada **%1\$s h** del %2\$s (%3\$s)"][$sprache],
            $eintrag->stundenText(), self::datumText($eintrag->datum), self::kurz($eintrag->beschreibung), $admin->displayName
        );
        $text .= $grund !== '' ? ":\n\n> " . $grund : '.';
        $text .= ['de' => "\n\nAm besten schreibst du " . $admin->displayName . ' kurz eine Nachricht.',
                  'en' => "\n\nPlease send " . $admin->displayName . ' a short message.',
                  'es' => "\n\nLo mejor es que escribas un mensaje breve a " . $admin->displayName . '.'][$sprache];
        Benachrichtigung::senden($eintrag->user_id, Benachrichtigung::TITEL_MITGLIED, $text);
    }

    private static function pruefen(Eintrag $eintrag, User $admin, string $status, ?string $grund): void
    {
        $eintrag->status = $status;
        $eintrag->geprueft_von = $admin->id;
        $eintrag->geprueft_am = date('Y-m-d H:i:s');
        $eintrag->rueckfrage = $grund;
        $eintrag->protokolliere($admin->displayName, $status === Eintrag::STATUS_FREIGEGEBEN
            ? 'freigegeben'
            : 'Rücksprache' . ($grund ? ' („' . $grund . '“)' : ''));
        $eintrag->save(false);
        NextcloudBericht::vormerken();
    }

    /** @return array{gesamt: float, monat: float, offen: float} */
    public static function summen(int $userId): array
    {
        $basis = Eintrag::find()->where(['user_id' => $userId]);
        return [
            'gesamt' => (float)(clone $basis)->andWhere(['status' => Eintrag::STATUS_FREIGEGEBEN])->sum('stunden'),
            'monat' => (float)(clone $basis)->andWhere(['status' => Eintrag::STATUS_FREIGEGEBEN])->andWhere(['>=', 'datum', date('Y-m-01')])->sum('stunden'),
            'offen' => (float)(clone $basis)->andWhere(['status' => Eintrag::STATUS_OFFEN])->sum('stunden'),
        ];
    }

    /** "Jeder mit Admin-Rolle" (Josh, 28.09.2026) = Mitglieder der HumHub-Administratorgruppe. */
    public static function admins(): array
    {
        return User::find()
            ->innerJoin('group_user', 'group_user.user_id = user.id')
            ->innerJoin('group', 'group.id = group_user.group_id')
            ->andWhere(['user.status' => User::STATUS_ENABLED, 'group.is_admin_group' => 1])
            ->all();
    }

    private static function adminsBenachrichtigen(Eintrag $eintrag): void
    {
        $name = $eintrag->name();
        $kreis = $eintrag->space ? $eintrag->space->name : '–';
        $link = Benachrichtigung::link('/nexus-arbeitszeit/freigabe/index');
        foreach (self::admins() as $admin) {
            Benachrichtigung::senden($admin->id, Benachrichtigung::TITEL_ADMIN, sprintf(
                "⏱ **%s** hat %s Std. eingetragen (%s, %s):\n\n> %s\n\nFreigeben: %s",
                $name, $eintrag->stundenText(), self::datumText($eintrag->datum), $kreis, $eintrag->beschreibung, $link
            ));
        }
    }

    public static function spracheVon(?User $user): string
    {
        $code = strtolower(explode('-', (string)($user->language ?? 'de'))[0]);
        return in_array($code, ['de', 'en', 'es'], true) ? $code : 'de';
    }

    public static function datumText(string $datum): string
    {
        return date('d.m.Y', strtotime($datum));
    }

    private static function kurz(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        return mb_strlen($text) > 80 ? mb_substr($text, 0, 77) . '…' : $text;
    }
}
