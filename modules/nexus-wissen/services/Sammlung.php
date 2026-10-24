<?php

namespace nexus\modules\wissen\services;

use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use nexus\modules\wissen\models\Thema;
use nexus\modules\wissen\Module;

/**
 * Eine Wissenssammlung: aus den Einstellungen (common.php) oder im Portal angelegt
 * (Tabelle nexus_wissen_sammlung, siehe istEigen()).
 *
 * Schluessel (Texte wahlweise als String oder als ['de' => ..., 'en' => ..., 'es' => ...]):
 *   kreis          Space-ID (Pflicht)
 *   name           Name im Menue und als Ueberschrift            (Vorgabe: "Wissen")
 *   beschreibung   Zeile unter der Ueberschrift
 *   symbol         Font-Awesome-Name fuer Menue und Ueberschrift  (Vorgabe: "book")
 *   emoji          vor Nachrichten und Kreis-Beitraegen          (Vorgabe: "📖")
 *   suchbeispiel   Platzhalter im Suchfeld
 *   hinweis        Kasten unter jedem Artikel (z. B. "ersetzt keinen aerztlichen Rat"), sonst keiner
 *   themen         [Code => Name] oder [Code => ['de' => ..., 'symbol' => 'leaf']] -- nur die
 *                  Startwerte: beim ersten Aufruf in die Datenbank uebernommen, danach pflegt die
 *                  Redaktion die Kategorien im Portal (services/Themen.php).
 *   importPfad     Ordner, aus dem "php yii wissen/import <kennung>" liest
 *   importStatus   nur Manifest-Eintraege mit diesem "status" uebernehmen (sonst alle)
 *   kiBeschreibung was die Sammlung ist -- fuer die KI-Schlagworte ("ein Kochbuch mit ...")
 *   kiAnweisung    komplette eigene Anweisung fuer die KI-Schlagworte (ersetzt die Vorgabe)
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
        private readonly bool $eigen = false,
    ) {
    }

    /** true: im Portal angelegt -- Name, Beschreibung, Symbol und Hinweis sind dort aenderbar. */
    public function istEigen(): bool
    {
        return $this->eigen;
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
     * Die Kategorien in ihrer Reihenfolge (aus der Datenbank, siehe Themen).
     * @param string[] $imBestand nicht mehr noetig -- Themen der Artikel kommen von selbst dazu
     * @return array<string, array{name: string, symbol: string, id: int}>
     */
    public function themen(array $imBestand = []): array
    {
        $liste = [];
        foreach (Themen::alle($this, $this->e) as $t) {
            $liste[$t->code] = ['name' => $t->anzeigeName() ?: self::nameAusCode($t->code), 'symbol' => $t->symbol ?: self::VORGABE_SYMBOL, 'id' => (int)$t->id];
        }
        return $liste;
    }

    /** @return Thema[] */
    public function themenEintraege(): array
    {
        return Themen::alle($this, $this->e);
    }

    public function verschiebeThema(int $id, int $richtung): void
    {
        Themen::verschieben($this, $this->e, $id, $richtung);
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

    /**
     * Redaktion einer Sammlung: Systemadmins und Besitzer/Admins ihres Kreises (Josh, 18.10.2026 --
     * vorher nur Systemadmins), seit 24.10.2026 auch die Moderatoren des Kreises: Wer Wissen pflegt, soll
     * dafuer nicht gleich den ganzen Kreis verwalten muessen. Sie schreiben Artikel, pflegen Kategorien und
     * geben frei. Einen Wissensbereich ANLEGEN bleibt Besitz/Admins (EinrichtenController).
     */
    public function darfBearbeiten(?User $mensch): bool
    {
        if ($mensch === null) {
            return false;
        }
        if ($mensch->isSystemAdmin()) {
            return true;
        }
        $kreis = $this->kreis();
        if ($kreis === null) {
            return false;
        }
        // isAdmin() immer mit der ID -- ohne sie fragt HumHub nach dem gerade angemeldeten Menschen
        if ($kreis->isAdmin((int)$mensch->id)) {
            return true;
        }
        $mitgliedschaft = $kreis->getMembership((int)$mensch->id);
        return $mitgliedschaft !== null && $mitgliedschaft->group_id === Space::USERGROUP_MODERATOR
            && (int)$mitgliedschaft->status === Membership::STATUS_MEMBER;
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
        if ($kreis !== null) {
            foreach (User::find()->innerJoin('space_membership m', 'm.user_id = user.id')
                ->where(['m.space_id' => $kreis->id, 'm.group_id' => [Space::USERGROUP_ADMIN, Space::USERGROUP_MODERATOR],
                    'm.status' => Membership::STATUS_MEMBER, 'user.status' => User::STATUS_ENABLED])->all() as $u) {
                $liste[$u->id] = $u;
            }
            $besitzer = User::findOne(['id' => $kreis->created_by, 'status' => User::STATUS_ENABLED]);
            if ($besitzer !== null) {
                $liste[$besitzer->id] = $besitzer;
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
        // "neu" -> Seite der Leseansicht, "redaktion/neu" -> Seite der Redaktion
        $route = str_contains($seite, '/') ? '/nexus-wissen/' . $seite : '/nexus-wissen/wissen/' . $seite;
        $pfad = $kreis->createUrl($route, ['s' => $this->kennung] + $parameter);
        if (!$absolut || preg_match('#^https?://#', $pfad)) {
            return $pfad;
        }
        // Volle Adresse auch ohne Browser-Anfrage (Cron, Konsole): aus der HumHub-Einstellung baseUrl
        $pfad = (string)preg_replace('#^.*?(/(?:s|u)/)#', '$1', $pfad);
        return rtrim((string)\Yii::$app->settings->get('baseUrl'), '/') . '/' . ltrim($pfad, '/');
    }
}
