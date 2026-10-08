<?php

/**
 * Created by PhpStorm.
 * User: kingb
 * Date: 29.07.2018
 * Time: 09:29
 */

namespace humhub\modules\mail\widgets;

use humhub\helpers\Html;
use humhub\modules\mail\helpers\Url;
use humhub\modules\mail\models\MessageEntry;
use humhub\widgets\JsWidget;
use Yii;

/**
 * Lesestatus-Ergaenzung (Josh, 14.09.2026): HumHub speichert pro
 * Unterhaltung und Person bereits ein "last_viewed"-Datum
 * (UserMessage::last_viewed), nutzt es aber nur intern fuer den eigenen
 * Ungelesen-Zaehler -- nirgends sichtbar fuer den Absender. Diese Klasse
 * (Marktplatz-Modul, liegt in /data/modules/mail, NICHT HumHub-Kern) wird
 * deshalb ergaenzt: bei der jeweils LETZTEN eigenen Nachricht im Thread
 * wird geprueft, ob alle anderen Beteiligten seither (last_viewed >
 * created_at dieser Nachricht) draufgeschaut haben, und das Ergebnis als
 * kleine Zeile unter der Nachricht angezeigt (siehe conversationEntry.php).
 * Absichtlich nur bei der letzten eigenen Nachricht, nicht bei jeder --
 * genau wie bei WhatsApp/Telegram, sonst wird die Unterhaltung unruhig.
 */
class ConversationEntry extends JsWidget
{
    /**
     * @inheritdoc
     */
    public $jsWidget = 'mail.ConversationEntry';

    /**
     * @var MessageEntry
     */
    public $entry;

    /**
     * @var MessageEntry
     */
    public $prevEntry;

    /**
     * @var MessageEntry
     */
    public $nextEntry;

    public bool $showDateBadge = true;

    public array $userColors = ['#34568B', '#FF6F61', '#6B5B95', '#88B04B', '#92A8D1', '#955251', '#B565A7', '#009B77',
        '#DD4124', '#D65076', '#45B8AC', '#EFC050', '#5B5EA6', '#9B2335', '#55B4B0', '#E15D44', '#BC243C', '#C3447A',
    ];

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->entry->type === MessageEntry::type()) {
            return $this->runMessage();
        }

        return $this->runState();
    }

    public function runMessage(): string
    {
        $showUser = $this->showUser();

        return $this->render('conversationEntry', [
            'entry' => $this->entry,
            'contentClass' => $this->getContentClass(),
            'showUser' => $showUser,
            'userColor' => $showUser ? $this->getUserColor() : null,
            'showDateBadge' => $this->showDateBadge(),
            'options' => $this->getOptions(),
            'isOwnMessage' => $this->isOwnMessage(),
            'readStatus' => $this->getReadStatus(),
        ]);
    }

    /**
     * Nur fuer die letzte eigene Nachricht im Thread: ob alle anderen
     * Beteiligten die Unterhaltung seit dieser Nachricht schon geoeffnet
     * haben. Liefert null, wenn hier kein Lesestatus angezeigt werden soll
     * (fremde Nachricht, nicht die letzte eigene, oder keine anderen
     * Beteiligten mehr in der Unterhaltung).
     */
    private function getReadStatus(): ?string
    {
        if (!$this->isOwnMessage() || $this->nextEntry !== null) {
            return null;
        }

        $message = $this->entry->message;
        if (!$message) {
            return null;
        }

        // WICHTIG: $message->users (magische Eigenschaft), NICHT
        // $message->getUsers() -- letzteres liefert nur das unaufgeloeste
        // ActiveQuery-Objekt zurueck, kein durchlaufbares Array.
        $andere = array_filter(
            $message->users,
            fn ($u) => !$u->is(Yii::$app->user->getIdentity())
        );

        if (empty($andere)) {
            return null;
        }

        $ungelesenVon = [];
        foreach ($andere as $person) {
            $userMessage = $message->getUserMessage($person->id);
            $zuletztGesehen = $userMessage ? $userMessage->last_viewed : null;

            if (!$zuletztGesehen || $zuletztGesehen < $this->entry->created_at) {
                $ungelesenVon[] = $person->displayName;
            }
        }

        if (empty($ungelesenVon)) {
            // Feste deutsche Texte statt Yii::t(): erfundene Textbausteine
            // ohne Eintrag in den Sprachdateien wuerden sonst nur auf
            // Englisch (den Quelltext) zurueckfallen -- diese Anwendung
            // ist aber durchgehend Deutsch (siehe CLAUDE.md).
            return count($andere) === 1 ? 'Gelesen' : 'Von allen gelesen';
        }

        if (count($andere) === 1) {
            return 'Noch nicht gelesen';
        }

        return 'Noch nicht gelesen von: ' . implode(', ', $ungelesenVon);
    }

    public function runState(): string
    {
        return $this->render('conversationState', [
            'entry' => $this->entry,
            'showDateBadge' => $this->showDateBadge(),
        ]);
    }

    private function getContentClass(): string
    {
        $result = 'conversation-entry-content';

        if ($this->isOwnMessage()) {
            $result .= ' own';
        }

        return $result;
    }

    private function isOwnMessage(): bool
    {
        return $this->entry->user->is(Yii::$app->user->getIdentity());
    }

    public function getData()
    {
        return [
            'entry-id' => $this->entry->id,
            'delete-url' => Url::toDeleteMessageEntry($this->entry),
            'created-at' => Yii::$app->formatter->asDatetime($this->entry->created_at, 'php:Y-m-d'),
        ];
    }

    public function getAttributes()
    {
        $result = [
            'class' => 'media mail-conversation-entry',
        ];

        if ($this->isOwnMessage()) {
            Html::addCssClass($result, 'own');
        }

        if ($this->isPrevEntryFromSameUser()) {
            Html::addCssClass($result, 'hideUserInfo');
        }

        return $result;
    }

    private function isPrevEntryFromSameUser(): bool
    {
        return $this->prevEntry && $this->prevEntry->created_by === $this->entry->created_by;
    }

    private function showUser(): bool
    {
        return !$this->isOwnMessage();
    }

    private function getUserColor(): string
    {
        return $this->userColors[$this->entry->created_by % count($this->userColors)];
    }

    private function showDateBadge(): bool
    {
        if (!$this->showDateBadge) {
            return false;
        }

        if (!$this->prevEntry) {
            return true;
        }

        $previousEntryDay = Yii::$app->formatter->asDatetime($this->prevEntry->created_at, 'php:Y-m-d');
        $currentEntryDay = Yii::$app->formatter->asDatetime($this->entry->created_at, 'php:Y-m-d');

        return $previousEntryDay !== $currentEntryDay;
    }

}
