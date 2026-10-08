<?php

/**
 * Hilfe & Support. Angemeldet: Anleitungen, Frag Nova, Formular. Gast: nur das Formular (mit Name
 * und E-Mail), weil Gaeste weder Anleitungen noch Nachrichten sehen.
 *
 * @var $t array
 * @var $gast bool
 * @var $anleitungen array
 * @var $novaLink array|null
 * @var $fehler string|null
 * @var $eingabe array
 */

use humhub\helpers\Html;
use yii\helpers\Url;

$this->pageTitle = $t['titel'];
?>
<div class="container nexus-hilfe" style="max-width:860px;">
    <style>
        .nexus-hilfe .panel-body { font-size: 15px; }
        .nexus-hilfe h1 { font-size: 26px; font-weight: 700; margin: 6px 0 6px; }
        .nexus-hilfe h2 { font-size: 19px; font-weight: 700; margin: 0 0 8px; }
        .nexus-hilfe .nh-anleitungen { columns: 2 260px; padding-left: 18px; margin: 6px 0 0; }
        .nexus-hilfe .nh-anleitungen li { margin-bottom: 6px; break-inside: avoid; }
        .nexus-hilfe label { font-weight: 600; margin-top: 10px; display: block; }
        .nexus-hilfe .nh-falle { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
        .nexus-hilfe .nh-gold { background: #D4AF37; border-color: #D4AF37; color: #0A1628; font-weight: 700; }
    </style>

    <div class="panel panel-default">
        <div class="panel-body">
            <h1>❓ <?= Html::encode($t['titel']) ?></h1>
            <p><?= Html::encode($t['intro']) ?></p>
        </div>
    </div>

    <?php if (!$gast && $anleitungen !== []) : ?>
        <div class="panel panel-default">
            <div class="panel-body">
                <h2>📖 <?= Html::encode($t['anleitungen']) ?></h2>
                <p><?= Html::encode($t['anleitungen_text']) ?></p>
                <ul class="nh-anleitungen">
                    <?php foreach ($anleitungen as $a) : ?>
                        <li><a href="<?= Html::encode($a['link']) ?>"><?= Html::encode($a['titel']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$gast && $novaLink !== null) : ?>
        <div class="panel panel-default">
            <div class="panel-body">
                <h2>🤖 <?= Html::encode($t['nova']) ?></h2>
                <p><?= Html::encode($t['nova_text']) ?></p>
                <a href="#" class="btn btn-primary" data-action-click="ui.modal.load"
                   data-action-url="<?= Html::encode(Url::to($novaLink)) ?>"><?= Html::encode($t['nova_knopf']) ?></a>
            </div>
        </div>
    <?php endif; ?>

    <div class="panel panel-default" id="anfrage">
        <div class="panel-body">
            <h2>🆘 <?= Html::encode($t['formular']) ?></h2>
            <p><?= Html::encode($gast ? $t['formular_gast'] : $t['formular_text']) ?></p>
            <?php if ($fehler !== null) : ?>
                <div class="alert alert-danger"><?= Html::encode($fehler) ?></div>
            <?php endif; ?>
            <?= Html::beginForm(['/nexus-hilfe/hilfe/senden'], 'post', ['enctype' => 'multipart/form-data', 'data-pjax-prevent' => '1']) ?>
                <?php if ($gast) : ?>
                    <label for="nh-name"><?= Html::encode($t['name']) ?></label>
                    <input id="nh-name" class="form-control" name="name" maxlength="120" required value="<?= Html::encode($eingabe['name']) ?>">
                    <label for="nh-email"><?= Html::encode($t['email']) ?></label>
                    <input id="nh-email" class="form-control" type="email" name="email" maxlength="190" required value="<?= Html::encode($eingabe['email']) ?>">
                <?php endif; ?>

                <label for="nh-thema"><?= Html::encode($t['thema']) ?></label>
                <select id="nh-thema" class="form-control" name="thema">
                    <?php foreach (['konto', 'fehler', 'frage', 'sonstiges'] as $thema) : ?>
                        <option value="<?= $thema ?>" <?= $eingabe['thema'] === $thema ? 'selected' : '' ?>><?= Html::encode($t['themen'][$thema]) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="nh-text"><?= Html::encode($t['beschreibung']) ?></label>
                <textarea id="nh-text" class="form-control" name="text" rows="6" maxlength="5000" required
                          placeholder="<?= Html::encode($t['beschreibung_hilfe']) ?>"><?= Html::encode($eingabe['text']) ?></textarea>

                <label for="nh-bild"><?= Html::encode($t['bild']) ?></label>
                <input id="nh-bild" class="form-control" type="file" name="bild" accept="image/jpeg,image/png,image/webp,image/gif">

                <div class="nh-falle" aria-hidden="true">
                    <label for="nh-website">Website</label>
                    <input id="nh-website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <p style="margin-top:16px;">
                    <button type="submit" class="btn nh-gold"><?= Html::encode($t['senden']) ?></button>
                </p>
            <?= Html::endForm() ?>
        </div>
    </div>
</div>
