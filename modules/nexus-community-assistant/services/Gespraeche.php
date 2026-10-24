<?php

namespace nexus\modules\communityAssistant\services;

use humhub\modules\user\models\User;
use nexus\modules\communityAssistant\Module;
use Yii;

/**
 * Nova-Gespraeche (seit 23.10.2026, Abstimmung in Kreis 1): Zugang, Grenzen, Gespraechsarten.
 *
 * Alle Einstellungen pflegen Systemadmins auf Novas Verwaltungsseite (gespeichert in den
 * Moduleinstellungen, nicht in common.php -- Josh: "sollte ein Admin manuell aendern koennen"):
 * - Zugang: Benutzernamen, eine je Zeile (Testgruppe). Systemadmins haben immer Zugang.
 * - Tageslimit: Nachrichten je Mensch und Tag (Vorgabe 20).
 * - Monatsgrenze: geschaetzte KI-Kosten aller Gespraeche im Monat in Euro (Vorgabe 50); danach Pause.
 * - Modell: Claude-Modell im Cockpit (Vorgabe Opus 5.5).
 * Die KI laeuft im Cockpit (Schluessel, Suche in den freigegebenen Quellen) -- hier liegt nur der
 * Zugangsschluessel zur Cockpit-Schnittstelle.
 */
class Gespraeche
{
    public const TAGESLIMIT = 20;
    public const MONATSGRENZE = 50.0;
    public const MODELLE = [
        'claude-opus-5-5' => 'Claude Opus 5.5 (Standard, gründlichste Antworten)',
        'claude-sonnet-5-5' => 'Claude Sonnet 5.5 (etwas günstiger)',
        'claude-haiku-5-5' => 'Claude Haiku 5.5 (am günstigsten, einfacher)',
    ];
    public const MAX_NACHRICHT = 8000;
    /** So viele Nachrichten eines Gespraechs bekommt die KI mit -- aeltere fallen weg */
    public const VERLAUF = 30;

    /** art => [Symbol, Anweisung an Nova] -- Beschriftungen in TEXTE ('art_<art>', 'art_<art>_hilfe') */
    public const ARTEN = [
        'frage' => ['question-circle',
            'Das Mitglied hat Fragen zu N.E.X.U.S. Antworte klar und verstaendlich, belege Inhaltliches mit den '
            . 'Dokumenten und sag ehrlich, wenn etwas dort nicht steht oder noch nicht entschieden ist.'],
        'kritik' => ['comments-o',
            'Das Mitglied moechte auf Kritik antworten -- meist ein Kommentar oder eine Frage von aussen (soziale '
            . 'Medien, Familie, Presse). Hilf zuerst zu verstehen, was die Kritik eigentlich meint. Pruefe dann '
            . 'mit den Dokumenten, was davon zutrifft. Erkenne berechtigte Punkte offen an. Schlage danach eine '
            . 'Antwort vor: respektvoll, ruhig, ehrlich, ohne Abwehr und ohne mehr zu versprechen, als die '
            . 'Dokumente hergeben. Passe Laenge und Ton an den Ort an, an dem geantwortet wird.'],
        'idee' => ['lightbulb-o',
            'Das Mitglied moechte eine Idee entwickeln. Sei ein ermutigender Denkpartner: stelle Rueckfragen, '
            . 'biete Varianten an, verbinde die Idee mit dem Bauplan und mit dem, was es in N.E.X.U.S. schon gibt '
            . '(Kreise, Projekte), und nenne naechste kleine Schritte. Lass dem Mitglied die Entscheidung.'],
        'pruefen' => ['search',
            'Das Mitglied moechte eine Idee, einen Plan oder einen Text kritisch pruefen lassen. Sei ein '
            . 'wohlwollender, ehrlicher Kritiker: Was traegt, wo sind Luecken, Risiken oder Widersprueche zum '
            . 'Bauplan und zur Charta, was wuerden Skeptiker einwenden? Ordne nach Wichtigkeit und schlage '
            . 'Verbesserungen vor. Nicht schoenreden, nicht abwerten.'],
        'text' => ['pencil',
            'Das Mitglied moechte einen Text ueberarbeiten (Beitrag, Brief, Flyer, Einladung ...). Behalte seine '
            . 'Stimme und seine Aussage bei, mache den Text klarer, verstaendlicher und fehlerfrei und achte auf '
            . 'die N.E.X.U.S.-Begriffe. Gib zuerst den ueberarbeiteten Text, danach kurz die wichtigsten '
            . 'Aenderungen. Frag nach Zweck und Leserkreis, wenn das unklar ist.'],
        'antwort' => ['reply',
            'Das Mitglied moechte eine Nachricht beantworten, die an sein Team oder an N.E.X.U.S. ging -- meist steht '
            . 'der bisherige Verlauf in der ersten Nachricht. Erfasse, was die schreibende Person eigentlich wissen '
            . 'oder erreichen will, pruefe Inhaltliches mit den Dokumenten und schlage eine kurze, freundliche, '
            . 'ehrliche Antwort in der Sprache der Unterhaltung vor. Versprich nichts, was nicht entschieden ist, und '
            . 'nenne offen, was das Team noch klaeren oder an die Kreisleitung geben sollte.'],
    ];

    private const TEXTE = [
        'de' => [
            'titel' => 'Nova – Gespräche',
            'neues' => 'Neues Gespräch',
            'art_frage' => 'Frage zu N.E.X.U.S.', 'art_frage_hilfe' => 'Fragen zum Bauplan, zur Charta, zu Gemeinschaften, zur OneApp …',
            'art_kritik' => 'Auf Kritik antworten', 'art_kritik_hilfe' => 'Einen kritischen Kommentar verstehen und gut darauf antworten',
            'art_idee' => 'Idee entwickeln', 'art_idee_hilfe' => 'Gemeinsam eine Idee weiterdenken',
            'art_pruefen' => 'Kritisch prüfen lassen', 'art_pruefen_hilfe' => 'Nova sucht Lücken und Risiken in deiner Idee oder deinem Text',
            'art_text' => 'Text überarbeiten', 'art_text_hilfe' => 'Einen Beitrag, Brief oder Flyer klarer und fehlerfrei machen',
            'art_antwort' => 'Antwort vorbereiten', 'art_antwort_hilfe' => 'Eine Nachricht an dich oder dein Team gut beantworten',
            'starten' => 'Gespräch beginnen',
            'keine' => 'Noch keine Gespräche. Wähle links, worum es gehen soll.',
            'eingabe' => 'Deine Nachricht an Nova …',
            'senden' => 'Senden',
            'denkt' => 'Nova denkt nach und sucht in den Dokumenten … (das kann bis zu einer Minute dauern)',
            'quellen' => 'Quellen',
            'loeschen' => 'Gespräch löschen',
            'loeschen_frage' => 'Dieses Gespräch mit allen Nachrichten löschen?',
            'gedaechtnis' => 'Was Nova sich über dich merkt',
            'gedaechtnis_hilfe' => 'Damit Nova dich in späteren Gesprächen besser versteht. Du kannst jeden Eintrag löschen.',
            'gedaechtnis_leer' => 'Noch nichts.',
            'vergessen' => 'Vergessen',
            'heute' => 'Heute noch %d von %d Nachrichten',
            'limit' => 'Für heute hast du alle %d Nachrichten genutzt. Morgen geht es weiter.',
            'pause' => 'Die Nova-Gespräche machen für diesen Monat Pause (das gemeinsame Budget ist aufgebraucht).',
            'stoerung' => 'Nova konnte gerade nicht antworten (technische Störung). Deine Nachricht ist gespeichert – versuche es gleich noch einmal.',
            'kein_zugang' => 'Die Nova-Gespräche sind gerade in der Testphase und noch nicht für alle freigeschaltet.',
            'datenschutz' => 'Gut zu wissen: Deine Gespräche sieht im Portal nur du – auch die Admins nicht. Damit Nova antworten kann, gehen deine Nachrichten an den KI-Anbieter Anthropic (Claude); er nutzt sie nicht zum Trainieren. Schreib bitte keine Gesundheitsdaten, Passwörter oder Angaben über andere Menschen hinein. Nova kann sich irren – prüfe Wichtiges in den Quellen nach.',
            'zu_lang' => 'Die Nachricht ist zu lang (höchstens %d Zeichen).',
            'nova' => 'Nova',
            'du' => 'Du',
        ],
        'en' => [
            'titel' => 'Nova – Conversations',
            'neues' => 'New conversation',
            'art_frage' => 'Question about N.E.X.U.S.', 'art_frage_hilfe' => 'Questions about the Blueprint, the Charter, communities, the OneApp …',
            'art_kritik' => 'Respond to criticism', 'art_kritik_hilfe' => 'Understand a critical comment and answer it well',
            'art_idee' => 'Develop an idea', 'art_idee_hilfe' => 'Think an idea further together',
            'art_pruefen' => 'Get critical feedback', 'art_pruefen_hilfe' => 'Nova looks for gaps and risks in your idea or text',
            'art_text' => 'Improve a text', 'art_text_hilfe' => 'Make a post, letter or flyer clearer and error-free',
            'art_antwort' => 'Prepare a reply', 'art_antwort_hilfe' => 'Answer a message to you or your team well',
            'starten' => 'Start conversation',
            'keine' => 'No conversations yet. Choose on the left what it should be about.',
            'eingabe' => 'Your message to Nova …',
            'senden' => 'Send',
            'denkt' => 'Nova is thinking and searching the documents … (this can take up to a minute)',
            'quellen' => 'Sources',
            'loeschen' => 'Delete conversation',
            'loeschen_frage' => 'Delete this conversation with all its messages?',
            'gedaechtnis' => 'What Nova remembers about you',
            'gedaechtnis_hilfe' => 'So Nova understands you better in later conversations. You can delete any entry.',
            'gedaechtnis_leer' => 'Nothing yet.',
            'vergessen' => 'Forget',
            'heute' => '%d of %d messages left today',
            'limit' => 'You have used all %d messages for today. See you tomorrow.',
            'pause' => 'Nova conversations are paused for this month (the shared budget is used up).',
            'stoerung' => 'Nova could not answer right now (technical problem). Your message is saved – please try again in a moment.',
            'kein_zugang' => 'Nova conversations are in a test phase and not yet open to everyone.',
            'datenschutz' => 'Good to know: only you can see your conversations in the portal – not even the admins. So that Nova can answer, your messages are sent to the AI provider Anthropic (Claude); it does not use them for training. Please do not enter health data, passwords or information about other people. Nova can be wrong – check important things in the sources.',
            'zu_lang' => 'The message is too long (at most %d characters).',
            'nova' => 'Nova',
            'du' => 'You',
        ],
        'es' => [
            'titel' => 'Nova – Conversaciones',
            'neues' => 'Nueva conversación',
            'art_frage' => 'Pregunta sobre N.E.X.U.S.', 'art_frage_hilfe' => 'Preguntas sobre el Plan, la Carta, las comunidades, la OneApp …',
            'art_kritik' => 'Responder a una crítica', 'art_kritik_hilfe' => 'Entender un comentario crítico y responder bien',
            'art_idee' => 'Desarrollar una idea', 'art_idee_hilfe' => 'Pensar juntos una idea',
            'art_pruefen' => 'Revisión crítica', 'art_pruefen_hilfe' => 'Nova busca lagunas y riesgos en tu idea o tu texto',
            'art_text' => 'Mejorar un texto', 'art_text_hilfe' => 'Hacer más claro y sin errores una publicación, carta o folleto',
            'art_antwort' => 'Preparar una respuesta', 'art_antwort_hilfe' => 'Responder bien a un mensaje para ti o tu equipo',
            'starten' => 'Empezar conversación',
            'keine' => 'Todavía no hay conversaciones. Elige a la izquierda de qué se trata.',
            'eingabe' => 'Tu mensaje a Nova …',
            'senden' => 'Enviar',
            'denkt' => 'Nova está pensando y buscando en los documentos … (puede tardar hasta un minuto)',
            'quellen' => 'Fuentes',
            'loeschen' => 'Borrar conversación',
            'loeschen_frage' => '¿Borrar esta conversación con todos sus mensajes?',
            'gedaechtnis' => 'Lo que Nova recuerda de ti',
            'gedaechtnis_hilfe' => 'Para que Nova te entienda mejor en conversaciones futuras. Puedes borrar cualquier entrada.',
            'gedaechtnis_leer' => 'Nada todavía.',
            'vergessen' => 'Olvidar',
            'heute' => 'Hoy te quedan %d de %d mensajes',
            'limit' => 'Hoy ya has usado los %d mensajes. Mañana seguimos.',
            'pause' => 'Las conversaciones con Nova están en pausa este mes (el presupuesto común se ha agotado).',
            'stoerung' => 'Nova no ha podido responder ahora (problema técnico). Tu mensaje está guardado; inténtalo de nuevo en un momento.',
            'kein_zugang' => 'Las conversaciones con Nova están en fase de prueba y aún no están abiertas a todos.',
            'datenschutz' => 'Bueno saberlo: en el portal solo tú ves tus conversaciones, ni siquiera los administradores. Para que Nova pueda responder, tus mensajes se envían al proveedor de IA Anthropic (Claude), que no los usa para entrenar. No escribas datos de salud, contraseñas ni información sobre otras personas. Nova puede equivocarse: comprueba lo importante en las fuentes.',
            'zu_lang' => 'El mensaje es demasiado largo (máximo %d caracteres).',
            'nova' => 'Nova',
            'du' => 'Tú',
        ],
    ];

    /**
     * Eine Nova, zwei Rollen (Josh, 24.10.2026): "Nova (KI-Assistent)" = das Konto mit kurzen Antworten und
     * festen Nachrichten, "Nova – Gespraeche" = ausfuehrlich, mit Quellen und Gedaechtnis. Diese Texte machen
     * den Unterschied sichtbar.
     */
    private const ROLLEN = [
        'de' => [
            'untertitel' => 'ausführlich, mit Quellen und Gedächtnis',
            'wegweiser' => 'Ausführlicher, mit Quellen und Gedächtnis: [Nova – Gespräche](%s)',
            'automatisch' => '🤖 Automatische Nachricht von Nova (KI-Assistent)',
        ],
        'en' => [
            'untertitel' => 'in depth, with sources and memory',
            'wegweiser' => 'In more depth, with sources and memory: [Nova – Conversations](%s)',
            'automatisch' => '🤖 Automatic message from Nova (AI assistant)',
        ],
        'es' => [
            'untertitel' => 'en profundidad, con fuentes y memoria',
            'wegweiser' => 'Con más detalle, con fuentes y memoria: [Nova – Conversaciones](%s)',
            'automatisch' => '🤖 Mensaje automático de Nova (asistente de IA)',
        ],
    ];

    public static function t(string $schluessel, ?string $sprache = null): string
    {
        $sprache = substr($sprache ?: (string)Yii::$app->language, 0, 2);
        return self::TEXTE[$sprache][$schluessel] ?? self::ROLLEN[$sprache][$schluessel]
            ?? self::TEXTE['de'][$schluessel] ?? self::ROLLEN['de'][$schluessel] ?? $schluessel;
    }

    /** Feste Nachrichten (Erinnerungen, Spendenaufruf) als solche kennzeichnen -- die schreibt keine KI frei */
    public static function mitHinweisAutomatisch(string $text, ?string $sprache = 'de'): string
    {
        return rtrim($text) . "\n\n_" . self::t('automatisch', $sprache) . '_';
    }

    /** Unter Novas kurzer Antwort auf eine private Nachricht: Weg zu den Gespraechen -- nur fuer, wer Zugang hat */
    public static function wegweiser(?User $mensch): string
    {
        if (!self::darfNutzen($mensch)) {
            return '';
        }
        $url = rtrim((string)Yii::$app->settings->get('baseUrl'), '/') . '/nexus-community-assistant/gespraech/index';
        return "\n\n_" . sprintf(self::t('wegweiser', $mensch->language ?: 'de'), $url) . '_';
    }

    // ── Einstellungen ───────────────────────────────────────────────────────

    private static function einstellung(string $name, $vorgabe = null)
    {
        return Module::instanz()->settings->get('gespraeche.' . $name, $vorgabe);
    }

    public static function setze(string $name, $wert): void
    {
        Module::instanz()->settings->set('gespraeche.' . $name, $wert);
    }

    /** @return string[] Benutzernamen mit Zugang (Testgruppe) */
    public static function zugang(): array
    {
        $zeilen = preg_split('/[\s,;]+/', (string)self::einstellung('zugang', ''));
        return array_values(array_unique(array_filter(array_map('trim', $zeilen))));
    }

    public static function tageslimit(): int
    {
        return max(0, (int)self::einstellung('tageslimit', self::TAGESLIMIT));
    }

    public static function monatsgrenze(): float
    {
        return max(0.0, (float)self::einstellung('monatsgrenze', self::MONATSGRENZE));
    }

    public static function modell(): string
    {
        $m = (string)self::einstellung('modell', '');
        return isset(self::MODELLE[$m]) ? $m : array_key_first(self::MODELLE);
    }

    public static function token(): string
    {
        return (string)self::einstellung('token', '');
    }

    /** Ohne Cockpit-Adresse (common.php) oder Schluessel gibt es keine Gespraeche */
    public static function eingerichtet(): bool
    {
        return (string)Module::instanz()->cockpitUrl !== '' && self::token() !== '';
    }

    // ── Zugang und Grenzen ──────────────────────────────────────────────────

    public static function darfNutzen(?User $mensch): bool
    {
        if ($mensch === null || !self::eingerichtet()) {
            return false;
        }
        return $mensch->isSystemAdmin() || in_array(mb_strtolower($mensch->username), array_map('mb_strtolower', self::zugang()), true)
            || self::imTeam($mensch);
    }

    /** Wer in einem Team mitarbeitet (Modul nexus-projekte), bekommt Nova automatisch -- Josh, 24.10.2026 */
    private static function imTeam(User $mensch): bool
    {
        try {
            return (new \yii\db\Query())->from('nexus_team_mitglied')->where(['user_id' => $mensch->id, 'status' => 'dabei'])->exists();
        } catch (\Throwable $e) {
            return false;   // Modul nexus-projekte ohne Teams-Tabelle
        }
    }

    public static function heuteGenutzt(User $mensch): int
    {
        return (int)Yii::$app->db->createCommand('SELECT anzahl FROM nexus_nova_nutzung WHERE user_id = :u AND datum = :d',
            [':u' => $mensch->id, ':d' => date('Y-m-d')])->queryScalar();
    }

    public static function monatsKosten(?string $monat = null): float
    {
        $monat = $monat ?? date('Y-m');
        return (float)Yii::$app->db->createCommand('SELECT COALESCE(SUM(kosten), 0) FROM nexus_nova_nutzung WHERE datum BETWEEN :von AND :bis',
            [':von' => $monat . '-01', ':bis' => $monat . '-31'])->queryScalar();
    }

    /** Warum gerade nichts geht -- oder null, wenn gesendet werden darf */
    public static function sperre(User $mensch): ?string
    {
        if (self::heuteGenutzt($mensch) >= self::tageslimit()) {
            return sprintf(self::t('limit'), self::tageslimit());
        }
        if (self::monatsgrenze() > 0 && self::monatsKosten() >= self::monatsgrenze()) {
            return self::t('pause');
        }
        return null;
    }

    public static function zaehlen(User $mensch, float $kosten): void
    {
        Yii::$app->db->createCommand(
            'INSERT INTO nexus_nova_nutzung (user_id, datum, anzahl, kosten) VALUES (:u, :d, 1, :k) '
            . 'ON DUPLICATE KEY UPDATE anzahl = anzahl + 1, kosten = kosten + VALUES(kosten)',
            [':u' => $mensch->id, ':d' => date('Y-m-d'), ':k' => round($kosten, 5)]
        )->execute();
    }
}
