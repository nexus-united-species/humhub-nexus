<?php

namespace nexus\modules\communityAssistant\services;

use humhub\modules\content\widgets\richtext\converter\RichTextToPlainTextConverter;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

/**
 * Liest die Aktivitaet der letzten 7 Tage direkt aus der HumHub-Datenbank
 * (Beitraege, Kommentare, Wiki-Aenderungen) zusammen, laesst Gemini daraus
 * einen kurzen Wochenbericht schreiben und schickt ihn per E-Mail an alle
 * Systemadministratoren. Bewusst eigenstaendig -- kein Zugriff auf das
 * Cockpit/Second-Brain (anderes System, andere Zustaendigkeit), um dieses
 * Modul unabhaengig lauffaehig zu halten.
 */
class WeeklySummaryService
{
    private const WEEKLY_SUMMARY_PROMPT =
        'Du bist Nova, der N.E.X.U.S. KI-Assistent. Schreibe aus dem folgenden '
        . 'Rohprotokoll der Aktivitaet der letzten 7 Tage in unserer HumHub-'
        . 'Plattform einen kurzen, strukturierten Wochenbericht auf Deutsch fuer '
        . 'die Administratoren. Gliedere nach Kreis/Space, wenn sinnvoll. Hebe '
        . 'die inhaltlich wichtigsten Themen und Diskussionen hervor, nicht jede '
        . 'einzelne Aktion. Wenn ein Bereich keine nennenswerte Aktivitaet hatte, '
        . 'erwaehne das kurz oder lass ihn weg. Halte dich an maximal 400 Woerter. '
        . 'Antworte nur mit dem reinen Berichtstext, ohne Einleitungssatz wie '
        . '"Hier ist der Bericht".';

    public function erstellenUndVersenden(): void
    {
        $seit = (new \DateTimeImmutable('-7 days'))->format('Y-m-d H:i:s');
        $protokoll = $this->rohprotokoll($seit);

        if (trim($protokoll) === '') {
            Yii::info('nexus-community-assistant: keine Aktivitaet in den letzten 7 Tagen -- kein Wochenbericht.');
            return;
        }

        $bericht = (new AiService())->frage($protokoll, self::WEEKLY_SUMMARY_PROMPT, 0.5);
        $this->anAdminsSenden($bericht);
    }

    private function rohprotokoll(string $seit): string
    {
        $zeilen = [];

        // Beitraege der letzten 7 Tage
        $beitraege = (new \yii\db\Query())
            ->select(['post.message', 'content.created_at', 'content.contentcontainer_id', 'content.created_by'])
            ->from('post')
            ->innerJoin('content', "content.object_model = 'humhub\\\\modules\\\\post\\\\models\\\\Post' AND content.object_id = post.id")
            ->where(['>=', 'content.created_at', $seit])
            ->andWhere(['content.state' => 1])
            ->orderBy(['content.created_at' => SORT_ASC])
            ->all();

        foreach ($beitraege as $b) {
            $zeilen[] = sprintf(
                "[%s] Beitrag in %s von %s: %s",
                $b['created_at'],
                $this->spaceName((int)$b['contentcontainer_id']),
                $this->userName((int)$b['created_by']),
                $this->klartext((string)$b['message'])
            );
        }

        // Kommentare der letzten 7 Tage
        $kommentare = (new \yii\db\Query())
            ->select(['comment.message', 'comment.created_at', 'comment.created_by', 'content.contentcontainer_id'])
            ->from('comment')
            ->innerJoin('content', 'content.object_model = comment.object_model AND content.object_id = comment.object_id')
            ->where(['>=', 'comment.created_at', $seit])
            ->orderBy(['comment.created_at' => SORT_ASC])
            ->all();

        foreach ($kommentare as $k) {
            $zeilen[] = sprintf(
                "[%s] Kommentar in %s von %s: %s",
                $k['created_at'],
                $this->spaceName((int)$k['contentcontainer_id']),
                $this->userName((int)$k['created_by']),
                $this->klartext((string)$k['message'])
            );
        }

        // Neue oder geaenderte Wiki-Seiten der letzten 7 Tage
        $wikiSeiten = (new \yii\db\Query())
            ->select(['wiki_page.title', 'content.created_at', 'content.updated_at', 'content.contentcontainer_id'])
            ->from('wiki_page')
            ->innerJoin('content', "content.object_model = 'humhub\\\\modules\\\\wiki\\\\models\\\\WikiPage' AND content.object_id = wiki_page.id")
            ->where(['>=', 'content.created_at', $seit])
            ->orWhere(['>=', 'content.updated_at', $seit])
            ->all();

        foreach ($wikiSeiten as $w) {
            $istNeu = $w['created_at'] >= $seit;
            $zeilen[] = sprintf(
                "[%s] Wiki-Seite %s in %s: \"%s\"",
                $istNeu ? $w['created_at'] : $w['updated_at'],
                $istNeu ? 'neu angelegt' : 'geaendert',
                $this->spaceName((int)$w['contentcontainer_id']),
                $w['title']
            );
        }

        usort($zeilen, fn($a, $b) => strcmp($a, $b));

        return implode("\n", $zeilen);
    }

    private function klartext(string $roh): string
    {
        $text = RichTextToPlainTextConverter::process($roh);
        $text = trim(preg_replace('/\s+/', ' ', $text));
        return mb_substr($text, 0, 500);
    }

    /** @var array<int,string> */
    private array $spaceNamenCache = [];

    private function spaceName(int $contentcontainerId): string
    {
        if (!isset($this->spaceNamenCache[$contentcontainerId])) {
            $space = Space::find()->where(['contentcontainer_id' => $contentcontainerId])->one();
            $this->spaceNamenCache[$contentcontainerId] = $space ? $space->name : 'unbekannter Bereich';
        }

        return $this->spaceNamenCache[$contentcontainerId];
    }

    /** @var array<int,string> */
    private array $userNamenCache = [];

    private function userName(?int $userId): string
    {
        if ($userId === null) {
            return 'unbekannt';
        }
        if (!isset($this->userNamenCache[$userId])) {
            $user = User::findOne(['id' => $userId]);
            $this->userNamenCache[$userId] = $user ? $user->displayName : 'unbekannt';
        }

        return $this->userNamenCache[$userId];
    }

    private function anAdminsSenden(string $bericht): void
    {
        $admins = User::find()
            ->innerJoin('group_user', 'group_user.user_id = user.id')
            ->innerJoin('group', 'group.id = group_user.group_id')
            ->andWhere(['user.status' => User::STATUS_ENABLED, 'group.is_admin_group' => true])
            ->all();

        // Zusaetzlich als Portal-Nachricht (Josh, 05.10.2026: Bericht fehlte -- die E-Mail ging an
        // die Adresse des Admin-Kontos und vom neuen Absender no-reply@, also leicht in den Spam).
        // Im Portal-Postfach kann er nicht verloren gehen.
        $benachrichtigung = 'nexus\modules\arbeitszeit\services\Benachrichtigung';
        if (class_exists($benachrichtigung)) {
            foreach ($admins as $admin) {
                $benachrichtigung::senden((int)$admin->id, '📊 Nova: Wochenbericht', "📊 **Wochenbericht vom " . date('d.m.Y') . "**\n\n" . $bericht);
            }
        }

        foreach ($admins as $admin) {
            if (empty($admin->email)) {
                continue;
            }
            try {
                Yii::$app->mailer->compose()
                    ->setTo($admin->email)
                    ->setSubject('Nova (N.E.X.U.S. KI-Assistent): Wochenbericht')
                    ->setTextBody($bericht)
                    ->send();
            } catch (\Throwable $e) {
                Yii::error('nexus-community-assistant: Wochenbericht-Versand fehlgeschlagen: ' . $e->getMessage());
            }
        }
    }
}
