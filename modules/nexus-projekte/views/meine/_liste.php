<?php

/**
 * Liste der eigenen Aufgaben -- fuer die Seite "Meine Aufgaben" und den Kasten auf der Startseite.
 *
 * @var $eintraege array<int, array{typ: string, karte: nexus\modules\projekte\models\Karte, datum: ?string, ueberfaellig: bool}>
 * @var $kurz bool
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Texte;

$symbole = ['verantwortlich' => 'user', 'helfer' => 'users', 'rueckmeldung' => 'thumbs-o-up'];
?>
<ul class="nx-pj-liste">
    <?php foreach ($eintraege as $e) : ?>
        <?php $k = $e['karte']; ?>
        <li>
            <span>
                <i class="fa fa-<?= $symbole[$e['typ']] ?> nx-pj-leise"></i>
                <a href="<?= Html::encode($k->url()) ?>"><strong><?= Html::encode($k->titel) ?></strong></a>
                <br><span class="nx-pj-leise"><?= Html::encode(Texte::t('typ_' . $e['typ'])) ?> · <?= Html::encode($k->projekt->titel) ?><?php if (!$kurz) : ?> · <?= Html::encode($k->projekt->kreis->name) ?><?php endif; ?></span>
            </span>
            <?php if ($e['datum'] !== null) : ?>
                <span class="nx-pj-leise <?= $e['ueberfaellig'] ? 'nx-pj-rot' : '' ?>" style="white-space:nowrap">
                    <?= Html::encode($e['ueberfaellig'] ? sprintf(Texte::t('ueberfaellig_seit'), Texte::datum($e['datum'])) : sprintf(Texte::t('bis'), Texte::datum($e['datum']))) ?>
                </span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
