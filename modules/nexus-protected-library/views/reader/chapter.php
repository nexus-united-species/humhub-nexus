<?php

/** @var yii\web\View $this */
/** @var nexus\modules\protectedLibrary\models\Book $buch */
/** @var nexus\modules\protectedLibrary\models\Chapter $kapitel */
/** @var nexus\modules\protectedLibrary\models\Chapter[] $alleKapitel */
/** @var nexus\modules\protectedLibrary\models\Chapter|null $vorheriges */
/** @var nexus\modules\protectedLibrary\models\Chapter|null $naechstes */

use humhub\modules\web\security\helpers\Security;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $buch->title . ' -- ' . $kapitel->title;

$nonce = Security::getNonce(true);
?>
<style nonce="<?= Html::encode($nonce) ?>">
    .nexus-lesebereich {
        max-width: 720px;
        margin: 0 auto;
        background: #fdfcf9;
        color: #2b2620;
        padding: 40px 56px;
        border-radius: 4px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        font-family: Georgia, 'Times New Roman', serif;
        font-size: 18px;
        line-height: 1.75;

        /* Kopierschutz: Text laesst sich nicht markieren */
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }
    .nexus-lesebereich h1, .nexus-lesebereich h2, .nexus-lesebereich h3 {
        font-family: Georgia, 'Times New Roman', serif;
        margin-top: 1.4em;
    }
    .nexus-lesebereich p {
        margin: 0 0 1.1em 0;
    }
    .nexus-lesebereich img {
        max-width: 100%;
        height: auto;
        pointer-events: none; /* verhindert Rechtsklick/Ziehen direkt auf dem Bild */
    }
    .nexus-buch-kopf {
        max-width: 720px;
        margin: 0 auto 12px auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .nexus-kapitel-nav {
        max-width: 720px;
        margin: 20px auto 0 auto;
        display: flex;
        justify-content: space-between;
        gap: 12px;
    }
    @media print {
        .nexus-lesebereich, .nexus-buch-kopf, .nexus-kapitel-nav {
            display: none !important;
        }
    }
    @media (max-width: 800px) {
        .nexus-lesebereich {
            padding: 24px 20px;
            font-size: 17px;
        }
    }
</style>

<div class="nexus-buch-kopf">
    <div>
        <a href="<?= Url::to(['reader/category', 'language' => $buch->language, 'mediaType' => $buch->media_type]) ?>">&larr; Bibliothek</a>
        &nbsp;·&nbsp;
        <strong><?= Html::encode($buch->title) ?></strong>
    </div>
    <div>
        <select id="nexus-kapitel-auswahl" class="form-control input-sm" style="width: auto; display: inline-block;">
            <?php foreach ($alleKapitel as $k): ?>
                <option value="<?= Url::to(['reader/chapter', 'bookId' => $buch->id, 'chapterId' => $k->id]) ?>"
                    <?= $k->id === $kapitel->id ? 'selected' : '' ?>>
                    <?= Html::encode($k->title) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="nexus-lesebereich" id="nexus-lesebereich">
    <h2><?= Html::encode($kapitel->title) ?></h2>
    <?= $kapitel->html_content ?>
</div>

<div class="nexus-kapitel-nav">
    <div>
        <?php if ($vorheriges): ?>
            <a href="<?= Url::to(['reader/chapter', 'bookId' => $buch->id, 'chapterId' => $vorheriges->id]) ?>" class="btn btn-default">
                &larr; <?= Html::encode($vorheriges->title) ?>
            </a>
        <?php endif; ?>
    </div>
    <div>
        <?php if ($naechstes): ?>
            <a href="<?= Url::to(['reader/chapter', 'bookId' => $buch->id, 'chapterId' => $naechstes->id]) ?>" class="btn btn-default">
                <?= Html::encode($naechstes->title) ?> &rarr;
            </a>
        <?php endif; ?>
    </div>
</div>

<script nonce="<?= Html::encode($nonce) ?>">
(function () {
    var bereich = document.getElementById('nexus-lesebereich');
    if (!bereich) { return; }

    // Rechtsklick-Menue im Lesebereich unterbinden.
    bereich.addEventListener('contextmenu', function (e) { e.preventDefault(); });

    // Kopieren/Ausschneiden im Lesebereich unterbinden.
    bereich.addEventListener('copy', function (e) { e.preventDefault(); });
    bereich.addEventListener('cut', function (e) { e.preventDefault(); });

    // Bilder lassen sich nicht per Ziehen speichern.
    bereich.addEventListener('dragstart', function (e) { e.preventDefault(); });

    // Gaengige Tastenkombinationen (Kopieren, Drucken, Speichern, Alles
    // markieren) innerhalb des Lesebereichs abfangen -- kein Eingriff
    // ausserhalb, damit die uebrige Seite normal bedienbar bleibt.
    document.addEventListener('keydown', function (e) {
        var mod = e.ctrlKey || e.metaKey;
        if (!mod || ['c', 'x', 'p', 's', 'a'].indexOf(e.key.toLowerCase()) === -1) {
            return;
        }

        // Drucken (Strg/Cmd+P) betrifft die ganze Seite -- immer blockieren,
        // solange man ueberhaupt auf dieser Kapitelseite ist. Kopieren/
        // Ausschneiden/Speichern/Alles-markieren nur blockieren, wenn die
        // Auswahl bzw. der Fokus im Lesebereich liegt, damit z.B. die
        // Kapitel-Auswahlliste normal bedienbar bleibt.
        var auswahl = window.getSelection ? window.getSelection() : null;
        var zielImLesebereich = bereich.contains(document.activeElement) ||
            (auswahl && auswahl.anchorNode && bereich.contains(auswahl.anchorNode));

        if (e.key.toLowerCase() === 'p' || zielImLesebereich) {
            e.preventDefault();
        }
    });

    document.getElementById('nexus-kapitel-auswahl').addEventListener('change', function () {
        window.location.href = this.value;
    });
})();
</script>
