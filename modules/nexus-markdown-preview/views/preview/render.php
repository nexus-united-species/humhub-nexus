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
    .nexus-md-preview-content pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .nexus-md-preview-content code { background: #f5f5f5; padding: 2px 4px; border-radius: 3px; }
    .nexus-md-preview-content blockquote { border-left: 3px solid #ddd; padding-left: 12px; color: #666; }
    .nexus-md-preview-content table { border-collapse: collapse; }
    .nexus-md-preview-content table td,
    .nexus-md-preview-content table th { border: 1px solid #ddd; padding: 6px 10px; }
</style>
