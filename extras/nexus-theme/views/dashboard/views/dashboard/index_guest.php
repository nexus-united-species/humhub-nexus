<?php

/**
 * Theme-Ueberschreibung von
 * humhub/modules/dashboard/views/dashboard/index_guest.php (Josh, 13.09.2026).
 *
 * Grund: Die HumHub-Standardansicht fuer Gaeste zeigte eine leere
 * "Keine oeffentlichen Inhalte gefunden!"-Box PLUS eine Seitenleiste mit
 * echten Mitgliederfotos/-namen ("Neue Mitglieder", "Aktivste Benutzer") --
 * fuer JEDEN anonymen Besucher sichtbar, auch Suchmaschinen. Josh' Kritik
 * 13.09.2026: das ist weder einladend noch datenschutzfreundlich richtig.
 *
 * Ersetzt durch eine eigene, einfache Willkommens-Seite: Logo, kurzer
 * Text inkl. der Bedeutung von N.E.X.U.S., und die zwei Knoepfe aus
 * accountTopMenu.php -- diesmal gross und zentriert. KEIN
 * DashboardContent::widget() und KEIN Sidebar::widget() mehr -- damit
 * verschwinden automatisch auch alle Mitglieder-Widgets von dieser Seite,
 * ohne sie einzeln suchen und ausblenden zu muessen.
 *
 * Die zugehoerige Anpassung an der Navigationsleiste (fuer Gaeste auf
 * GENAU dieser Seite ausgeblendet) steckt in
 * themes/NEXUS/views/humhub/widgets/views/topNavigation.php.
 *
 * Logo (Josh, 13.09.2026): NICHT das automatisch zugeschnittene
 * Marken-Logo `/assets/logo/600x80.png` (wirkte auf dem hellen
 * Hintergrund blass/transparent) -- stattdessen das Original-Artwork mit
 * eigenem dunklem Hintergrund, als eigene Datei unter
 * themes/NEXUS/resources/img/nexus-logo-voll.jpg (Quelle:
 * !Nexus_Wissen/10_Medien/Bilder/Marketing_Sonstiges/logo_slogan_gold.png,
 * fuer die Web-Nutzung auf 520px/JPEG verkleinert). Ueber
 * Yii::$app->view->theme->getBaseUrl() eingebunden statt fest verdrahtet,
 * damit die URL auch dann stimmt, wenn der Theme-Hash sich mal aendert.
 *
 * Footer (Josh, 13.09.2026): Diese Seite ist fuer einen Gast oft die
 * EINZIGE Seite der ganzen Plattform, die er zu sehen bekommt (siehe
 * oben) -- ohne eigenen Footer fehlten Impressum/Datenschutz und alle
 * Kanal-Links komplett. Inhalt 1:1 von der Haupt-Webseite
 * www.nexus-terminal.org uebernommen (deren <footer>), nur farblich ans
 * Kreis-Theme angepasst (Gold- statt Cyan-Akzent). Rechtlich gehoert diese
 * Seite zur selben Marke wie die Hauptseite, deshalb genau derselbe Text --
 * nicht selbst umformuliert.
 *
 * Dreisprachig (Josh, 25.09.2026): Die Seite richtet sich nach der Sprache des
 * Browsers (HumHub setzt fuer Gaeste Yii::$app->language danach). Fusszeile je
 * Sprache wie auf www.nexus-terminal.org/en/ bzw. /es/ -- dort gibt es eigene
 * Telegram-Kanaele, Amazon-Shops und uebersetzte Datenschutz-/Impressum-Seiten.
 * Markennamen (Threads, Bluesky) bleiben unuebersetzt, anders als auf der
 * spanischen Webseite ("hilos", "cielo azul").
 *
 * Karte der Gemeinschaften (Josh, 30.09.2026): Die Zeile mit dem Kommentar "nexus-karte" ist
 * nur ein Platzhalter. Das Modul nexus-karte ersetzt ihn durch die Karte
 * (Events::onViewAfterRender); ist das Modul aus, bleibt ein unsichtbarer Kommentar.
 * Gaeste sehen dort nur Name und Ort.
 *
 * Schaufenster (Josh, 03.10.2026): Der Platzhalter "nexus-schaufenster" wird vom Modul nexus-teilen
 * durch die neuesten drei Beitraege der oeffentlichen Seite "Aus der N.E.X.U.S.-Gemeinschaft"
 * ersetzt (anonym), unter der Karte (Josh, 03.10.2026). Gibt es keine, bleibt die Stelle leer.
 */

use humhub\helpers\Html;

$sprache = strtolower(explode('-', (string)Yii::$app->language)[0]);

$texte = [
    'de' => [
        'titel' => 'Willkommen bei N.E.X.U.S.',
        'intro' => 'Wir bauen gemeinsam an einer neuen Art des Zusammenlebens — menschlich, dezentral und auf echter Gemeinschaft aufgebaut. N.E.X.U.S. steht für die nächste Stufe unserer Entwicklung: nicht abwarten, sondern gemeinsam mitgestalten. Werde Teil davon.',
        'anmelden' => 'Anmelden',
        'registrieren' => 'Registrieren',
        'hilfe' => '❓ Probleme bei der Anmeldung oder Registrierung? Hier gibt es Hilfe',
        'hinweis' => 'Schon Mitglied? Dann links <strong>Anmelden</strong>. Neu hier? Dann rechts <strong>Registrieren</strong> — in wenigen Schritten bist du dabei.',
        'telegram' => ['Telegram Kanal', 'https://t.me/NexusProjectOfficial'],
        'amazon' => 'https://amzn.eu/d/eWMNOTG',
        'datenschutz' => ['Datenschutz', 'https://www.nexus-terminal.org/datenschutz.html'],
        'impressum' => ['Impressum', 'https://www.nexus-terminal.org/impressum.html'],
        'webseite' => ['Webseite', 'https://www.nexus-terminal.org/'],
        'kontakt' => 'Kontakt:',
        'steht' => 'N.E.X.U.S. steht für',
        'recht1' => 'Der Bauplan, die Volksversion und die Romantrilogie sind geistiges Eigentum von Josh Richman (© 2024–2026).',
        'recht2' => 'Lesen und Teilen ist ausdrücklich erwünscht. Die Verwendung als Grundlage für eigene Projekte ohne Genehmigung ist nicht gestattet.',
    ],
    'en' => [
        'titel' => 'Welcome to N.E.X.U.S.',
        'intro' => 'Together we are building a new way of living together — human, decentralised and grounded in real community. N.E.X.U.S. stands for the next stage of our development: not waiting, but shaping it together. Become part of it.',
        'anmelden' => 'Sign in',
        'registrieren' => 'Register',
        'hilfe' => '❓ Trouble signing in or registering? Get help here',
        'hinweis' => 'Already a member? Then <strong>Sign in</strong> on the left. New here? Then <strong>Register</strong> on the right — you\'ll be in within a few steps.',
        'telegram' => ['Telegram channel', 'https://t.me/nexus_canal_english'],
        'amazon' => 'https://www.amazon.com/-/de/dp/B0HDTZ1QC9',
        'datenschutz' => ['Data protection', 'https://www.nexus-terminal.org/en/datenschutz.html'],
        'impressum' => ['Imprint', 'https://www.nexus-terminal.org/en/impressum.html'],
        'webseite' => ['Website', 'https://www.nexus-terminal.org/en/'],
        'kontakt' => 'Contact:',
        'steht' => 'N.E.X.U.S. stands for',
        'recht1' => 'The blueprint, the popular version and the novel trilogy are the intellectual property of Josh Richman (© 2024–2026).',
        'recht2' => 'Reading and sharing is expressly encouraged. Using it as a basis for your own projects without permission is not permitted.',
    ],
    'es' => [
        'titel' => 'Bienvenido a N.E.X.U.S.',
        'intro' => 'Juntos construimos una nueva forma de convivencia — humana, descentralizada y basada en una comunidad real. N.E.X.U.S. representa la siguiente etapa de nuestra evolución: no esperar, sino darle forma juntos. Forma parte de ello.',
        'anmelden' => 'Iniciar sesión',
        'registrieren' => 'Registrarse',
        'hilfe' => '❓ ¿Problemas para entrar o registrarte? Aquí tienes ayuda',
        'hinweis' => '¿Ya eres miembro? Entonces <strong>Iniciar sesión</strong> a la izquierda. ¿Eres nuevo? Entonces <strong>Registrarse</strong> a la derecha — en pocos pasos estarás dentro.',
        'telegram' => ['Canal de Telegram', 'https://t.me/nexus_canal_espanol'],
        'amazon' => 'https://www.amazon.es/dp/B0HCP7J8T3',
        'datenschutz' => ['Política de Privacidad', 'https://www.nexus-terminal.org/es/datenschutz.html'],
        'impressum' => ['Aviso Legal', 'https://www.nexus-terminal.org/es/impressum.html'],
        'webseite' => ['Sitio web', 'https://www.nexus-terminal.org/es/'],
        'kontakt' => 'Contacto:',
        'steht' => 'N.E.X.U.S. significa',
        'recht1' => 'El Blueprint, la Versión Popular y la Trilogía de Novelas son propiedad intelectual de Josh Richman (© 2024–2026).',
        'recht2' => 'Se fomenta encarecidamente la lectura y el intercambio. No está permitido su uso como base para proyectos propios sin autorización.',
    ],
];
// Andere Sprachen (z. B. Franzoesisch) bekommen Englisch -- international verstaendlicher als Deutsch.
$t = $texte[$sprache] ?? $texte['en'];

// Vorschau beim Teilen (WhatsApp, Signal, Facebook ...) und Suchmaschinen (Josh, 03.10.2026: "am
// besten geht sie viral"). Vorher stand dort nur "Übersicht - N.E.X.U.S. Terminal" ohne Text.
$vorschau = [
    'de' => ['Willkommen bei N.E.X.U.S. – Gemeinschaft in deiner Nähe', 'Menschen, die gemeinsam eine neue Art des Zusammenlebens aufbauen: menschlich, dezentral, mit echten Gemeinschaften vor Ort. Sieh dir auf der Karte an, wo es schon Gemeinschaften gibt – und mach mit.'],
    'en' => ['Welcome to N.E.X.U.S. – community near you', 'People building a new way of living together: human, decentralised, with real local communities. See on the map where communities already exist – and join in.'],
    'es' => ['Bienvenido a N.E.X.U.S. – comunidad cerca de ti', 'Personas que construyen juntas una nueva forma de convivencia: humana, descentralizada, con comunidades reales en su lugar. Mira en el mapa dónde ya existen comunidades – y únete.'],
][$sprache] ?? null;
if ($vorschau === null) {
    $vorschau = ['Welcome to N.E.X.U.S. – community near you', 'People building a new way of living together: human, decentralised, with real local communities. See on the map where communities already exist – and join in.'];
}
$this->setPageTitle($vorschau[0]);
$this->meta->setTitle($vorschau[0]);
$this->meta->setDescription($vorschau[1]);
$link = 'color:#fff;margin:0 9px;';
$gold = 'color:#D4AF37;margin:0 9px;';

?>

<?= Html::beginContainer(); ?>
<div class="nexus-welcome" style="max-width:640px;margin:40px auto;padding:32px 24px;text-align:center;">
    <img src="<?= Html::encode(Yii::$app->view->theme->getBaseUrl()) ?>/resources/img/nexus-logo-voll.jpg"
         alt="N.E.X.U.S. -- Next Evolution Xperience United Species"
         style="max-width:260px;width:100%;height:auto;margin-bottom:20px;border-radius:8px;">

    <p style="letter-spacing:0.3px;color:#8a8f98;font-weight:600;text-transform:uppercase;font-size:clamp(10px, 3vw, 13px);margin-bottom:20px;white-space:nowrap;">
        Next&nbsp;·&nbsp;Evolution&nbsp;·&nbsp;Xperience&nbsp;·&nbsp;United&nbsp;·&nbsp;Species
    </p>

    <h1 style="font-size:26px;font-weight:700;margin-bottom:16px;">
        <?= Html::encode($t['titel']) ?>
    </h1>

    <p style="font-size:16px;line-height:1.6;color:#444;margin-bottom:32px;">
        <?= Html::encode($t['intro']) ?>
    </p>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:16px;">
        <a href="<?= Html::encode(\yii\helpers\Url::to(['/user/auth/external', 'authclient' => 'authentik', 'rememberMe' => 1])) ?>"
           class="btn btn-primary btn-enter" style="padding:12px 32px;font-size:16px;">
            <?= Html::encode($t['anmelden']) ?>
        </a>
        <a href="https://login.nexus-terminal.org/if/flow/nexus-registrierung/"
           class="btn btn-enter"
           style="padding:12px 32px;font-size:16px;background-color:#D4AF37;border-color:#D4AF37;color:#0A1628;font-weight:600;">
            <?= Html::encode($t['registrieren']) ?>
        </a>
    </div>

    <p style="font-size:13px;color:#999;">
        <?= $t['hinweis'] /* fester Text aus dieser Datei, enthaelt bewusst <strong> */ ?>
    </p>
    <?php /* Wer nicht ins Portal kommt, findet hier Hilfe (Josh, 07.10.2026 -- Hilfe & Support) */ ?>
    <p style="font-size:13px;margin-top:4px;">
        <a href="<?= Html::encode(\yii\helpers\Url::to(['/nexus-hilfe/hilfe/index'])) ?>" data-pjax-prevent="1" style="color:#b8941f;font-weight:600;"><?= Html::encode($t['hilfe']) ?></a>
    </p>
</div>

<!-- nexus-karte -->

<!-- nexus-schaufenster -->

<footer style="background:#080808;color:#999;text-align:center;padding:40px 20px;margin-top:24px;border-top:1px solid #222;">
    <div style="max-width:980px;margin:0 auto;line-height:2;">
        <a href="https://youtube.com/@project-n.e.x.u.s-official" target="_blank" rel="noopener" style="<?= $link ?>">YouTube</a> |
        <a href="<?= Html::encode($t['amazon']) ?>" target="_blank" rel="noopener" style="<?= $link ?>">Amazon</a> |
        <a href="<?= Html::encode($t['telegram'][1]) ?>" target="_blank" rel="noopener" style="<?= $link ?>"><?= Html::encode($t['telegram'][0]) ?></a>
        <a href="https://www.facebook.com/NexusTerminal" target="_blank" rel="noopener" style="<?= $link ?>">Facebook</a>
        <a href="https://www.instagram.com/n.e.x.u.s._navigator" target="_blank" rel="noopener" style="<?= $link ?>">Instagram</a>
        <a href="https://www.threads.com/@n.e.x.u.s._navigator" target="_blank" rel="noopener" style="<?= $link ?>">Threads</a>
        <a href="https://www.tiktok.com/@n.e.x.u.s._navigator" target="_blank" rel="noopener" style="<?= $link ?>">TikTok</a>
        <a href="https://x.com/nexusxnavigator" target="_blank" rel="noopener" style="<?= $link ?>">X</a>
        <a href="https://bsky.app/profile/nexus-navigator.bsky.social" target="_blank" rel="noopener" style="<?= $link ?>">Bluesky</a>
        <br>
        <a href="<?= Html::encode($t['datenschutz'][1]) ?>" target="_blank" rel="noopener" style="<?= $gold ?>"><?= Html::encode($t['datenschutz'][0]) ?></a> |
        <a href="<?= Html::encode($t['impressum'][1]) ?>" target="_blank" rel="noopener" style="<?= $gold ?>"><?= Html::encode($t['impressum'][0]) ?></a> |
        <a href="<?= Html::encode($t['webseite'][1]) ?>" target="_blank" rel="noopener" style="<?= $gold ?>"><?= Html::encode($t['webseite'][0]) ?></a> |
        <a href="https://start.nexus-terminal.org/" target="_blank" rel="noopener" style="<?= $gold ?>">Intern</a>
    </div>

    <p style="margin-top:16px;">
        <?= Html::encode($t['kontakt']) ?> <a href="mailto:nexus.blueprint@proton.me" style="color:#D4AF37;">nexus.blueprint@proton.me</a>
    </p>

    <div style="max-width:800px;margin:24px auto 0 auto;padding:20px 20px 0 20px;border-top:1px solid #222;font-size:0.8rem;color:#777;line-height:1.6;">
        <p style="margin-bottom:8px;"><?= Html::encode($t['steht']) ?> Next&nbsp;·&nbsp;Evolution&nbsp;·&nbsp;Xperience&nbsp;·&nbsp;United&nbsp;·&nbsp;Species.</p>
        <p style="margin:0;">
            <?= Html::encode($t['recht1']) ?><br>
            <?= Html::encode($t['recht2']) ?>
        </p>
    </div>
</footer>
<?= Html::endContainer(); ?>
