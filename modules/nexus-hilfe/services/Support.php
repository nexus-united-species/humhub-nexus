<?php

namespace nexus\modules\hilfe\services;

use humhub\modules\file\models\FileUpload;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Membership;
use humhub\modules\user\models\User;
use nexus\modules\hilfe\Module;
use RuntimeException;
use Throwable;
use Yii;
use yii\web\UploadedFile;

/**
 * Eine Support-Anfrage anlegen: Zeile in nexus_hilfe_anfrage (Nummer), Beitrag im privaten Kreis
 * "Support-Team" (Absender Nova), Portal-Nachricht an jedes Team-Mitglied (mit E-Mail) und -- wenn
 * gewuenscht -- Eingangsbestaetigung an den Menschen. Auch Nova ruft das auf (Weitergabe).
 */
class Support
{
    private const BENACHRICHTIGUNG = 'nexus\modules\arbeitszeit\services\Benachrichtigung';
    private const TITEL_TEAM = '🆘 Support-Anfragen';
    private const TITEL_MENSCH = '🆘 Deine Support-Anfrage';
    private const MAX_BILD_BYTES = 10 * 1024 * 1024;

    public static function verfuegbar(): bool
    {
        $modul = Yii::$app->getModule('nexus-hilfe');
        return $modul instanceof Module && $modul->supportKreis() !== null && class_exists(self::BENACHRICHTIGUNG);
    }

    /**
     * @param string $quelle "formular" | "gast" | "nova"
     * @return int Anfrage-Nummer
     */
    public static function anlegen(
        ?User $mensch,
        string $thema,
        string $text,
        string $quelle,
        bool $bestaetigen = true,
        string $name = '',
        string $email = '',
        ?UploadedFile $bild = null
    ): int {
        /** @var Module $modul */
        $modul = Yii::$app->getModule('nexus-hilfe');
        $kreis = $modul->supportKreis();
        $nova = User::findOne(['id' => (int)getenv('ASSISTANT_USER_ID')]);
        if ($kreis === null || $nova === null) {
            throw new RuntimeException('Support-Kreis oder Nova-Konto fehlt.');
        }

        $db = Yii::$app->db;
        $db->createCommand()->insert('nexus_hilfe_anfrage', [
            'user_id' => $mensch?->id,
            'name' => $mensch ? null : mb_substr($name, 0, 120),
            'email' => $mensch ? null : mb_substr($email, 0, 190),
            'thema' => in_array($thema, Texte::THEMEN, true) ? $thema : 'sonstiges',
            'text' => $text,
            'quelle' => $quelle,
            'created_at' => date('Y-m-d H:i:s'),
        ])->execute();
        $nummer = (int)$db->getLastInsertID();

        $post = self::beitrag($kreis, $nova, $nummer, $mensch, $thema, $text, $quelle, $name, $email, $bild);
        $db->createCommand()->update('nexus_hilfe_anfrage', ['post_id' => $post->id], ['id' => $nummer])->execute();

        $b = self::BENACHRICHTIGUNG;
        $wer = $mensch ? $mensch->displayName : trim($name) . ' (ohne Anmeldung)';
        $auszug = mb_strlen($text) > 300 ? mb_substr($text, 0, 297) . '…' : $text;
        $team = Membership::find()->where(['space_id' => $kreis->id, 'status' => Membership::STATUS_MEMBER])->select('user_id')->column();
        foreach ($team as $mitgliedId) {
            if ((int)$mitgliedId === (int)$nova->id) {
                continue;
            }
            $b::senden((int)$mitgliedId, self::TITEL_TEAM, "🆘 **Neue Anfrage #{$nummer}** von {$wer} – " . Texte::themaDeutsch($thema)
                . "\n\n> " . str_replace("\n", "\n> ", $auszug)
                . "\n\nZur Anfrage: " . $b::link($post->content->getUrl()));
        }

        if ($bestaetigen && $mensch !== null) {
            $t = Texte::alle($mensch->language);
            $vorname = trim((string)($mensch->profile->firstname ?? '')) ?: $mensch->displayName;
            $b::senden((int)$mensch->id, self::TITEL_MENSCH, sprintf($t['bestaetigung'], $vorname, $nummer));
        }
        return $nummer;
    }

    private static function beitrag(
        $kreis,
        User $nova,
        int $nummer,
        ?User $mensch,
        string $thema,
        string $text,
        string $quelle,
        string $name,
        string $email,
        ?UploadedFile $bild
    ): Post {
        $von = $mensch
            ? '[' . $mensch->displayName . '](mention:' . $mensch->guid . ' "' . $mensch->getUrl() . '")'
            : trim($name) . ' – ' . $email . ' *(ohne Anmeldung – Antwort per E-Mail)*';
        $ueber = ['formular' => 'Hilfe-Formular', 'gast' => 'Hilfe-Formular ohne Anmeldung', 'nova' => 'von Nova weitergegeben'][$quelle] ?? $quelle;
        $nachricht = "🆘 **Anfrage #{$nummer} – " . Texte::themaDeutsch($thema) . "**\n\n"
            . "**Von:** {$von}  \n**Über:** {$ueber}  \n**Eingang:** " . date('d.m.Y H:i') . ' Uhr – Antwort zugesagt innerhalb von '
            . Module::ANTWORT_STUNDEN . " Stunden\n\n"
            . '> ' . str_replace("\n", "\n> ", trim($text)) . "\n\n---\n"
            . ($mensch
                ? 'Bitte direkt per Nachricht antworten (Namen anklicken → „Nachricht senden“) und hier kurz kommentieren, wer sich kümmert.'
                : 'Bitte per E-Mail an die Adresse oben antworten und hier kurz kommentieren, wer sich kümmert.')
            . ' Erledigt? Beitrag über „…“ → „Archivieren“.';

        $vorher = Yii::$app->user->getIdentity(false);
        try {
            Yii::$app->user->setIdentity($nova);
            $post = new Post($kreis);
            $post->message = $nachricht;
            if (!$post->save()) {
                throw new RuntimeException('Beitrag nicht gespeichert: ' . json_encode($post->errors));
            }
            if ($bild !== null) {
                self::bildAnhaengen($post, $bild);
            }
            return $post;
        } finally {
            Yii::$app->user->setIdentity($vorher);
        }
    }

    public static function bildGueltig(?UploadedFile $bild): bool
    {
        if ($bild === null) {
            return true;
        }
        $mime = (string)(mime_content_type($bild->tempName) ?: '');
        return $bild->error === UPLOAD_ERR_OK
            && $bild->size <= self::MAX_BILD_BYTES
            && in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
    }

    private static function bildAnhaengen(Post $post, UploadedFile $bild): void
    {
        try {
            $datei = new FileUpload();
            $datei->setUploadedFile($bild);
            if ($datei->save()) {
                $post->fileManager->attach($datei->guid);
            } else {
                Yii::warning('nexus-hilfe: Bild nicht gespeichert: ' . json_encode($datei->errors), 'nexus-hilfe');
            }
        } catch (Throwable $e) {
            // Ohne Bild ist die Anfrage trotzdem da -- nicht daran scheitern lassen.
            Yii::warning('nexus-hilfe: Bild nicht angehaengt: ' . $e->getMessage(), 'nexus-hilfe');
        }
    }
}
