<?php

use humhub\helpers\Html;

/* @var string $dateiname */
/* @var string $html HTML aus cebe/markdown, bereits durch HtmlPurifier gereinigt -- unverschluesselte Ausgabe ist hier bewusst und sicher. */
/* @var string $downloadUrl */

$this->title = $dateiname;
?>
<div class="panel panel-default nexus-md-preview">
    <div class="panel-heading nexus-md-preview-heading">
        <strong><?= Html::encode($dateiname) ?></strong>
        <a href="<?= Html::encode($downloadUrl) ?>" class="btn btn-default btn-xs">
            <i class="fa fa-download"></i> Rohdatei herunterladen
        </a>
    </div>
    <div class="panel-body nexus-md-preview-content">
        <?= $html ?>
    </div>
</div>
<style>
    .nexus-md-preview-heading { display: flex; justify-content: space-between; align-items: center; }
    .nexus-md-preview-content { line-height: 1.6; }
    .nexus-md-preview-content h1,
    .nexus-md-preview-content h2,
    .nexus-md-preview-content h3 { margin-top: 1.2em; }
    /* Keine festen hellen Farben: im dunklen Modus stand sonst helle (goldene) Schrift auf fast weissem
       Grund und war nicht lesbar (Rueckmeldung eines Mitglieds mit Sehschwaeche). Durchsichtige Toenung
       und die Schriftfarbe der Seite passen in beiden Modi. */
    .nexus-md-preview-content pre { background: rgba(127, 127, 127, .15); color: inherit; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .nexus-md-preview-content code { background: rgba(127, 127, 127, .15); color: inherit; padding: 2px 4px; border-radius: 3px; }
    .nexus-md-preview-content pre code { background: none; padding: 0; }
    .nexus-md-preview-content blockquote { border-left: 3px solid rgba(127, 127, 127, .5); padding-left: 12px; opacity: .85; }
    .nexus-md-preview-content table { border-collapse: collapse; }
    .nexus-md-preview-content table td,
    .nexus-md-preview-content table th { border: 1px solid rgba(127, 127, 127, .45); padding: 6px 10px; }
</style>
