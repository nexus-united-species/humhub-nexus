<?php

namespace nexus\modules\wissen\services;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\wissen\Module;

/**
 * Eine Wissenssammlung mit ihren Einstellungen aus common.php.
 *
 * Schluessel (Texte wahlweise als String oder als ['de' => ..., 'en' => ..., 'es' => ...]):
 *   kreis          Space-ID (Pflicht)
 *   name           Name im Menue und als Ueberschrift            (Vorgabe: "Wissen")
 *   beschreibung   Zeile unter der Ueberschrift
 *   symbol         Font-Awesome-Name fuer Menue und Ueberschrift  (Vorgabe: "book")
 *   emoji          vor Nachrichten und Kreis-Beitraegen          (Vorgabe: "📖")
 *   suchbeispiel   Platzhalter im Suchfeld
 *   hinweis        Kasten unter jedem Artikel (z. B. "ersetzt keinen aerztlichen Rat"), sonst keiner
 *   themen         [Code => Name] oder [Code => ['de' => ..., 'symbol' => 'leaf']] -- Reihenfolge und
 *                  Namen der Themen. Themen aus dem Import ohne Eintrag erscheinen trotzdem, mit
 *                  einem aus dem Code gebildeten Namen ("02_Heilpflanzen" -> "Heilpflanzen").
 *   importPfad     Ordner, aus dem "php yii wissen/import <kennung>" liest
 *   importStatus   nur Manifest-Eintraege mit diesem "status" uebernehmen (sonst alle)
 *   kiBeschreibung was die Sammlung ist -- fuer die KI-Schlagworte ("ein Kochbuch mit ...")
 *   kiAnweisung    komplette eigene Anweisung fuer die KI-Schlagworte (ersetzt die Vorgabe)
 *   kreisAdmins    true: auch die Admins des Kreises duerfen bearbeiten (Vorgabe: nur Systemadmins)
 *   menuePosition  sortOrder im Kreis-Menue (Vorgabe 105, direkt unter "Stream")
 */
class Sammlung
{
    private const VORGABE_SYMBOL = 'book';
    private const VORGABE_EMOJI = '📖';
    private const VORGABE_MENUE = 105;

    public function __construct(
        public readonly string $kennung,
        private readonly array $e,
        private readonly Module $modul,
    ) {
    }

    /** Kennungen landen in Adressen und Einstellungs-Namen: nur Kleinbuchstaben, Ziffern, Bindestrich. */
    public static function gueltigeKennung(string $kennung): bool
    {
        return (bool)preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $kennung);
    }

    public function kreisId(): int
    {
        return (int)($this->e['kreis'] ?? 0);
    }

    public function kreis(): ?Space
    {
        return $this->kreisId() > 0 ? Space::findOne(['id' => $this->kreisId()]) : null;
    }

    /** @param string|null $sprache z. B. 'de' fuer die (deutschen) Benachrichtigungen */
    public function name(?string $sprache = null): string
    {
        return Texte::waehle($this->e['name'] ?? null, $sprache) ?: Texte::t('wissen');
    }

    public function beschreibung(): string
    {
        return Texte::waehle($this->e['beschreibung'] ?? null);
    }

    public function suchbeispiel(): string
    {
        return Texte::waehle($this->e['suchbeispiel'] ?? null) ?: Texte::t('suche_platzhalter');
    }

    public function hinweis(): string
    {
        return Texte::waehle($this->e['hinweis'] ?? null);
    }

    public function symbol(): string
    {
        $s = (string)($this->e['symbol'] ?? '');
        return preg_match('/^[a-z0-9-]+$/', $s) ? $s : self::VORGABE_SYMBOL;
    }

    public function emoji(): string
    {
        return (string)($this->e['emoji'] ?? self::VORGABE_EMOJI);
    }

    public function menuePosition(): int
    {
        return (int)($this->e['menuePosition'] ?? self::VORGABE_MENUE);
    }

    public function importPfad(): string
    {
        return (string)($this->e['importPfad'] ?? '');
    }

    public function importStatus(): ?string
    {
        $s = $this->e['importStatus'] ?? null;
        return $s === null || $s === '' ? null : (string)$s;
    }

    public function kiBeschreibung(): string
    {
        return (string)($this->e['kiBeschreibung'] ?? '');
    }

    public function kiAnweisung(): string
    {
        return (string)($this->e['kiAnweisung'] ?? '');
    }

    /**
     * Themen in der eingestellten Reihenfolge, danach die uebrigen aus dem Bestand (nach Code sortiert).
     * @param string[] $imBestand Themen-Codes, die Artikel tatsaechlich haben
     * @return array<string, array{name: string, symbol: string}>
     */
    public function themen(array $imBestand = []): array
    {
        $liste = [];
        foreach ((array)($this->e['themen'] ?? []) as $code => $wert) {
            $liste[(string)$code] = [
                'name' => Texte::waehle(is_array($wert) ? array_diff_key($wert, ['symbol' => 1]) : $wert) ?: self::nameAusCode((string)$code),
                'symbol' => is_array($wert) && preg_match('/^[a-z0-9-]+$/', (string)($wert['symbol'] ?? '')) ? $wert['symbol'] : self::VORGABE_SYMBOL,
            ];
        }
        sort($imBestand, SORT_NATURAL);  // "01_..." vor "02_..." -- Ordnernamen bestimmen die Reihenfolge
        foreach ($imBestand as $code) {
            if ($code !== '' && !isset($liste[$code])) {
                $liste[$code] = ['name' => self::nameAusCode($code), 'symbol' => self::VORGABE_SYMBOL];
            }
        }
        return $liste;
    }

    public function themaName(string $code): string
    {
        return $this->themen([$code])[$code]['name'] ?? $code;
    }

    /** "02_Heilpflanzen" -> "Heilpflanzen", "erste-hilfe" -> "Erste hilfe" */
    public static function nameAusCode(string $code): string
    {
        $name = trim(str_replace(['_', '-'], ' ', (string)preg_replace('/^\d+[_\-\s]*/', '', $code)));
        return $name === '' ? $code : mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    // ── Rechte ─────────────────────────────────────────────────────────────

    public function freigegeben(): bool
    {
        return (bool)$this->modul->settings->get('freigegeben.' . $this->kennung, false);
    }

    public function setzeFreigabe(bool $an): void
    {
        $this->modul->settings->set('freigegeben.' . $this->kennung, $an ? '1' : '0');
    }

    public function darfBearbeiten(?User $mensch): bool
    {
        if ($mensch === null) {
            return false;
        }
        if ($mensch->isSystemAdmin()) {
            return true;
        }
        $kreis = $this->kreis();
        return !empty($this->e['kreisAdmins']) && $kreis !== null && $kreis->isAdmin($mensch->id);
    }

    public function darfLesen(?User $mensch): bool
    {
        return $mensch !== null && ($this->freigegeben() || $this->darfBearbeiten($mensch));
    }

    public function darfVorschlagen(?User $mensch): bool
    {
        $kreis = $this->kreis();
        return $mensch !== null && $kreis !== null && ($kreis->isMember($mensch->id) || $this->darfBearbeiten($mensch));
    }

    /** @return User[] wer Vorschlaege und Erinnerungen bekommt */
    public function bearbeitende(): array
    {
        $liste = [];
        foreach (User::find()->innerJoin('group_user gu', 'gu.user_id = user.id')
            ->innerJoin('`group` g', 'g.id = gu.group_id AND g.is_admin_group = 1')
            ->where(['user.status' => User::STATUS_ENABLED])->all() as $u) {
            $liste[$u->id] = $u;
        }
        $kreis = $this->kreis();
        if (!empty($this->e['kreisAdmins']) && $kreis !== null) {
            foreach (User::find()->innerJoin('space_membership m', 'm.user_id = user.id')
                ->where(['m.space_id' => $kreis->id, 'm.group_id' => Space::USERGROUP_ADMIN, 'user.status' => User::STATUS_ENABLED])->all() as $u) {
                $liste[$u->id] = $u;
            }
        }
        return array_values($liste);
    }

    // ── Adressen ───────────────────────────────────────────────────────────

    public function url(string $seite = 'index', array $parameter = [], bool $absolut = false): string
    {
        $kreis = $this->kreis();
        if ($kreis === null) {
            return '';
        }
        $pfad = $kreis->createUrl('/nexus-wissen/wissen/' . $seite, ['s' => $this->kennung] + $parameter);
        if (!$absolut || preg_match('#^https?://#', $pfad)) {
            return $pfad;
        }
        // Volle Adresse auch ohne Browser-Anfrage (Cron, Konsole): aus der HumHub-Einstellung baseUrl
        $pfad = (string)preg_replace('#^.*?(/(?:s|u)/)#', '$1', $pfad);
        return rtrim((string)\Yii::$app->settings->get('baseUrl'), '/') . '/' . ltrim($pfad, '/');
    }
}
