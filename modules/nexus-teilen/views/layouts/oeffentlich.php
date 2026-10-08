<?php

/**
 * Eigener, schlichter Rahmen fuer die oeffentliche Leseseite: KEIN Portal-Layout (keine
 * Navigation, keine Mitgliederlisten, kein Skript). Nur Logo, Inhalt, Einladung, Fusszeile.
 * Die og:-Angaben sorgen fuer eine Vorschau (Titel, Text, Bild), wenn der Link in Signal,
 * WhatsApp, Telegram usw. eingefuegt wird.
 *
 * @var $this \humhub\components\View
 * @var $content string
 */

use humhub\helpers\Html;

$p = $this->params['nexusTeilen'] ?? [];
$titel = (string)($p['titel'] ?? 'N.E.X.U.S.');
$logo = Yii::$app->view->theme->getBaseUrl() . '/resources/img/nexus-logo-voll.jpg';
?>
<!DOCTYPE html>
<html lang="<?= Html::encode($p['sprache'] ?? 'de') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="<?= !empty($p['indexieren']) ? 'index, follow' : 'noindex, nofollow' ?>">
    <?php if (!empty($p['indexieren']) && !empty($p['adresse'])) : ?>
        <link rel="canonical" href="<?= Html::encode($p['adresse']) ?>">
        <?php foreach ($p['sprachfassungen'] ?? [] as $code => $url) : ?>
            <link rel="alternate" hreflang="<?= Html::encode($code) ?>" href="<?= Html::encode($url) ?>">
        <?php endforeach; ?>
        <meta name="description" content="<?= Html::encode((string)($p['beschreibung'] ?? '')) ?>">
    <?php endif; ?>
    <title><?= Html::encode($titel) ?> – N.E.X.U.S.</title>
    <?php if (!empty($p['adresse'])) : ?>
        <meta property="og:type" content="article">
        <meta property="og:site_name" content="N.E.X.U.S.">
        <meta property="og:title" content="<?= Html::encode($titel) ?>">
        <meta property="og:description" content="<?= Html::encode((string)($p['beschreibung'] ?? '')) ?>">
        <meta property="og:url" content="<?= Html::encode($p['adresse']) ?>">
        <meta property="og:image" content="<?= Html::encode((string)($p['bild'] ?? '')) ?>">
        <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>
    <link rel="icon" href="/uploads/icon/icon.png">
    <?= $p['zusatzKopf'] ?? '' /* z. B. Stylesheets der Karte, fertige Tags aus nexus-karte */ ?>
    <style>
        :root { --navy: #0b1a2e; --gold: #D4AF37; --text: #1f2933; --leise: #6b7280; --flaeche: #ffffff; --grund: #f3f1ea; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--grund); color: var(--text); font: 17px/1.65 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        a { color: #8a6d12; }
        .kopf { background: var(--navy); text-align: center; padding: 18px 16px 14px; }
        .kopf img { max-width: 190px; width: 60%; height: auto; border-radius: 6px; }
        .rahmen { max-width: 760px; margin: 0 auto; padding: 24px 16px 40px; }
        .karte { background: var(--flaeche); border-radius: 10px; box-shadow: 0 2px 14px rgba(11, 26, 46, .08); padding: 28px 30px; }
        .herkunft { font-size: 13px; letter-spacing: .4px; text-transform: uppercase; color: var(--leise); font-weight: 600; margin-bottom: 6px; }
        .herkunft time { text-transform: none; letter-spacing: 0; font-weight: 400; }
        .sprachen { font-size: 14px; color: var(--leise); margin: 0 0 18px; }
        .sprachen a, .sprachen strong { margin-right: 10px; }
        .hinweis-uebersetzt { font-size: 14px; color: var(--leise); background: #faf6e8; border-left: 3px solid var(--gold); padding: 6px 10px; margin: 0 0 18px; }
        h1.titel { font-size: 28px; line-height: 1.25; margin: 4px 0 16px; color: var(--navy); }
        .inhalt { overflow-wrap: anywhere; }
        .inhalt img, .inhalt video, .anhaenge img, .anhaenge video { max-width: 100%; height: auto; border-radius: 6px; }
        .inhalt h1, .inhalt h2, .inhalt h3 { color: var(--navy); line-height: 1.3; }
        .inhalt blockquote { margin: 0; padding: 4px 16px; border-left: 4px solid var(--gold); color: #374151; background: #fbfaf5; }
        .inhalt pre, .inhalt code { background: #f3f4f6; border-radius: 4px; }
        .inhalt pre { padding: 10px; overflow-x: auto; }
        .inhalt table { border-collapse: collapse; display: block; overflow-x: auto; }
        .inhalt td, .inhalt th { border: 1px solid #e5e7eb; padding: 4px 8px; }
        .anhaenge { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 14px; }
        .anhaenge h2 { font-size: 15px; color: var(--leise); margin: 0 0 10px; }
        .anhaenge .medium { margin-bottom: 12px; }
        /* Videonachricht rund wie im Portal; mask-image, weil Safari sonst eckig abspielt. */
        .anhaenge video.rund { width: 280px; max-width: 80vw; aspect-ratio: 1 / 1; object-fit: cover; border-radius: 50%;
            -webkit-mask-image: -webkit-radial-gradient(white, black); mask-image: radial-gradient(white, black);
            border: 3px solid var(--gold); background: #000; display: block; }
        .einladung { margin-top: 26px; background: var(--navy); color: #fff; border-radius: 10px; padding: 24px 26px; text-align: center; }
        .einladung p { margin: 0 0 16px; }
        .knopf { display: inline-block; background: var(--gold); color: var(--navy); font-weight: 700; text-decoration: none; padding: 12px 22px; border-radius: 8px; margin: 4px; }
        .knopf:hover, .knopf:focus { background: #e6c45a; }
        .knopf.leise { background: transparent; color: #fff; border: 1px solid rgba(255, 255, 255, .5); font-weight: 600; }
        .fuss { text-align: center; font-size: 14px; color: var(--leise); padding: 18px 16px 30px; }
        .fuss a { margin: 0 8px; }
        .nsf-kopf { text-align: center; margin: 4px 0 22px; }
        .nsf-kopf p { color: var(--leise); margin: 0 0 10px; }
        .nsf-blaettern { display: flex; justify-content: space-between; margin: 22px 0 0; font-weight: 600; }
        .nsf-blaettern a:only-child { margin-left: auto; }
        .mehr { text-align: center; margin: 18px 0 0; font-weight: 600; }
        .nsf-karte-block { margin: 34px 0 0; }
        .nsf-karte-block .nka-gast { padding: 0; margin-bottom: 0; }
        .nsf-karte-block .nka-gast-titel { color: var(--navy); }
        <?= $this->render('@nexus-teilen/views/lesen/_karten_stil') ?>
        @media (max-width: 560px) { .karte { padding: 20px 18px; } h1.titel { font-size: 23px; } .einladung { padding: 20px 16px; } }
    </style>
</head>
<body>
<header class="kopf">
    <img src="<?= Html::encode($logo) ?>" alt="N.E.X.U.S. – Next Evolution Xperience United Species">
</header>
<main class="rahmen">
    <?= $content ?>
</main>
<?= $p['zusatzEnde'] ?? '' /* z. B. Skripte der Karte */ ?>
</body>
</html>
