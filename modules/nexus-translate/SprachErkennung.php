<?php

namespace humhub\modules\nexusTranslate;

use Yii;

/**
 * Automatische Uebersetzung von Beitraegen und Kommentaren in die Sprache des
 * Lesers (Josh, 25.09.2026): Wer auf Spanisch schreibt, wird auf Deutsch gelesen
 * und umgekehrt -- eine Plattform statt drei Parallelwelten.
 *
 * Die Sprache eines Textes wird hier OHNE KI-Aufruf erkannt (Zaehlen haeufiger
 * Fuellwoerter). Nur wenn sie sicher erkannt ist UND von der Sprache des Lesers
 * abweicht, wird uebersetzt -- sonst kostete jeder angezeigte Beitrag einen
 * Aufruf. Unsicher erkannte (sehr kurze, gemischte) Texte bleiben im Original;
 * dort hilft weiter der "Translate"-Knopf.
 */
class SprachErkennung
{
    public const SPRACHEN = ['de', 'en', 'es', 'ru', 'fr'];

    private const WOERTER = [
        'de' => ['der', 'die', 'das', 'und', 'ist', 'nicht', 'mit', 'ich', 'wir', 'sie', 'ein', 'eine', 'auch', 'auf',
            'für', 'zu', 'es', 'den', 'dem', 'von', 'sich', 'wie', 'aber', 'noch', 'nur', 'wenn', 'dass', 'hat', 'sind', 'bei',
            // Seit 25.09.2026: typische Woerter kurzer Kommentare ("ja bitte teil uns das mit" blieb sonst unerkannt).
            // Bewusst NICHT: "was"/"so"/"will" (auch englisch), "me" (englisch/spanisch).
            'ja', 'bitte', 'uns', 'sehr', 'mir', 'mich', 'dich', 'du', 'ihr', 'euch', 'hier', 'schon', 'jetzt', 'danke',
            'gut', 'habe', 'haben', 'wird', 'oder', 'kann', 'gerne', 'toll', 'liebe', 'viel'],
        'en' => ['the', 'and', 'is', 'are', 'not', 'with', 'you', 'we', 'they', 'this', 'that', 'for', 'to', 'of', 'it',
            'be', 'have', 'on', 'what', 'but', 'can', 'will', 'our', 'your', 'from', 'was', 'there', 'about', 'would', 'just',
            'i', 'my', 'please', 'thanks', 'thank', 'very', 'yes', 'do', 'if', 'all', 'great', 'good', 'love', 'here', 'now'],
        'es' => ['el', 'la', 'los', 'las', 'y', 'es', 'no', 'con', 'que', 'de', 'por', 'para', 'una', 'un', 'se', 'lo',
            'como', 'pero', 'más', 'muy', 'del', 'al', 'en', 'su', 'mi', 'está', 'son', 'también', 'hay', 'todo',
            'yo', 'te', 'nos', 'gracias', 'sí', 'ya', 'este', 'esta', 'bien', 'hola', 'tengo', 'estoy', 'aquí', 'ahora',
            'mucho', 'cuando', 'porque'],
        'ru' => ['и', 'в', 'не', 'на', 'я', 'что', 'он', 'с', 'как', 'это', 'мы', 'вы', 'они', 'по', 'но', 'для', 'из',
            'от', 'так', 'все', 'вас', 'нас', 'есть', 'был', 'была', 'будет', 'или', 'да', 'нет', 'уже', 'очень', 'при',
            'спасибо', 'пожалуйста', 'привет', 'здравствуйте', 'можно', 'нужно', 'если', 'тоже', 'только', 'здесь', 'сейчас', 'хорошо'],
        // Franzoesisch (seit 29.10.2026). Bewusst ohne Woerter, die auch spanisch/englisch/deutsch haeufig sind
        // (de, en, la, a, on, an, in ...), damit die Erkennung nicht kippt.
        'fr' => ['le', 'les', 'des', 'du', 'est', 'et', 'pas', 'nous', 'vous', 'je', 'il', 'elle', 'ils', 'pour', 'dans',
            'avec', 'sur', 'mais', 'où', 'une', 'ce', 'cette', 'ces', 'sont', 'être', 'très', 'merci', 'bonjour', 'oui',
            'aussi', 'tout', 'comme', 'notre', 'votre', 'nos', 'vos', 'ici', 'plus', 'fait', 'faire', 'peut', 'tu', 'toi',
            'ton', 'ta', 'tes', 'qu', 'c', 'l', 'd', 'j', 'ça', 'bien', 'maintenant', 'cercle', 'communauté'],
    ];

    /** Erkennt de/en/es oder liefert null, wenn es nicht eindeutig ist. */
    public static function erkenne(?string $text): ?string
    {
        // Markdown-Links/Erwaehnungen und Adressen zaehlen nicht mit.
        $sauber = preg_replace('/\[[^\]]*\]\([^)]*\)|https?:\/\/\S+/u', ' ', mb_strtolower((string)$text));
        preg_match_all('/[\p{L}]+/u', mb_substr($sauber, 0, 1500), $m);
        $woerter = $m[0];
        if (count($woerter) < 6) {
            return null;
        }
        $punkte = [];
        foreach (self::WOERTER as $sprache => $liste) {
            $menge = array_flip($liste);
            $punkte[$sprache] = count(array_filter($woerter, fn($w) => isset($menge[$w])));
        }
        arsort($punkte);
        [$erste, $zweite] = array_slice(array_values($punkte), 0, 2);
        if ($erste < 3 || $erste < 1.5 * $zweite) {
            return null;
        }
        return array_key_first($punkte);
    }

    /** Sprache des angemeldeten Lesers als de/en/es, sonst null. */
    public static function leser(): ?string
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return in_array($code, self::SPRACHEN, true) ? $code : null;
    }

    /** Sollte dieser Text fuer den aktuellen Leser automatisch uebersetzt werden? Liefert die Quellsprache. */
    public static function autoQuelle(?string $text): ?string
    {
        $leser = self::leser();
        if ($leser === null) {
            return null;
        }
        $von = self::erkenne($text);
        return ($von !== null && $von !== $leser) ? $von : null;
    }

    /** Beschriftungen des Hinweises, in der Sprache des Lesers. */
    public static function beschriftung(string $von): array
    {
        $namen = [
            'de' => ['de' => 'Deutsch', 'en' => 'Englisch', 'es' => 'Spanisch', 'ru' => 'Russisch', 'fr' => 'Französisch'],
            'en' => ['de' => 'German', 'en' => 'English', 'es' => 'Spanish', 'ru' => 'Russian', 'fr' => 'French'],
            'es' => ['de' => 'alemán', 'en' => 'inglés', 'es' => 'español', 'ru' => 'ruso', 'fr' => 'francés'],
            'ru' => ['de' => 'немецкого', 'en' => 'английского', 'es' => 'испанского', 'ru' => 'русского', 'fr' => 'французского'],
            'fr' => ['de' => 'de l’allemand', 'en' => 'de l’anglais', 'es' => 'de l’espagnol', 'ru' => 'du russe', 'fr' => 'du français'],
        ];
        $texte = [
            'de' => ['hinweis' => 'Automatisch übersetzt aus dem %s', 'original' => 'Original anzeigen', 'uebersetzung' => 'Übersetzung anzeigen'],
            'en' => ['hinweis' => 'Automatically translated from %s', 'original' => 'Show original', 'uebersetzung' => 'Show translation'],
            'es' => ['hinweis' => 'Traducido automáticamente del %s', 'original' => 'Ver original', 'uebersetzung' => 'Ver traducción'],
            'ru' => ['hinweis' => 'Автоматический перевод с %s языка', 'original' => 'Показать оригинал', 'uebersetzung' => 'Показать перевод'],
            'fr' => ['hinweis' => 'Traduit automatiquement %s', 'original' => 'Afficher l’original', 'uebersetzung' => 'Afficher la traduction'],
        ];
        $leser = self::leser() ?? 'de';
        $t = $texte[$leser];
        $name = $namen[$leser][$von] ?? $von;
        // "aus dem Spanischen" / "from Spanish" / "del español"
        $hinweis = $leser === 'de' ? sprintf('Automatisch übersetzt aus dem %sen', $name) : sprintf($t['hinweis'], $name);
        return ['hinweis' => $hinweis, 'original' => $t['original'], 'uebersetzung' => $t['uebersetzung']];
    }
}
