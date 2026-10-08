<?php

namespace humhub\modules\nexusTranslate;

use humhub\helpers\Html;
use humhub\modules\comment\widgets\CommentEntryLinks;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\nexusTranslate\assets\Assets;
use humhub\modules\nexusTranslate\widgets\CommentTranslateLink;
use humhub\modules\nexusTranslate\widgets\TranslateLink;
use humhub\modules\post\models\Post;
use humhub\modules\nexusTranslate\TerminUebersetzung;
use humhub\modules\nexusTranslate\WikiVorschau;
use humhub\modules\space\models\Space;
use Yii;

class Events
{
    /**
     * Feuert fuer JEDEN Eintrag im Stream -- Beitraege, aber auch
     * Umfragen, Wiki-Seiten, Dateien und mehr. Die Pruefung MUSS hier
     * stattfinden, bevor das Widget ueberhaupt hinzugefuegt wird: Ein
     * fruehes "return" in Widget::init() verhindert in Yii2 das
     * eigentliche Rendern NICHT (init() und render() sind getrennte
     * Schritte) -- das fuehrte am 29.08.2026 dazu, dass die Basisklasse
     * mit einem leeren Titel weiterrenderte und daran abbrach, sobald
     * ein Wiki-Beitrag im selben Strom auftauchte.
     */
    public static function onWallEntryLinksInit($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $wallEntryLinks = $event->sender;
        $record = $wallEntryLinks->object;

        $istWiki = WikiVorschau::istWiki($record) || TerminUebersetzung::istTermin($record);
        if (!$istWiki && (!($record instanceof Post) || trim((string)$record->message) === '')) {
            return;
        }

        $wallEntryLinks->addWidget(
            TranslateLink::class,
            ['record' => $record]
        );
    }

    /**
     * Dasselbe fuer Kommentare -- CommentEntryLinks prueft in seiner
     * eigenen initDefaultWidgets() bereits, dass $object wirklich ein
     * Comment ist, bevor dieses Ereignis ueberhaupt feuert. Die
     * Leertext-Pruefung steckt trotzdem zusaetzlich in
     * CommentTranslateLink::run() -- dort ist ein frueher Ausstieg
     * sicher, siehe Kommentar dort.
     */
    public static function onCommentEntryLinksInit($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        $links = $event->sender;
        $links->addWidget(
            CommentTranslateLink::class,
            ['object' => $links->object],
            ['sortOrder' => 250]
        );
    }

    /**
     * Private Nachrichten (Mail-Modul) haben -- anders als Beitraege und
     * Kommentare -- KEINEN offiziellen Erweiterungspunkt fuer zusaetzliche
     * Aktions-Links (ConversationEntryMenu::initMenus() ist eine reine
     * interne Methode ohne Event). Deshalb hier ein allgemeinerer, aber
     * gaengiger Yii2-Kniff: nach dem Rendern JEDER Ansicht pruefen, ob es
     * die eine Nachrichtenzeilen-Ansicht des Mail-Moduls war, und dann den
     * Uebersetzen-Link selbst anhaengen. $event->output haengt per
     * Referenz (siehe yii\base\View::afterRender()) direkt am tatsaechlich
     * ausgelieferten HTML.
     *
     * Bewusst NICHT ueber eine Datei des Mail-Moduls selbst geloest
     * (Theme-Override o.ae.) -- das waere fragiler bei einem
     * Mail-Modul-Update als dieser rein additive Haken.
     *
     * WICHTIG (Fund 09.09.2026, Meldung aus der Gemeinschaft: mehrfache "Uebersetzen"-
     * Links unter derselben Nachricht): Das Mail-Modul prueft Konversationen
     * im Hintergrund periodisch auf neue Nachrichten (HumHubs Live/Poll-
     * Mechanismus, siehe humhub\modules\mail\live\UserMessageDeleted) und
     * rendert dabei ConversationEntry erneut -- jedes Mal feuert dieses
     * Ereignis erneut und haengt einen weiteren Block an. Serverseitig laesst
     * sich das nicht zuverlaessig verhindern (jeder Render-Aufruf ist ein
     * eigener, unabhaengiger PHP-Request ohne Wissen ueber vorherige). Die
     * Absicherung liegt deshalb bewusst im Skript (nexus.translate.js): ein
     * MutationObserver entfernt ueberzaehlige Bloecke mit derselben
     * data-nexus-message-block-id wieder, sobald sie im DOM auftauchen.
     */
    public static function onViewAfterRender($event): void
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        if (str_ends_with((string)$event->viewFile, 'wiki/views/page/_view_content.php')) {
            self::wikiSeiteMarkieren($event);
            return;
        }

        if (!str_ends_with((string)$event->viewFile, 'mail/widgets/views/conversationEntry.php')) {
            return;
        }

        $entry = $event->params['entry'] ?? null;
        if (!($entry instanceof MessageEntry) || trim((string)$entry->content) === '') {
            return;
        }

        Assets::register(Yii::$app->view);

        $link = Html::a(
            '<i class="fa fa-language" aria-hidden="true"></i> Translate',
            '#',
            ['data-nexus-message-id' => $entry->id, 'class' => 'nexus-translate-message-link']
        );
        $ziel = Html::tag('div', '', ['id' => 'nexus-msg-translate-target-' . $entry->id]);

        $event->output .= Html::tag(
            'div',
            $link . $ziel,
            [
                'class' => 'nexus-translate-message-block',
                'data-nexus-message-block-id' => $entry->id,
                'style' => 'margin: 2px 0 6px 0; font-size: 12px;',
            ]
        );
    }

    /**
     * Ganze Wiki-Seite in der Sprache des Lesers (Josh, 28.09.2026: Anleitungen im Willkommen-Kreis
     * wurden beim Oeffnen nicht uebersetzt -- vorher nur die Vorschau im Stream). Haengt an die
     * Seitenansicht eine unsichtbare Markierung; nexus.translate.js holt die Uebersetzung (Typ
     * "wikis", ganze Seite, je Sprache einmal gespeichert) und tauscht sie ein, sobald das Wiki die
     * Seite fertig aufgebaut hat. Nur die aktuelle Fassung -- beim Ansehen alter Versionen nicht.
     */
    private static function wikiSeiteMarkieren($event): void
    {
        $seite = $event->params['page'] ?? null;
        $inhalt = (string)($event->params['content'] ?? '');
        if (!WikiVorschau::istWiki($seite) || trim($inhalt) === ''
            || $inhalt !== (string)($seite->latestRevision->content ?? '')) {
            return;
        }
        $auto = TranslateLink::autoAttribute($inhalt);
        if ($auto === []) {
            return;
        }
        Assets::register(Yii::$app->view);
        $event->output .= Html::a('', '#', ['hidden' => true, 'class' => 'nexus-wikiseite', 'data-nexus-wikiseite-id' => $seite->id] + $auto);
    }

    /**
     * Neuer oder geaenderter Kreis: Name/Beschreibung uebersetzen -- nur wenn sich
     * eines davon geaendert hat, sonst liefe bei jedem Speichern ein KI-Aufruf.
     */
    public static function onSpaceSaved($event): void
    {
        $space = $event->sender;
        if (!($space instanceof Space)) {
            return;
        }
        $geaendert = $event->changedAttributes ?? null;
        if (is_array($geaendert) && !array_intersect(array_keys($geaendert), KreisUebersetzung::FELDER)) {
            return;
        }
        KreisUebersetzung::sicherAktualisieren($space);
    }

    /**
     * Neuer oder geaenderter Kalendertermin: Titel uebersetzen (siehe TerminUebersetzung).
     * Nur wenn sich Titel oder Beschreibung geaendert haben -- Zu-/Absagen speichern den
     * Termin ebenfalls und sollen keinen KI-Aufruf ausloesen.
     */
    public static function onCalendarEntrySaved($event): void
    {
        $termin = $event->sender;
        if (!TerminUebersetzung::istTermin($termin)) {
            return;
        }
        $geaendert = $event->changedAttributes ?? null;
        if (is_array($geaendert) && !array_intersect(array_keys($geaendert), ['title', 'description'])) {
            return;
        }
        TerminUebersetzung::sicherAktualisieren($termin);
    }

    /**
     * Tauscht beim Ausliefern Kreis-Namen/-Beschreibungen gegen die Uebersetzung in
     * der Sprache des Lesers. Nur HTML/JSON, nur angemeldete Nicht-Deutsch-Leser,
     * nie auf Bearbeitungsseiten, und nur wenn in den Modul-Einstellungen
     * eingeschaltet ("kreisnamenAktiv" -- erst nach Josh' Durchsicht der Liste).
     */
    public static function onResponseAfterPrepare($event): void
    {
        try {
            $response = $event->sender;
            if (!is_string($response->content) || $response->content === '' || $response->stream !== null) {
                return;
            }
            if (!in_array($response->format, ['html', 'json'], true)) {
                return;
            }
            $module = Yii::$app->getModule('nexus-translate');
            if (!$module || !$module->settings->get('kreisnamenAktiv')) {
                return;
            }
            $code = KreisUebersetzung::leserSprache();
            if ($code === null || KreisUebersetzung::istBearbeitungsseite()) {
                return;
            }
            $response->content = KreisUebersetzung::ersetze($response->content, $code);
        } catch (\Throwable $e) {
            Yii::error('Kreis-Namen austauschen fehlgeschlagen: ' . $e->getMessage(), 'nexus-translate');
        }
    }
}
