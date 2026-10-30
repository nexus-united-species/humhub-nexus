<?php

/**
 * Dateien einer Kachel (oder eines Vorschlags) nach Fassung: die neueste offen, aeltere zum Aufklappen.
 * Bilder als gleich grosse Vorschaubilder in einem Raster, alle anderen Dateien als Liste. Mit $karte, $ich und $url
 * (nur an Kacheln, nicht im Vorschlag) bekommt jede Datei, die die Person loeschen darf, einen Papierkorb-Knopf.
 *
 * @var $fassungen array<int, nexus\modules\projekte\models\Datei[]>
 * @var $kompakt bool  ohne Bildvorschau (im Formular)
 * @var $karte nexus\modules\projekte\models\Karte|null
 * @var $ich humhub\modules\user\models\User|null
 * @var $url callable|null  Aktion, Parameter => Adresse (wie in _detail.php)
 * @var $lesen callable|null  Datei => Adresse der Leseansicht (Markdown/Text im Portal)
 */

use humhub\helpers\Html;
use nexus\modules\projekte\services\Rechte;
use nexus\modules\projekte\services\Texte;

$kompakt = $kompakt ?? false;
$karte = $karte ?? null;
$ich = $ich ?? null;
$url = $url ?? null;
$lesen = $lesen ?? null;

$darfLoeschen = static fn($d): bool => $karte !== null && $url !== null && Rechte::darfDateiLoeschen($karte, $d, $ich);
$loeschen = function ($d, string $anzeige) use ($url, $darfLoeschen, $kompakt): string {
    if (!$darfLoeschen($d)) {
        return '';
    }
    // Im Formular (kompakt) darf kein Formular im Formular stehen: Der Knopf bekommt ein form-Attribut, das Formular selbst
    // sammelt die Ansicht in $this->params und gibt es nach dem Hauptformular aus (vorschlag/formular.php).
    $knopf = '<button type="submit" class="nx-pj-x" title="' . Html::encode(Texte::t('datei_loeschen')) . '" aria-label="' . Html::encode(Texte::t('datei_loeschen')) . '"'
        . ($kompakt ? ' form="nx-pj-loesch-' . (int)$d->id . '"' : '') . '><i class="fa fa-trash-o"></i></button>';
    $formular = Html::beginForm($url('datei-loeschen', ['d' => $d->id]), 'post', [
        'class' => 'nx-pj-datei-loeschen',
        'id' => $kompakt ? 'nx-pj-loesch-' . (int)$d->id : null,
        'data-nx-pj-frage' => sprintf(Texte::t('datei_loeschen_frage'), $anzeige),
    ]) . ($kompakt ? '' : $knopf) . Html::endForm();
    if ($kompakt) {
        $this->params['nxPjLoeschFormulare'][] = $formular;
        return $knopf;
    }
    return $formular;
};
$groesse = static fn(int $b): string => Yii::$app->formatter->asShortSize($b, 1);

$liste = function (array $dateien) use ($kompakt, $loeschen, $groesse, $lesen): string {
    $bilder = $andere = [];
    foreach ($dateien as $d) {
        $info = $d->info();
        if ($info === null) {
            // Im Dateibereich des Kreises geloescht: den verwaisten Eintrag kann man hier entfernen
            $andere[] = '<li class="nx-pj-leise"><i class="fa fa-ban"></i> ' . Html::encode(Texte::t('datei_fehlt')) . ' ' . $loeschen($d, Texte::t('datei_fehlt')) . '</li>';
            continue;
        }
        if ($info['bild'] && !$kompakt) {
            $bilder[] = '<figure class="nx-pj-vorschau">'
                . '<a href="' . Html::encode($info['url']) . '" target="_blank" rel="noopener" title="' . Html::encode($info['anzeige']) . '">'
                . '<img src="' . Html::encode($info['url']) . '" alt="' . Html::encode($info['anzeige']) . '" loading="lazy"></a>'
                . '<figcaption><span class="nx-pj-dname" title="' . Html::encode($info['anzeige']) . '">' . Html::encode($info['anzeige']) . '</span>'
                . ' <span class="nx-pj-leise">' . Html::encode($groesse($info['groesse'])) . '</span></figcaption>'
                . $loeschen($d, $info['anzeige']) . '</figure>';
        } else {
            $andere[] = '<li><a href="' . Html::encode($info['url']) . '" target="_blank" rel="noopener"><i class="fa fa-file-o"></i> '
                . Html::encode($info['anzeige']) . '</a> <span class="nx-pj-leise">(' . Html::encode($groesse($info['groesse'])) . ')</span> '
                . ($info['lesbar'] && $lesen !== null ? '<a class="btn btn-xs btn-light" href="' . Html::encode($lesen($d)) . '"><i class="fa fa-book"></i> ' . Html::encode(Texte::t('datei_lesen')) . '</a> ' : '')
                . $loeschen($d, $info['anzeige']) . '</li>';
        }
    }
    return ($bilder !== [] ? '<div class="nx-pj-galerie">' . implode('', $bilder) . '</div>' : '')
        . ($andere !== [] ? '<ul class="nx-pj-dateiliste">' . implode('', $andere) . '</ul>' : '');
};
$kopf = function (int $nr, array $dateien) use ($url, $darfLoeschen, $kompakt) {
    $erste = $dateien[0];
    $teile = [sprintf(Texte::t('fassung'), $nr), Texte::datum($erste->created_at)];
    if ($erste->autor) {
        $teile[] = $erste->autor->displayName;
    }
    $teile[] = sprintf(Texte::t('dateien_anzahl'), count($dateien));
    $html = Html::encode(implode(' · ', $teile)) . ($erste->hinweis ? ' – <em>' . Html::encode($erste->hinweis) . '</em>' : '');
    // Ganze Fassung loeschen: nur anbieten, wenn die Person ALLE ihre Dateien loeschen darf und es mehr als eine ist
    if ($url !== null && count($dateien) > 1 && count(array_filter($dateien, $darfLoeschen)) === count($dateien)) {
        $formular = Html::beginForm($url('fassung-loeschen', ['f' => $nr]), 'post', [
            'class' => 'nx-pj-datei-loeschen',
            'id' => $kompakt ? 'nx-pj-fassung-loesch-' . $nr : null,
            'data-nx-pj-frage' => sprintf(Texte::t('fassung_loeschen_frage'), $nr),
        ]) . ($kompakt ? '' : '<button type="submit" class="nx-pj-x nx-pj-x-text"><i class="fa fa-trash-o"></i> ' . Html::encode(Texte::t('fassung_loeschen')) . '</button>') . Html::endForm();
        if ($kompakt) {
            $this->params['nxPjLoeschFormulare'][] = $formular;
            $html .= ' <button type="submit" form="nx-pj-fassung-loesch-' . $nr . '" class="nx-pj-x nx-pj-x-text"><i class="fa fa-trash-o"></i> ' . Html::encode(Texte::t('fassung_loeschen')) . '</button>';
        } else {
            $html .= ' ' . $formular;
        }
    }
    return $html;
};
$nummern = array_keys($fassungen);
$neueste = array_shift($nummern);
?>
<div class="nx-pj-fassung">
    <div class="nx-pj-leise nx-pj-fassungskopf"><?= $kopf($neueste, $fassungen[$neueste]) ?></div>
    <?= $liste($fassungen[$neueste]) ?>
</div>
<?php if ($nummern !== []) : ?>
    <details class="nx-pj-fassung">
        <summary class="nx-pj-leise"><?= Html::encode(Texte::t('aeltere_fassungen')) ?> (<?= count($nummern) ?>)</summary>
        <?php foreach ($nummern as $nr) : ?>
            <div class="nx-pj-leise nx-pj-fassungskopf" style="margin-top:.4rem"><?= $kopf($nr, $fassungen[$nr]) ?></div>
            <?= $liste($fassungen[$nr]) ?>
        <?php endforeach; ?>
    </details>
<?php endif; ?>
