<?php

/**
 * Theme-Ueberschreibung von
 * humhub/modules/user/widgets/views/accountTopMenu.php (Josh, 13.09.2026).
 *
 * Grund: Fuer Gaeste zeigte HumHub nur EINEN Knopf "Einloggen | Registrieren",
 * der auf ein Modal mit einem einzigen, unklar beschrifteten Knopf
 * ("N.E.X.U.S. Anmeldung") fuehrte -- ein neues Mitglied (
 * 13.09.2026: "Kam irgendwie nicht weiter") fand darueber nicht zur
 * Registrierung. Nach Josh' Vorbild (Facebook: getrennte, klar beschriftete
 * Knoepfe) fuehren jetzt zwei eigene Knoepfe DIREKT zu Authentik, ohne
 * Umweg ueber das HumHub-eigene Anmelde-Modal:
 *  - "Anmelden"     -> /user/auth/external?authclient=authentik (startet
 *                      den bestehenden OpenID-Connect-Login direkt)
 *  - "Registrieren" -> Authentik-Registrierungs-Flow "nexus-registrierung"
 *                      direkt (derselbe Flow, der bisher nur ueber den
 *                      "Registrieren."-Link auf der Authentik-Login-Seite
 *                      erreichbar war)
 *
 * Der eingeloggte Zustand (Profil-Dropdown) bleibt unveraendert -- nur der
 * Gast-Zweig wurde ersetzt.
 *
 * Direkte Links "Mein Profil" / "Einstellungen" (Josh, 14.09.2026): Beide
 * stecken schon lange im Profil-Dropdown (Klick aufs eigene Bild oben
 * rechts), aber "die Leute finden das nicht". Jetzt zusaetzlich als
 * eigene, immer sichtbare Links links neben dem Profilbild -- in der
 * Luecke zwischen den Glocken-/Umschlag-Symbolen und dem Profilbild, die
 * vorher leer war. Dropdown bleibt zusaetzlich bestehen (nichts entfernt,
 * nur ergaenzt). Dieselben Ziel-URLs wie die Original-Eintraege im
 * HumHub-Kern (humhub/modules/user/widgets/AccountTopMenu.php):
 * '/user/profile/home' und '/user/account/edit'.
 */

use humhub\components\View;
use humhub\helpers\Html;
use humhub\modules\ui\menu\MenuEntry;
use humhub\modules\ui\menu\widgets\DropdownMenu;
use humhub\modules\user\widgets\Image;
use humhub\widgets\FooterMenu;

/* @var $this View */
/* @var $menu DropdownMenu */
/* @var $entries MenuEntry[] */
/* @var $options [] */

/** @var \humhub\modules\user\models\User $userModel */

$userModel = Yii::$app->user->identity;

// Feste Beschriftungen dieser Vorlage in der Sprache des Besuchers (Josh, 25.09.2026:
// ein Spanier soll nicht "Anmelden"/"Einstellungen" lesen muessen).
$nexusSprache = strtolower(explode('-', (string)Yii::$app->language)[0]);
$nexusTexte = [
    'de' => ['anmelden' => 'Anmelden', 'registrieren' => 'Registrieren', 'profil' => 'Mein Profil', 'einstellungen' => 'Einstellungen', 'sprache' => 'Sprache', 'hilfe' => 'Hilfe'],
    'en' => ['anmelden' => 'Sign in', 'registrieren' => 'Register', 'profil' => 'My profile', 'einstellungen' => 'Settings', 'sprache' => 'Language', 'hilfe' => 'Help'],
    'es' => ['anmelden' => 'Iniciar sesión', 'registrieren' => 'Registrarse', 'profil' => 'Mi perfil', 'einstellungen' => 'Ajustes', 'sprache' => 'Idioma', 'hilfe' => 'Ayuda'],
];
$nexusText = $nexusTexte[$nexusSprache] ?? $nexusTexte['en']; // andere Sprachen: Englisch, wie index_guest.php

?>

<?php if (Yii::$app->user->isGuest): ?>
    <?php /* rememberMe=1 (Rueckmeldung eines Mitglieds, 07.10.2026: "muss mich jedes Mal neu einloggen"): Ohne den Schalter
             meldet HumHub SSO-Anmeldungen nur fuer die Browsersitzung an -- nach ~23 Min. Pause oder
             Schliessen der App war man raus. Mit ihm 30 Tage angemeldet (wie "Angemeldet bleiben"). */ ?>
    <div class="nexus-auth-buttons" style="display:flex;gap:8px;align-items:center;">
        <a href="<?= Html::encode(\yii\helpers\Url::to(['/user/auth/external', 'authclient' => 'authentik', 'rememberMe' => 1])) ?>"
           class="btn btn-primary btn-enter">
            <?= Html::encode($nexusText['anmelden']) ?>
        </a>
        <a href="https://login.nexus-terminal.org/if/flow/nexus-registrierung/"
           class="btn btn-enter"
           style="background-color:#D4AF37;border-color:#D4AF37;color:#0A1628;font-weight:600;">
            <?= Html::encode($nexusText['registrieren']) ?>
        </a>
    </div>
<?php else: ?>
    <style>
        /* Wichtig: FLOAT statt Flex, weil die benachbarte <ul class="nav">
           per Kern-CSS (".topbar ul.nav{float:left}") selbst schwimmt --
           ein normaler Block-Container davor wuerde sonst in eine eigene
           Zeile darueber rutschen statt daneben zu stehen. Genau das war
           der erste, fehlgeschlagene Versuch (Josh' Screenshot 14.09.2026:
           "Mein Profil"/"Einstellungen" standen ueber statt neben dem
           Profilbild). Senkrechtes Padding an ".topbar ul.nav>li>a" (15px
           oben/unten) nachgebildet, damit die Links auf derselben Hoehe
           sitzen wie Profilbild und Glocken-Symbole.
           Auf sehr schmalen Handy-Bildschirmen etwas enger, damit nichts
           umbricht ("muss auch mobil funktionieren"). */
        .nexus-quick-links { float: left; padding: 15px 0; margin-right: 4px; white-space: nowrap; }
        .nexus-quick-links a { color: #fff; font-size: 13px; white-space: nowrap; margin-right: 14px; }
        /* Platz in der Kopfzeile (Josh, 07.10.2026: mit dem neuen Hilfe-Link rutschten die Sprachknoepfe
           am PC unter Glocke/Umschlag, auf dem Handy legte sich das Profilbild ueber die Menuezeile).
           Die Glocken-Symbole stehen fest in der Mitte -- der rechte Block darf nicht breiter als die
           halbe Zeile werden. Deshalb: Name neben dem Profilbild nie (steht im eigenen Profil),
           "Mein Profil"/"Einstellungen" erst ab 1200px (sonst nur im Profil-Menue), unter 768px gar
           keine Direkt-Links (dort alles ueber das Profilbild, inkl. Hilfe). */
        #topbar-first .nav>.account .dropdown-toggle .user-title { display: none !important; }
        @media (max-width: 1199px) {
            .nexus-quick-links .nexus-ql-breit { display: none; }
        }
        @media (max-width: 767px) {
            .nexus-quick-links { display: none; }
        }
        /* Sprachumschalter DE / EN / ES (Josh, 25.09.2026): neue Mitglieder fanden die
           Spracheinstellung nicht. Eine Zeile Knoepfe, die aktuelle Sprache in Gold. */
        /* Abstand und Trennstrich zu "Mein Profil" (Josh, 07.10.2026): ES stand direkt daneben --
           Ein Mitglied traf beim Klick auf "Mein Profil" offenbar ES und hatte das Portal auf Spanisch. */
        .nexus-sprache { display: inline; margin: 0 12px 0 0; padding-right: 10px; border-right: 1px solid rgba(255, 255, 255, .35); }
        .nexus-sprache button { background: none; border: 0; padding: 0 4px; color: #fff; font-size: 12px; cursor: pointer; opacity: .75; }
        .nexus-sprache button:hover { opacity: 1; text-decoration: underline; }
        .nexus-sprache button.aktiv { color: #D4AF37; font-weight: 700; opacity: 1; cursor: default; text-decoration: none; }
        /* Auf dem Handy war die Kopfzeile mit dem Umschalter zu breit und legte sich ueber
           den Menue-Knopf (Josh, 25.09.2026: "Menue laesst sich nicht mehr klicken"). Dort
           steht der Umschalter deshalb NUR im Profil-Menue (Klick aufs eigene Bild); die
           Kopfzeile sieht auf dem Handy wieder aus wie vorher. */
        /* Knoepfe schrumpfen nicht (sonst "D/E" untereinander) und uebernehmen die Schriftfarbe des
           Menues -- vorher fest dunkel, im dunklen Modus also unsichtbar (Josh, 28.09.2026, Screenshot). */
        /* Farbe wie die Menue-Links (".account .dropdown-menu li a" = --hh-primary-contrast): "inherit" lieferte die dunkle
           Grundschrift des Menues, sichtbar erst beim Drueberfahren (Josh, 28.09.2026, 2. Screenshot). */
        .nexus-sprache-menue { color: var(--hh-primary-contrast, #fff); display: flex; flex-wrap: nowrap; gap: 6px; align-items: center; padding: 6px 16px; white-space: nowrap; }
        .nexus-sprache-menue span { flex: 0 0 auto; font-size: 13px; color: inherit; opacity: .75; margin-right: 4px; }
        .nexus-sprache-menue button { flex: 0 0 auto; min-width: 42px; background: none; color: inherit; border: 1px solid currentColor; border-radius: 6px; padding: 5px 10px; font-size: 13px; line-height: 1.2; white-space: nowrap; cursor: pointer; }
        .nexus-sprache-menue button:hover { border-color: #D4AF37; }
        .nexus-sprache-menue button.aktiv { background: #D4AF37; border-color: #D4AF37; color: #0A1628; font-weight: 700; cursor: default; }
    </style>
    <div class="nexus-quick-links">
        <?= Html::beginForm(['/nexus-translate/sprache/setzen'], 'post', ['class' => 'nexus-sprache', 'title' => $nexusText['sprache'], 'aria-label' => $nexusText['sprache']]) ?>
            <?php foreach (['de' => 'DE', 'en-US' => 'EN', 'es' => 'ES'] as $code => $kuerzel): ?>
                <?php $aktiv = strtolower(explode('-', $code)[0]) === $nexusSprache; ?>
                <button type="<?= $aktiv ? 'button' : 'submit' ?>" name="sprache" value="<?= $code ?>"
                        class="<?= $aktiv ? 'aktiv' : '' ?>"><?= $kuerzel ?></button>
            <?php endforeach; ?>
        <?= Html::endForm() ?>
        <a class="nexus-ql-breit" href="<?= Html::encode($userModel->createUrl('/user/profile/home')) ?>">
            <?= Html::encode($nexusText['profil']) ?>
        </a>
        <a class="nexus-ql-breit" href="<?= Html::encode(\yii\helpers\Url::toRoute('/user/account/edit')) ?>">
            <?= Html::encode($nexusText['einstellungen']) ?>
        </a>
        <?php /* Hilfe & Support (Josh, 07.10.2026: Anfragen sollen nicht mehr bei ihm persoenlich landen) */ ?>
        <a href="<?= Html::encode(\yii\helpers\Url::toRoute('/nexus-hilfe/hilfe/index')) ?>" data-pjax-prevent="1">
            ❓ <?= Html::encode($nexusText['hilfe']) ?>
        </a>
    </div>
    <?= Html::beginTag('ul', $options) ?>
    <li class="dropdown account">
        <a href="#" id="account-dropdown-link" class="dropdown-toggle" data-bs-toggle="dropdown"
           aria-label="<?= Yii::t('base', 'Profile dropdown') ?>">

            <?php if ($this->context->showUserName): ?>
                <div class="user-title float-start d-none d-sm-block">
                    <strong><?= Html::encode($userModel->displayName); ?></strong><br/><span
                        class="truncate"><?= Html::encode($userModel->displayNameSub); ?></span>
                </div>
            <?php endif; ?>

            <?= Image::widget([
                'user' => $userModel,
                'link' => false,
                'width' => 32,
                'htmlOptions' => ['id' => 'user-account-image'],
                'showSelfOnlineStatus' => true,
            ]) ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <?= Html::beginForm(['/nexus-translate/sprache/setzen'], 'post', ['class' => 'nexus-sprache-menue']) ?>
                    <span><?= Html::encode($nexusText['sprache']) ?>:</span>
                    <?php foreach (['de' => 'DE', 'en-US' => 'EN', 'es' => 'ES'] as $code => $kuerzel): ?>
                        <?php $aktiv = strtolower(explode('-', $code)[0]) === $nexusSprache; ?>
                        <button type="<?= $aktiv ? 'button' : 'submit' ?>" name="sprache" value="<?= $code ?>"
                                class="<?= $aktiv ? 'aktiv' : '' ?>"><?= $kuerzel ?></button>
                    <?php endforeach; ?>
                <?= Html::endForm() ?>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= Html::encode(\yii\helpers\Url::toRoute('/nexus-hilfe/hilfe/index')) ?>" data-pjax-prevent="1">❓ <?= Html::encode($nexusText['hilfe']) ?></a></li>
            <?php foreach ($entries as $entry): ?>
                <li><?= $entry->render(['class' => 'dropdown-item']) ?></li>
            <?php endforeach; ?>
            <?= FooterMenu::widget(['location' => FooterMenu::LOCATION_ACCOUNT_MENU]); ?>
        </ul>
    </li>
    <?= Html::endTag('ul') ?>
<?php endif; ?>
