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
 * Bewusst NICHT ausgetauscht wird auf Verwaltungs-/Bearbeitungsseiten: dort
 * stehen Name und Beschreibung in Formularfeldern, und wer dort speichert,
 * wuerde sonst die Uebersetzung als neuen deutschen Namen eintragen.
 */
class KreisUebersetzung
{
    /** Zielsprachen neben dem deutschen Original. */
    public const SPRACHEN = ['en' => 'English', 'es' => 'Spanish'];

    public const FELDER = ['name', 'description'];

    private const CACHE_PREFIX = 'nexus-kreis-uebersetzung-';

    /**
     * Feste Begriffe, damit "Kreis" ueberall gleich heisst wie auf der Webseite
     * und in den Nextcloud-Ordnern (Circle / Círculo) und Eigennamen bleiben.
     */
    public const BEGRIFFE = [
        'en' => 'Always translate "Kreis" as "Circle" and "Kreise" as "Circles", "Bauplan" as "Blueprint", "Unterstützer" as "Supporters". Keep these unchanged: '
            . 'N.E.X.U.S., AETHER, OneApp, DAO, VITA, TERRA, AURA, Mesh. Keep dashes and numbering exactly as in the original.',
        'es' => 'Always translate "Kreis" as "Círculo" and "Kreise" as "Círculos", "Bauplan" as "Plano" (never "Plano de construcción"), "Unterstützer" as "Colaboradores". Keep these unchanged: '
            . 'N.E.X.U.S., AETHER, OneApp, DAO, VITA, TERRA, AURA, Mesh. Keep dashes and numbering exactly as in the original.',
    ];

    /** Kurzcode der Sprache des angemeldeten Menschen (de, en, es) oder null. */
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
        foreach (self::FELDER as $feld) {
            $original = trim((string)$space->$feld);
            if ($original === '') {
                continue;
            }
            $hash = md5($original);
            foreach (self::SPRACHEN as $code => $sprachname) {
                $eintrag = SpaceTranslation::findOne(['space_id' => $space->id, 'language' => $code, 'feld' => $feld]);
                if (!$erzwingen && $eintrag !== null && $eintrag->quelle_hash === $hash) {
                    continue;
                }
                $text = $module->uebersetzen($original, $code, self::BEGRIFFE[$code]);
                // Keine Zeichen, die im ausgelieferten HTML/JSON stoeren koennten.
                $text = trim(str_replace(['"', '\\', "\n", "\r"], ['', '', ' ', ' '], $text));
                if ($text === '') {
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
