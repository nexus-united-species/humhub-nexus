<?php

namespace nexus\modules\communityAssistant\services;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\communityAssistant\Module;
use Throwable;
use Yii;

/**
 * Begruessung neuer Mitglieder (Josh, 02.10.2026 -- Text von Josh, siehe TEXTE). Gesendet vom
 * Konto Nova, der Text spricht aber als "Willkommensteam" und nennt die KI nur beim Antworten
 * (Josh: manche Menschen sind vorsichtig und haben Angst vor KI).
 *
 * - "neu": 3 Stunden nach dem ersten Betreten des Portals (= Anlage des Kontos, das entsteht
 *   beim ersten Login ueber Authentik) -- nicht sofort, damit sie nicht in der ersten Klickflut
 *   untergeht. Nur fuer Konten ab STICHTAG.
 * - "nachgeholt": einmalig fuer alle, die sich vorher angemeldet, aber noch nie etwas geschrieben
 *   haben (Josh: "ja unbedingt"). Leicht anderer Anfang.
 *
 * Fester Text, nur EIN Absatz wird je Mensch von der KI geschrieben: ein Satz zu dem, was er bei
 * der Registrierung angegeben hat (nexus_mitgliedsanfrage), mit EINEM passenden Kreis. Die KI
 * waehlt den Kreis nur aus einer festen Liste; Name und Link setzt der Code ein. Ohne Angaben oder
 * ohne passenden Kreis: Hinweis auf die Kreis-Uebersicht, ganz ohne KI.
 */
class WillkommensService
{
    /** Ab hier begruesst Nova automatisch; aeltere Konten nur ueber die Nachhol-Runde. */
    public const STICHTAG = '2026-10-01 00:00:00';
    public const WARTEZEIT_STUNDEN = 3;

    // Vorstellungs-Kreis, nicht vorzuschlagende Kreise und der Termin der Willkommensrunde sind
    // Moduleinstellungen (vorstellungenKreis, nichtVorschlagen, willkommensrundeTermin).

    private const TEXTE = [
        'de' => [
            'titel' => 'Willkommen bei N.E.X.U.S. 🌱',
            'hallo_neu' => 'Hallo %s, herzlich willkommen bei N.E.X.U.S.! 🌱',
            'hallo_nachgeholt' => 'Hallo %s, schön, dass du dich bei N.E.X.U.S. angemeldet hast! 🌱',
            'ich' => 'Wir freuen uns, dass du da bist, und helfen dir gern beim Ankommen.',
            'ich_nachgeholt' => 'Vielleicht bist du noch nicht richtig dazu gekommen, dich umzusehen – deshalb ein paar Tipps zum Ankommen.',
            'vorbei' => 'Schau gern mal vorbei: %s',
            'ohne_kreis' => 'In der Kreis-Übersicht findest du alle Arbeitskreise und Gemeinschaften – schau gern, was dich anspricht: %s',
            'schritte' => [1 => 'Ein einfacher erster Schritt:', 2 => 'Zwei einfache erste Schritte:', 3 => 'Drei einfache erste Schritte:'],
            'vorstellen' => 'Bitte stell dich kurz vor im Kreis „Vorstellungen“ – zwei, drei Sätze genügen: %s',
            'runde' => 'Komm zur Willkommensrunde – jeden Sonntag um 18 Uhr (deutsche Zeit), 30 Minuten, ganz locker. Hier ist der Link dazu: %s – und kurz vorher erscheint im Portal „Jetzt live“ mit Link zum Beitreten.',
            'bild' => 'Lade ein Bild in dein Profil – es ist immer schön, wenn wir dich sehen und erkennen können: %s',
            'fragen' => 'Wenn du Fragen hast, antworte einfach auf diese Nachricht – so kommst du zu Nova (KI-Assistent), unserem Assistenten für Fragen rund um N.E.X.U.S. und das Portal. Weiß Nova nicht weiter, geht deine Frage an einen Menschen aus dem Team.',
            'schluss' => "Schön, dass du da bist!\nDein Willkommensteam",
        ],
        'en' => [
            'titel' => 'Welcome to N.E.X.U.S. 🌱',
            'hallo_neu' => 'Hello %s, a warm welcome to N.E.X.U.S.! 🌱',
            'hallo_nachgeholt' => 'Hello %s, great that you signed up for N.E.X.U.S.! 🌱',
            'ich' => 'We are glad you are here and happy to help you settle in.',
            'ich_nachgeholt' => "Maybe you haven't really had the chance to look around yet – so here are a few tips to get started.",
            'vorbei' => 'Feel free to drop by: %s',
            'ohne_kreis' => 'In the circle overview you will find all working circles and communities – have a look at what speaks to you: %s',
            'schritte' => [1 => 'One simple first step:', 2 => 'Two simple first steps:', 3 => 'Three simple first steps:'],
            'vorstellen' => 'Please introduce yourself briefly in the circle “Introductions” – two or three sentences are enough: %s',
            'runde' => 'Join the welcome round – every Sunday at 6 pm (German time), 30 minutes, very relaxed. Here is the link: %s – and shortly before it starts, the portal shows “Live now” with a link to join.',
            'bild' => 'Upload a picture to your profile – it is always nice when we can see and recognise you: %s',
            'fragen' => "If you have questions, just reply to this message – that's how you reach Nova (AI assistant), our assistant for questions about N.E.X.U.S. and the portal. If Nova doesn't know the answer, your question goes to a person from the team.",
            'schluss' => "Great to have you here!\nYour welcome team",
        ],
        'es' => [
            'titel' => 'Bienvenida/o a N.E.X.U.S. 🌱',
            'hallo_neu' => 'Hola %s, ¡te damos la bienvenida a N.E.X.U.S.! 🌱',
            'hallo_nachgeholt' => 'Hola %s, ¡qué bien que te hayas registrado en N.E.X.U.S.! 🌱',
            'ich' => 'Nos alegra que estés aquí y te ayudamos con gusto a dar tus primeros pasos.',
            'ich_nachgeholt' => 'Quizás aún no has tenido ocasión de mirar con calma – por eso, unos consejos para empezar.',
            'vorbei' => 'Pásate cuando quieras: %s',
            'ohne_kreis' => 'En la vista general de círculos encontrarás todos los círculos de trabajo y comunidades – mira cuál te interesa: %s',
            'schritte' => [1 => 'Un primer paso sencillo:', 2 => 'Dos primeros pasos sencillos:', 3 => 'Tres primeros pasos sencillos:'],
            'vorstellen' => 'Preséntate brevemente en el círculo «Presentaciones» – dos o tres frases bastan: %s',
            'runde' => 'Ven a la ronda de bienvenida – cada domingo a las 18:00 (hora de Alemania), 30 minutos, muy relajada. Aquí está el enlace: %s – y poco antes aparece en el portal «En directo» con un enlace para unirte.',
            'bild' => 'Sube una foto a tu perfil – siempre es bonito poder verte y reconocerte: %s',
            'fragen' => 'Si tienes preguntas, responde simplemente a este mensaje – así llegas a Nova (asistente de IA), nuestro asistente para preguntas sobre N.E.X.U.S. y el portal. Si Nova no sabe la respuesta, tu pregunta pasa a una persona del equipo.',
            'schluss' => "¡Qué bien que estés aquí!\nTu equipo de bienvenida",
        ],
    ];

    public function __construct(private readonly int $assistentId)
    {
    }

    /** @return User[] Neue Konten, deren erstes Betreten mehr als 3 Stunden her ist. */
    public function faelligeNeue(): array
    {
        $bis = date('Y-m-d H:i:s', time() - self::WARTEZEIT_STUNDEN * 3600);
        return $this->ohneBegruessung()
            ->andWhere(['>=', 'user.created_at', self::STICHTAG])
            ->andWhere(['<=', 'user.created_at', $bis])
            ->all();
    }

    /** @return User[] Vor dem Stichtag angemeldet und noch nie einen Beitrag oder Kommentar geschrieben. */
    public function nachzuholende(): array
    {
        return $this->ohneBegruessung()
            ->andWhere(['<', 'user.created_at', self::STICHTAG])
            ->andWhere("NOT EXISTS (SELECT 1 FROM content c WHERE c.created_by = user.id AND c.object_model NOT LIKE '%Activity')")
            ->andWhere('NOT EXISTS (SELECT 1 FROM comment k WHERE k.created_by = user.id)')
            ->all();
    }

    private function ohneBegruessung(): \yii\db\ActiveQuery
    {
        return User::find()
            ->where(['user.status' => User::STATUS_ENABLED])
            ->andWhere(['!=', 'user.id', $this->assistentId])
            ->andWhere('NOT EXISTS (SELECT 1 FROM nexus_willkommen w WHERE w.user_id = user.id)')
            ->orderBy(['user.created_at' => SORT_ASC]);
    }

    /**
     * @return array{titel: string, text: string, kreis_id: ?int}
     */
    public function nachricht(User $mensch, bool $nachgeholt): array
    {
        $sprache = $this->sprache($mensch);
        $t = self::TEXTE[$sprache];
        [$persoenlich, $kreisId] = $this->persoenlicherAbsatz($mensch, $sprache);

        $schritte = [];
        $vorstellungen = (int)Module::instanz()->vorstellungenKreis;
        if ($vorstellungen > 0) {
            $schritte[] = sprintf($t['vorstellen'], $this->kreisLink($vorstellungen));
        }
        $runde = $this->terminLink();
        if ($runde !== null) {
            $schritte[] = sprintf($t['runde'], $runde);
        }
        if (!$this->hatProfilbild($mensch)) {
            $schritte[] = sprintf($t['bild'], self::voll($mensch->getUrl()));
        }
        $liste = [];
        foreach ($schritte as $i => $schritt) {
            $liste[] = ($i + 1) . '. ' . $schritt;
        }

        $vorname = trim((string)($mensch->profile->firstname ?? '')) ?: $mensch->displayName;
        $absaetze = [
            sprintf($t[$nachgeholt ? 'hallo_nachgeholt' : 'hallo_neu'], $vorname),
            $t[$nachgeholt ? 'ich_nachgeholt' : 'ich'],
            $persoenlich,
            $liste === [] ? null : ($t['schritte'][count($liste)] ?? $t['schritte'][3]) . "\n\n" . implode("\n", $liste),
            $t['fragen'],
            $t['schluss'],
        ];
        $absaetze = array_filter($absaetze, static fn($absatz) => $absatz !== null);
        return ['titel' => $t['titel'], 'text' => implode("\n\n", $absaetze), 'kreis_id' => $kreisId];
    }

    /** Begruesst einen Menschen genau einmal. true = gesendet. */
    public function senden(User $mensch, bool $nachgeholt): bool
    {
        $db = Yii::$app->db;
        $neu = $db->createCommand(
            'INSERT IGNORE INTO nexus_willkommen (user_id, art, sent_at) VALUES (:u, :a, :t)',
            [':u' => $mensch->id, ':a' => $nachgeholt ? 'nachgeholt' : 'neu', ':t' => date('Y-m-d H:i:s')]
        )->execute();
        if ($neu === 0) {
            return false;
        }
        try {
            $nachricht = $this->nachricht($mensch, $nachgeholt);
            (new PosterService($this->assistentId))->nachrichtSenden((int)$mensch->id, $nachricht['titel'], $nachricht['text']);
            $db->createCommand()->update('nexus_willkommen', ['kreis_id' => $nachricht['kreis_id']], ['user_id' => $mensch->id])->execute();
            return true;
        } catch (Throwable $e) {
            // Nicht als begruesst stehen lassen -- der naechste Lauf versucht es noch einmal.
            $db->createCommand()->delete('nexus_willkommen', ['user_id' => $mensch->id])->execute();
            Yii::error('nexus-community-assistant: Begruessung fuer user#' . $mensch->id . ' fehlgeschlagen: ' . $e->getMessage(), 'nexus-community-assistant');
            throw $e;
        }
    }

    /** @return array{0: string, 1: ?int} */
    private function persoenlicherAbsatz(User $mensch, string $sprache): array
    {
        $t = self::TEXTE[$sprache];
        $ohne = [sprintf($t['ohne_kreis'], self::voll('/spaces')), null];
        $antworten = $this->antworten($mensch);
        if ($antworten === null) {
            return $ohne;
        }
        $kreise = $this->vorschlagbareKreise();
        $liste = [];
        foreach ($kreise as $kreis) {
            $liste[] = "- id {$kreis->id}: {$kreis->name} – " . trim(preg_replace('/\s+/', ' ', (string)$kreis->description));
        }
        $sprachname = ['de' => 'Deutsch', 'en' => 'Englisch', 'es' => 'Spanisch'][$sprache];
        $prompt = "Ein neues Mitglied hat bei der Registrierung bei N.E.X.U.S. geschrieben:\n"
            . "- Was erhofft es sich: {$antworten['erwartung']}\n"
            . "- Fähigkeiten oder Beruf: {$antworten['faehigkeiten']}\n"
            . "- Was könnte es einbringen: {$antworten['einbringen']}\n\n"
            . "Kreise (Arbeitsgruppen) im Portal:\n" . implode("\n", $liste) . "\n\n"
            . "Wähle GENAU EINEN Kreis, der am besten zu diesen Angaben passt, und schreibe 1–2 warme Sätze auf {$sprachname}, per Du, "
            . "die aufgreifen, was der Mensch geschrieben hat, und erklären, warum dieser Kreis passt. Beschreibe den Kreis NUR mit "
            . "dem, was in seiner Beschreibung steht – erfinde keine Projekte, Sammlungen oder Aktivitäten. Nenne den Kreis mit "
            . "seinem Namen. Ortsgruppen (Gemeinschaften an einem Ort) nur, wenn der Mensch diesen Ort erwähnt. "
            . "Passt kein Kreis wirklich, gib kreis_id null zurück.\n\n"
            . 'Antworte NUR mit JSON: {"kreis_id": <Zahl oder null>, "satz": "<die 1–2 Sätze>"}';
        try {
            $roh = (new AiService())->frage($prompt, null, 0.4);
            $daten = json_decode(preg_replace('/^```[a-z]*\s*|\s*```$/i', '', trim($roh)), true);
        } catch (Throwable $e) {
            Yii::warning('nexus-community-assistant: persoenlicher Absatz nicht moeglich: ' . $e->getMessage(), 'nexus-community-assistant');
            return $ohne;
        }
        $kreisId = is_array($daten) && is_numeric($daten['kreis_id'] ?? null) ? (int)$daten['kreis_id'] : null;
        $satz = is_array($daten) ? trim((string)($daten['satz'] ?? '')) : '';
        if ($kreisId === null || !isset($kreise[$kreisId]) || $satz === '' || mb_strlen($satz) > 600) {
            return $ohne;
        }
        return [$satz . ' ' . sprintf($t['vorbei'], $this->kreisLink($kreisId)), $kreisId];
    }

    /** @return array<int, Space> */
    private function vorschlagbareKreise(): array
    {
        $kreise = [];
        foreach (Space::find()->where(['status' => Space::STATUS_ENABLED])->andWhere(['>', 'join_policy', 0])->orderBy('sort_order')->all() as $kreis) {
            if (!in_array((int)$kreis->id, array_map('intval', Module::instanz()->nichtVorschlagen), true)) {
                $kreise[(int)$kreis->id] = $kreis;
            }
        }
        return $kreise;
    }

    /** @return array<string, string>|null */
    private function antworten(User $mensch): ?array
    {
        $json = Yii::$app->db->createCommand('SELECT antworten FROM nexus_mitgliedsanfrage WHERE user_id = :u', [':u' => $mensch->id])->queryScalar();
        $daten = $json ? json_decode((string)$json, true) : null;
        if (!is_array($daten)) {
            return null;
        }
        $sauber = [];
        foreach (['erwartung', 'faehigkeiten', 'einbringen'] as $feld) {
            $sauber[$feld] = trim(mb_substr((string)($daten[$feld] ?? ''), 0, 600)) ?: '(keine Angabe)';
        }
        return trim(($daten['faehigkeiten'] ?? '') . ($daten['einbringen'] ?? '') . ($daten['erwartung'] ?? '')) === '' ? null : $sauber;
    }

    private function kreisLink(int $id): string
    {
        $kreis = Space::findOne(['id' => $id]);
        return $kreis ? self::voll($kreis->getUrl()) : self::voll('/spaces');
    }

    private function terminLink(): ?string
    {
        $klasse = 'humhub\modules\calendar\models\CalendarEntry';
        $id = (int)Module::instanz()->willkommensrundeTermin;
        if ($id <= 0 || !class_exists($klasse)) {
            return null;
        }
        $termin = $klasse::findOne(['id' => $id]);
        return ($termin && (int)$termin->content->state === 1) ? self::voll($termin->content->getUrl()) : null;
    }

    private function hatProfilbild(User $mensch): bool
    {
        try {
            return $mensch->getProfileImage()->hasImage();
        } catch (Throwable) {
            return false;
        }
    }

    private function sprache(User $mensch): string
    {
        $code = strtolower(explode('-', (string)($mensch->language ?: 'de'))[0]);
        return isset(self::TEXTE[$code]) ? $code : 'de';
    }

    /** Volle Adresse -- in einer Nachricht muss der Link auch aus der E-Mail heraus funktionieren. */
    private static function voll(string $pfad): string
    {
        if (preg_match('#^https?://#', $pfad)) {
            return $pfad;
        }
        $basis = rtrim((string)Yii::$app->settings->get('baseUrl'), '/');
        // Auf der Konsole liefert getUrl() teils einen Pfad mit Skriptnamen -- nur den Pfad ab /s/, /u/ usw. behalten.
        $pfad = preg_replace('#^.*?(/(?:s|u|spaces)(?:/|$))#', '$1', $pfad);
        return $basis . '/' . ltrim($pfad, '/');
    }
}
