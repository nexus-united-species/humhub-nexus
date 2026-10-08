<?php

use humhub\components\View;
use humhub\modules\comment\widgets\CommentEntryLinks;
use humhub\modules\content\widgets\WallEntryLinks;
use humhub\modules\space\models\Space;
use yii\db\ActiveRecord;
use yii\web\Response;
use humhub\modules\nexusTranslate\Events;

return [
    'id' => 'nexus-translate',
    'class' => 'humhub\modules\nexusTranslate\Module',
    'namespace' => 'humhub\modules\nexusTranslate',
    'events' => [
        ['class' => WallEntryLinks::class, 'event' => WallEntryLinks::EVENT_INIT, 'callback' => [Events::class, 'onWallEntryLinksInit']],
        ['class' => CommentEntryLinks::class, 'event' => CommentEntryLinks::EVENT_INIT, 'callback' => [Events::class, 'onCommentEntryLinksInit']],
        // Fuer Nachrichten (Mail-Modul) gibt es keinen offiziellen Haken
        // wie bei Beitraegen/Kommentaren -- deshalb an View::EVENT_AFTER_RENDER
        // angeschlossen und dort gezielt auf die eine Nachrichten-Ansicht
        // gefiltert (siehe Events::onViewAfterRender).
        ['class' => View::class, 'event' => View::EVENT_AFTER_RENDER, 'callback' => [Events::class, 'onViewAfterRender']],
        // Kreis-Namen/-Beschreibungen in der Sprache des Lesers (siehe KreisUebersetzung.php):
        // beim Speichern eines Kreises uebersetzen, beim Ausliefern einer Seite austauschen.
        ['class' => Space::class, 'event' => ActiveRecord::EVENT_AFTER_INSERT, 'callback' => [Events::class, 'onSpaceSaved']],
        ['class' => Space::class, 'event' => ActiveRecord::EVENT_AFTER_UPDATE, 'callback' => [Events::class, 'onSpaceSaved']],
        // Kalendertermine: Titel beim Speichern uebersetzen (siehe TerminUebersetzung.php).
        ['class' => 'humhub\modules\calendar\models\CalendarEntry', 'event' => ActiveRecord::EVENT_AFTER_INSERT, 'callback' => [Events::class, 'onCalendarEntrySaved']],
        ['class' => 'humhub\modules\calendar\models\CalendarEntry', 'event' => ActiveRecord::EVENT_AFTER_UPDATE, 'callback' => [Events::class, 'onCalendarEntrySaved']],
        ['class' => Response::class, 'event' => Response::EVENT_AFTER_PREPARE, 'callback' => [Events::class, 'onResponseAfterPrepare']],
    ],
];
