<?php

namespace humhub\modules\nexusTranslate\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\activity\models\Activity;
use humhub\modules\comment\models\Comment;
use humhub\modules\content\widgets\richtext\converter\RichTextToHtmlConverter;
use humhub\modules\content\widgets\richtext\converter\RichTextToShortTextConverter;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\like\models\Like;
use humhub\modules\nexusTranslate\SprachErkennung;
use humhub\modules\nexusTranslate\TerminUebersetzung;
use humhub\modules\nexusTranslate\UebersetzungsSpeicher;
use humhub\modules\nexusTranslate\WikiVorschau;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\mail\models\UserMessage;
use humhub\modules\nexusTranslate\models\TranslationCache;
use humhub\modules\post\models\Post;
use Throwable;
use Yii;
use yii\web\HttpException;
use yii\web\Response;

class TranslateController extends Controller
{
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_LOGGED_IN_ONLY => ['translate', 'auto', 'aktivitaeten']]];
    }

    /**
     * Automatische Uebersetzung eines Beitrags/Kommentars in die Sprache des Lesers,
     * MIT Formatierung (25.09.2026). Liefert fertiges HTML, gerendert mit HumHubs
     * eigenem RichText -- derselbe Weg wie beim Original, also dieselbe Absicherung.
     * Private Nachrichten bewusst NICHT: die wuerden sonst ungefragt an Google gehen.
     * Je Text und Sprache wird nur EINMAL uebersetzt (Cache mit Pruefsumme -- nach
     * dem Bearbeiten eines Beitrags entsteht die Uebersetzung neu).
     */
    public function actionAuto(int $id, string $type = 'post')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($type === 'comment') {
            $record = Comment::findOne(['id' => $id]);
            if (!$record) {
                throw new HttpException(404, 'Kommentar nicht gefunden.');
            }
            if (!$record->canView()) {
                throw new HttpException(403, 'Kein Zugriff auf diesen Kommentar.');
            }
        } elseif ($type === 'wiki' || $type === 'wikis') {
            // wiki = 500-Zeichen-Vorschau im Stream, wikis = ganze Seite beim Oeffnen (seit 28.09.2026)
            $record = class_exists(WikiVorschau::KLASSE) ? (WikiVorschau::KLASSE)::findOne(['id' => $id]) : null;
            if (!$record) {
                throw new HttpException(404, 'Wiki-Seite nicht gefunden.');
            }
            if (!$record->content->canView()) {
                throw new HttpException(403, 'Kein Zugriff auf diese Wiki-Seite.');
            }
        } elseif ($type === 'cal') {
            $record = class_exists(TerminUebersetzung::KLASSE) ? (TerminUebersetzung::KLASSE)::findOne(['id' => $id]) : null;
            if (!$record) {
                throw new HttpException(404, 'Termin nicht gefunden.');
            }
            if (!$record->content->canView()) {
                throw new HttpException(403, 'Kein Zugriff auf diesen Termin.');
            }
        } else {
            $type = 'post';
            $record = Post::findOne(['id' => $id]);
            if (!$record) {
                throw new HttpException(404, 'Beitrag nicht gefunden.');
            }
            if (!$record->content->canView()) {
                throw new HttpException(403, 'Kein Zugriff auf diesen Beitrag.');
            }
        }

        $speicherId = $id;
        if ($type === 'wiki') {
            $text = WikiVorschau::text($record);
            $von = SprachErkennung::erkenne($text);
        } elseif ($type === 'wikis') {
            $text = (string)($record->latestRevision->content ?? '');
            $von = SprachErkennung::erkenne($text);
        } elseif ($type === 'cal') {
            // Beschreibung uebersetzen, Sprache an Titel + Beschreibung erkennen; wiederkehrende
            // Termine teilen sich EINE Uebersetzung (gespeichert am Serienanfang).
            $text = (string)$record->description;
            $von = SprachErkennung::erkenne($record->title . "\n" . $text);
            $speicherId = TerminUebersetzung::wurzelId($record);
        } else {
            $text = (string)$record->message;
            $von = SprachErkennung::erkenne($text);
        }
        $ziel = SprachErkennung::leser();
        if ($ziel === null || $von === null || $von === $ziel || trim($text) === '') {
            throw new HttpException(400, 'Keine Uebersetzung noetig.');
        }

        $titel = null;
        try {
            $uebersetzt = $this->markdownFassung($type, $speicherId, $text, $ziel);
            if ($type === 'wiki' || $type === 'wikis') {
                $titel = $this->markdownFassung('wikit', $id, (string)$record->title, $ziel);
            }
        } catch (Throwable $e) {
            Yii::error('Auto-Uebersetzung fehlgeschlagen: ' . $e->getMessage(), 'nexus-translate');
            throw new HttpException(502, $e->getMessage());
        }

        // NICHT RichText::output(): das liefert nur den Rohtext, den HumHubs Skript erst im
        // Browser in Formatierung umwandelt -- in unseren nachtraeglich eingesetzten Text
        // greift dieses Skript nicht mehr ein, Josh sah am 25.09.2026 "**", "_" und rohe
        // Bild-Verweise. Der Converter rendert fertiges HTML auf dem Server (inkl. Bilder
        // ueber file-guid, ohne Skripte/Ereignis-Attribute).
        return [
            'html' => RichTextToHtmlConverter::process($uebersetzt, ['record' => $record]),
            'von' => $von,
            // Nur Wiki: der Titel steht im Kopf des Eintrags und wird dort als Text getauscht.
            'titel' => $titel,
        ];
    }

    /**
     * Uebersetzung eines Beitrags/Kommentars MIT Markdown, je Text und Sprache nur EINMAL
     * erzeugt (Cache mit Pruefsumme -- nach dem Bearbeiten entsteht sie neu).
     */
    private function markdownFassung(string $type, int $id, string $text, string $ziel): string
    {
        return UebersetzungsSpeicher::markdown($type, $id, $text, $ziel);
    }

    /**
     * Kurz-Auszuege in "Letzte Aktivitaeten" in der Sprache des Lesers (Josh, 25.09.2026).
     * HumHub zeigt dort z. B. 'Isabel hat einen Beitrag erstellt "hallo ihr lieben! ..."' --
     * der Rahmen ist schon uebersetzt, der Auszug in Anfuehrungszeichen nicht. Liefert je
     * Aktivitaet Paare {original, uebersetzt} der Auszuege; das Skript tauscht sie im
     * sichtbaren Text aus (nur als Text, nie als HTML). Nutzt denselben Cache wie die
     * automatische Uebersetzung im Stream -- meist entstehen keine neuen Kosten.
     */
    public function actionAktivitaeten()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $ziel = SprachErkennung::leser();
        $ids = array_slice(array_unique(array_map('intval', (array)Yii::$app->request->post('ids', []))), 0, 30);
        if ($ziel === null || $ids === []) {
            return ['eintraege' => (object)[]];
        }

        $ergebnis = [];
        foreach (Activity::findAll(['id' => $ids]) as $aktivitaet) {
            try {
                $paare = $this->aktivitaetsPaare($aktivitaet, $ziel);
            } catch (Throwable $e) {
                Yii::warning('Aktivitaet ' . $aktivitaet->id . ' nicht uebersetzt: ' . $e->getMessage(), 'nexus-translate');
                continue;
            }
            if ($paare !== []) {
                $ergebnis[$aktivitaet->id] = $paare;
            }
        }
        return ['eintraege' => (object)$ergebnis];
    }

    /** @return array<int, array{original: string, uebersetzt: string, hinweis: string}> */
    private function aktivitaetsPaare(Activity $aktivitaet, string $ziel): array
    {
        if (!$aktivitaet->content->canView()) {
            return [];
        }
        $quelle = $aktivitaet->getSource();
        // Genau die Kuerzung, die HumHubs Aktivitaets-Vorlage benutzt -- sonst wird der
        // Auszug im Text nicht wiedergefunden: "Neuer Kommentar" = RichText::preview(…, 100)
        // (comment/activities/views/newComment.php), "erstellt"/"gefaellt" =
        // SocialActivity::getContentPreview() mit 60 Zeichen (aber ohne dessen
        // Cache-Schluessel, der gehoert dem Original).
        if ($quelle instanceof Comment) {
            $record = $quelle;
            $kuerzen = fn(string $t) => RichText::preview($t, 100);
        } else {
            $record = $quelle instanceof Like ? $quelle->getSource() : $quelle;
            $kuerzen = fn(string $t) => RichTextToShortTextConverter::process($t, [RichTextToShortTextConverter::OPTION_MAX_LENGTH => 60]);
        }
        $type = $record instanceof Post ? 'post' : ($record instanceof Comment ? 'comment' : null);
        $text = $type ? (string)$record->message : '';
        $von = SprachErkennung::erkenne($text);
        if ($type === null || $von === null || $von === $ziel) {
            return [];
        }
        $kurz = fn(string $t) => html_entity_decode($kuerzen($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return [[
            'original' => $kurz($text),
            'uebersetzt' => $kurz($this->markdownFassung($type, (int)$record->id, $text, $ziel)),
            'hinweis' => SprachErkennung::beschriftung($von)['hinweis'],
        ]];
    }

    public function actionTranslate(int $id, string $type = 'post')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($type === 'comment') {
            $record = Comment::findOne(['id' => $id]);
            if (!$record) {
                throw new HttpException(404, 'Kommentar nicht gefunden.');
            }
            if (!$record->canView()) {
                throw new HttpException(403, 'Kein Zugriff auf diesen Kommentar.');
            }
            $text = $record->message;
        } elseif ($type === 'message') {
            // Private Nachrichten haben kein canView() wie Beitraege/
            // Kommentare -- stattdessen direkt pruefen, ob die anfragende
            // Person wirklich Teilnehmer dieser Konversation ist.
            $record = MessageEntry::findOne(['id' => $id]);
            if (!$record) {
                throw new HttpException(404, 'Nachricht nicht gefunden.');
            }
            $istTeilnehmer = UserMessage::find()
                ->where(['message_id' => $record->message_id, 'user_id' => Yii::$app->user->id])
                ->exists();
            if (!$istTeilnehmer) {
                throw new HttpException(403, 'Kein Zugriff auf diese Konversation.');
            }
            $text = $record->content;
        } else {
            $type = 'post';
            $record = Post::findOne(['id' => $id]);
            if (!$record) {
                throw new HttpException(404, 'Beitrag nicht gefunden.');
            }
            if (!$record->content->canView()) {
                throw new HttpException(403, 'Kein Zugriff auf diesen Beitrag.');
            }
            $text = $record->message;
        }

        $sprache = Yii::$app->language;

        $vorhanden = TranslationCache::findOne([
            'content_type' => $type,
            'content_id' => $id,
            'language' => $sprache,
        ]);
        if ($vorhanden) {
            return ['text' => $vorhanden->translated_text];
        }

        $module = Yii::$app->getModule('nexus-translate');
        try {
            $uebersetzt = $module->uebersetzen($text, $sprache);
        } catch (Throwable $e) {
            Yii::error('Uebersetzung fehlgeschlagen: ' . $e->getMessage(), 'nexus-translate');
            throw new HttpException(502, $e->getMessage());
        }

        $eintrag = new TranslationCache();
        $eintrag->content_type = $type;
        $eintrag->content_id = $id;
        $eintrag->language = $sprache;
        $eintrag->translated_text = $uebersetzt;
        $eintrag->created_at = date('Y-m-d H:i:s');
        try {
            $eintrag->save();
        } catch (\yii\db\Exception $e) {
            // Zwei gleichzeitige Anfragen fuer denselben Text (Doppelklick,
            // zweiter Tab): die andere hat die Uebersetzung schon gespeichert
            // (Primaerschluessel Typ+ID+Sprache). Kein Fehler -- das Ergebnis
            // geht trotzdem an die Person zurueck. Vorher: HTTP 500.
            Yii::info('Uebersetzung war schon gespeichert: ' . $e->getMessage(), 'nexus-translate');
        }

        return ['text' => $uebersetzt];
    }
}
