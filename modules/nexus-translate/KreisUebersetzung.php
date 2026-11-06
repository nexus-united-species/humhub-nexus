<?php

namespace humhub\modules\nexusTranslate;

use humhub\helpers\Html;
use humhub\modules\nexusTranslate\models\SpaceTranslation;
use humhub\modules\space\models\Space;
use Throwable;
use Yii;

/**
 * Kreis-Namen und -Kurzbeschreibungen in der Sprache des Lesers (Josh, 25.09.2026).
 *
 * HumHub kennt fuer Kreise nur EINEN Namen. Statt drei Kreise je Sprache
 * (= Parallelwelten) wird jede Fassung einmal automatisch uebersetzt,
 * gespeichert und erst beim Ausliefern einer Seite ausgetauscht
 * (Events::onResponseAfterPrepare). Den HumHub-Kern fassen wir nicht an.
 *
 * Seit 06.11.2026 auch nach Deutsch: Kreise wie "Decretos del Alma" (Spanisch)
 * oder "Abundomy Movement" (Englisch) sind nicht auf Deutsch angelegt; deutsch
 * eingestellte Leser sahen sie bis dahin im Original. Die deutsche Fassung
 * entsteht nur, wenn der Kreis eindeutig in einer anderen Sprache geschrieben ist
 * (deutsche Originale bleiben unangetastet) und sich vom Original unterscheidet.
 *
 * Bewusst NICHT ausgetauscht wird auf Verwaltungs-/Bearbeitungsseiten: dort
 * stehen Name und Beschreibung in Formularfeldern, und wer dort speichert,
 * wuerde sonst die Uebersetzung als neuen deutschen Namen eintragen.
 */
class KreisUebersetzung
{
    /** Zielsprachen. "de" nur fuer Kreise, die eindeutig nicht deutsch angelegt sind (eindeutigFremd). */
    public const SPRACHEN = ['de' => 'German', 'en' => 'English', 'es' => 'Spanish', 'ru' => 'Russian', 'fr' => 'French'];

    public const FELDER = ['name', 'description'];

    private const CACHE_PREFIX = 'nexus-kreis-uebersetzung-';

    /**
     * Feste Begriffe, damit "Kreis" ueberall gleich heisst wie auf der Webseite
     * und in den Nextcloud-Ordnern (Circle / Círculo) und Eigennamen bleiben.
     */
    public const BEGRIFFE = [
        'de' => 'The text may already be German -- then return it exactly unchanged. Translate "Circle" as "Kreis". Keep these unchanged: '
            . 'N.E.X.U.S., AETHER, OneApp, DAO, VITA, TERRA, AURA, Mesh, and names of movements or projects. Keep dashes and numbering exactly as in the original.',
        'en' => 'Always translate "Kreis" as "Circle" and "Kreise" as "Circles", "Bauplan" as "Blueprint", "Unterstützer" as "Supporters". Keep these unchanged: '
            . 'N.E.X.U.S., AETHER, OneApp, DAO, VITA, TERRA, AURA, Mesh. Keep dashes and numbering exactly as in the original.',
        'es' => 'Always translate "Kreis" as "Círculo" and "Kreise" as "Círculos", "Bauplan" as "Plano" (never "Plano de construcción"), "Unterstützer" as "Colaboradores". Keep these unchanged: '
            . 'N.E.X.U.S., AETHER, OneApp, DAO, VITA, TERRA, AURA, Mesh. Keep dashes and numbering exactly as in the original.',
        'ru' => 'Always translate "Kreis" as "Круг" and "Kreise" as "Круги", "Bauplan" as "Генеральный план", "Unterstützer" as "Сторонники". Keep these unchanged: '
            . 'N.E.X.U.S., AETHER, OneApp, DAO, VITA, TERRA, AURA, Mesh. Keep dashes and numbering exactly as in the original.',
        'fr' => 'Always translate "Kreis" as "Cercle" and "Kreise" as "Cercles", "Bauplan" as "Plan directeur", "Unterstützer" as "Contributeurs". Keep these unchanged: '
            . 'N.E.X.U.S., AETHER, OneApp, DAO, VITA, TERRA, AURA, Mesh. Keep dashes and numbering exactly as in the original.',
    ];

    /** Kurzcode der Sprache des angemeldeten Menschen (de, en, es, ru, fr) oder null. */
    public static function leserSprache(): ?string
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }
        $code = strtolower(explode('-', (string)Yii::$app->language)[0]);
        return isset(self::SPRACHEN[$code]) ? $code : null;
    }

    /** Uebersetzt Name und Beschreibung eines Kreises, soweit neu oder geaendert. */
    public static function aktualisiere(Space $space, bool $erzwingen = false): array
    {
        $ergebnis = [];
        /** @var Module $module */
        $module = Yii::$app->getModule('nexus-translate');
        $nachDeutsch = self::eindeutigFremd($space);
        foreach (self::FELDER as $feld) {
            $original = trim((string)$space->$feld);
            if ($original === '') {
                continue;
            }
            $hash = md5($original);
            foreach (self::SPRACHEN as $code => $sprachname) {
                if ($code === 'de' && !$nachDeutsch) {
                    continue;
                }
                $eintrag = SpaceTranslation::findOne(['space_id' => $space->id, 'language' => $code, 'feld' => $feld]);
                if (!$erzwingen && $eintrag !== null && $eintrag->quelle_hash === $hash) {
                    continue;
                }
                $text = $module->uebersetzen($original, $code, self::BEGRIFFE[$code]);
                // Keine Zeichen, die im ausgelieferten HTML/JSON stoeren koennten.
                $text = trim(str_replace(['"', '\\', "\n", "\r"], ['', '', ' ', ' '], $text));
                if ($text === '' || ($code === 'de' && $text === $original)) {
                    continue;
                }
                $eintrag ??= new SpaceTranslation(['space_id' => $space->id, 'language' => $code, 'feld' => $feld]);
                $eintrag->quelle_hash = $hash;
                $eintrag->text = $text;
                $eintrag->geprueft = false;
                $eintrag->updated_at = date('Y-m-d H:i:s');
                $eintrag->save(false);
                $ergebnis[] = "$feld/$code";
            }
        }
        self::cacheLeeren();
        return $ergebnis;
    }

    /**
     * Ist der Kreis eindeutig NICHT auf Deutsch angelegt? Im Zweifel nein: Lieber einen
     * fremdsprachigen Kreis im Original zeigen, als einen deutschen Namen umschreiben
     * zu lassen. Kurze deutsche Kreise ("Digitale Selbstbestimmung") erkennt
     * SprachErkennung nicht sicher (null) -- die bekommen deshalb keine deutsche Fassung.
     */
    private static function eindeutigFremd(Space $space): bool
    {
        $sprache = SprachErkennung::erkenne(trim($space->name . '. ' . $space->description . ' ' . $space->about));
        return $sprache !== null && $sprache !== 'de';
    }

    public static function cacheLeeren(): void
    {
        foreach (array_keys(self::SPRACHEN) as $code) {
            Yii::$app->cache->delete(self::CACHE_PREFIX . $code);
        }
    }

    /**
     * Ersetzungstabelle Original -> Uebersetzung fuer eine Sprache, in beiden
     * Schreibweisen (roh und HTML-kodiert, z. B. "&" als "&amp;").
     */
    public static function tabelle(string $code): array
    {
        return Yii::$app->cache->getOrSet(self::CACHE_PREFIX . $code, function () use ($code) {
            $tabelle = [];
            $uebersetzt = SpaceTranslation::find()->where(['language' => $code])->indexBy(fn($r) => $r->space_id . '/' . $r->feld)->all();
            foreach (Space::find()->all() as $space) {
                foreach (self::FELDER as $feld) {
                    $original = trim((string)$space->$feld);
                    $eintrag = $uebersetzt[$space->id . '/' . $feld] ?? null;
                    // Nur gueltige Uebersetzungen: passt die Pruefsumme nicht (Original
                    // inzwischen geaendert), lieber das Original zeigen als etwas Veraltetes.
                    if ($original === '' || mb_strlen($original) < 4 || $eintrag === null || $eintrag->quelle_hash !== md5($original)) {
                        continue;
                    }
                    $tabelle[$original] = $eintrag->text;
                    $tabelle[Html::encode($original)] = Html::encode($eintrag->text);
                }
            }
            // Termin-Titel laufen ueber dieselbe Liste (Stream, "Naechste Termine", Kalender-JSON).
            // Ebenso die Wiki-Seiten in der linken Kreis-Navigation (seit 28.09.2026).
            return $tabelle + TerminUebersetzung::tabelle($code) + WikiVorschau::menueTitelTabelle($code);
        }, 3600);
    }

    /** Ist die aktuelle Seite eine, auf der NICHT ersetzt werden darf? */
    public static function istBearbeitungsseite(): bool
    {
        $route = Yii::$app->controller?->getRoute() ?? '';
        return str_starts_with($route, 'admin/')
            || str_contains($route, '/manage/')
            || str_starts_with($route, 'space/create')
            || str_starts_with($route, 'nexus-translate/');
    }

    public static function ersetze(string $inhalt, string $code): string
    {
        $tabelle = self::tabelle($code);
        return $tabelle ? strtr($inhalt, $tabelle) : $inhalt;
    }

    public static function sicherAktualisieren(Space $space): void
    {
        try {
            self::aktualisiere($space);
        } catch (Throwable $e) {
            // Eine fehlgeschlagene Uebersetzung darf das Speichern eines Kreises nie verhindern.
            Yii::error('Kreis-Uebersetzung fehlgeschlagen: ' . $e->getMessage(), 'nexus-translate');
        }
    }
}
