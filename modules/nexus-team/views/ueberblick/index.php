<?php

/**
 * Community-Überblick für das Team.
 *
 * @var $ich humhub\modules\user\models\User
 * @var $neue array
 * @var $neueWoche int
 * @var $offenWoche int
 * @var $uebersicht array
 * @var $aeltesteWoche int
 * @var $unbegruesstWoche int
 * @var $meldungen array
 * @var $fragen array
 * @var $support array
 * @var $ruhige array
 */

use humhub\helpers\Html;
use nexus\modules\team\services\Daten;
use nexus\modules\team\services\Texte;
use yii\helpers\Url;

$t = fn(string $k) => Texte::t($k);
$datum = fn(string $zeit) => Yii::$app->formatter->asDate($zeit, 'medium');
$ichName = trim((string)($ich->profile->firstname ?? '')) !== '' ? trim((string)$ich->profile->firstname) : (string)$ich->username;
$kachel = fn(int $zahl, string $titel, string $zusatz = '', bool $wichtig = false) =>
    '<div class="nx-tm-kachel' . ($wichtig ? ' nx-tm-wichtig' : '') . '"><b>' . $zahl . '</b><span>' . Html::encode($titel)
    . ($zusatz !== '' ? ' · ' . Html::encode($zusatz) : '') . '</span></div>';
$besuchText = fn(string $b) => $t('besuch_' . $b);
// Wochen-Ansicht: beide Listen merken sich ihre Woche in der Adresse (neue=, offen=)
$seite = fn(int $neue, int $offen) => Url::to(['/nexus-team/ueberblick/index', 'neue' => $neue, 'offen' => $offen]);
$wochenText = function (int $woche) use ($datum): string {
    [$von, $bis] = Daten::wochenFenster($woche);
    return $datum(date('Y-m-d H:i:s', $von)) . ' – ' . $datum(date('Y-m-d H:i:s', $bis - 1));
};
$blaettern = function (int $woche, int $aeltesteWoche, callable $link) use ($t, $wochenText): string {
    $alt = $woche < $aeltesteWoche
        ? '<a class="btn btn-sm btn-light" href="' . Html::encode($link($woche + 1)) . '">' . Html::encode($t('woche_aelter')) . '</a>'
        : '<span class="btn btn-sm btn-light disabled">' . Html::encode($t('woche_aelter')) . '</span>';
    $neu = $woche > 0
        ? '<a class="btn btn-sm btn-light" href="' . Html::encode($link($woche - 1)) . '">' . Html::encode($t('woche_neuer')) . '</a>'
        : '<span class="btn btn-sm btn-light disabled">' . Html::encode($t('woche_neuer')) . '</span>';
    $titel = $woche === 0 ? $t('woche_diese') . ' · ' : '';
    return '<div class="nx-tm-blaettern">' . $alt . '<strong>' . Html::encode($titel . $wochenText($woche)) . '</strong>' . $neu . '</div>';
};
?>
<div class="container">
<div class="panel panel-default nx-tm">
    <div class="panel-body">
        <h1><i class="fa fa-handshake-o"></i> <?= Html::encode($t('titel')) ?></h1>
        <p class="nx-tm-leise"><?= Html::encode($t('einleitung')) ?></p>

        <div class="nx-tm-kacheln">
            <?= $kachel(count($neue), $t('k_neue'), $t('k_neue_zeit')) ?>
            <?= $kachel((int)$uebersicht['unbegruesst'], $t('k_unbegruesst'), '', $uebersicht['unbegruesst'] > 0) ?>
            <?= $kachel(count($meldungen), $t('k_meldungen'), '', count($meldungen) > 0) ?>
            <?= $kachel(count($fragen), $t('k_fragen')) ?>
            <?= $kachel(count($ruhige), $t('k_ruhig')) ?>
        </div>

        <h2><?= Html::encode($t('h_neue')) ?></h2>
        <p class="nx-tm-leise"><?= Html::encode($t('neue_text')) ?></p>
        <?= $blaettern($neueWoche, $aeltesteWoche, fn(int $w) => $seite($w, $offenWoche)) ?>
        <p class="nx-tm-leise"><?= Html::encode(sprintf($t('woche_zahl'), count($neue), $unbegruesstWoche)) ?></p>
        <?php if ($uebersicht['wochen'] !== []) : ?>
            <p class="nx-tm-leise nx-tm-chips"><?= Html::encode($t('offene_wochen')) ?>
                <?php foreach (array_slice($uebersicht['wochen'], 0, 14, true) as $w => $anzahl) : ?>
                    <a class="nx-tm-chip<?= $w === $neueWoche ? ' aktiv' : '' ?>" href="<?= Html::encode($seite((int)$w, $offenWoche)) ?>"><?= Html::encode(explode(' – ', $wochenText((int)$w))[0]) ?> (<?= (int)$anzahl ?>)</a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
        <?php if ($unbegruesstWoche > 0) : ?>
            <?= Html::beginForm(['/nexus-team/ueberblick/woche-begruesst'], 'post', ['class' => 'nx-tm-woche-form']) ?>
                <input type="hidden" name="woche" value="<?= (int)$neueWoche ?>">
                <input type="hidden" name="neue" value="<?= (int)$neueWoche ?>">
                <input type="hidden" name="offen" value="<?= (int)$offenWoche ?>">
                <button type="submit" class="btn btn-sm btn-light" data-nx-team-frage="<?= Html::encode($t('woche_alle_frage')) ?>"><?= Html::encode($t('woche_alle_markieren')) ?></button>
            <?= Html::endForm() ?>
        <?php endif; ?>
        <?php if ($neue === []) : ?>
            <div class="nx-tm-leer"><?= Html::encode($t('keine_neuen')) ?></div>
        <?php else : ?>
            <table class="nx-tm-tabelle">
                <thead><tr>
                    <th><?= Html::encode($t('sp_name')) ?></th><th><?= Html::encode($t('sp_seit')) ?></th>
                    <th><?= Html::encode($t('sp_vorgestellt')) ?></th><th><?= Html::encode($t('sp_nova')) ?></th>
                    <th><?= Html::encode($t('sp_besuch')) ?></th><th><?= Html::encode($t('sp_begruesst')) ?></th>
                    <th><?= Html::encode($t('sp_aktion')) ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($neue as $m) : ?>
                    <tr>
                        <td data-spalte="<?= Html::encode($t('sp_name')) ?>">
                            <a href="<?= Html::encode(Url::to('/u/' . rawurlencode($m['username']))) ?>"><strong><?= Html::encode($m['name']) ?></strong></a>
                            <div class="nx-tm-leise"><?= Html::encode(strtoupper(explode('-', $m['sprache'] ?: '-')[0] ?: '–')) ?></div>
                        </td>
                        <td data-spalte="<?= Html::encode($t('sp_seit')) ?>"><?= Html::encode($datum($m['seit'])) ?></td>
                        <td data-spalte="<?= Html::encode($t('sp_vorgestellt')) ?>">
                            <?= $m['vorgestellt'] ? '<span class="nx-tm-ja">✓ ' . Html::encode($t('ja')) . '</span>' : '<span class="nx-tm-offen">' . Html::encode($t('noch_nicht')) . '</span>' ?>
                        </td>
                        <td data-spalte="<?= Html::encode($t('sp_nova')) ?>">
                            <?= $m['nova'] !== null ? '<span class="nx-tm-ja">' . Html::encode($t('nova_gesendet')) . '</span>' : '<span class="nx-tm-leise">' . Html::encode($t('noch_nicht')) . '</span>' ?>
                        </td>
                        <td data-spalte="<?= Html::encode($t('sp_besuch')) ?>"><?= Html::encode($besuchText($m['besuch'])) ?></td>
                        <td data-spalte="<?= Html::encode($t('sp_begruesst')) ?>">
                            <?php if ($m['begruesst'] !== null) : ?>
                                <span class="nx-tm-ja"><?= Html::encode(sprintf($t('begruesst_von'), $m['begruesst']['von'], $datum($m['begruesst']['am']))) ?></span>
                            <?php else : ?>
                                <span class="nx-tm-offen"><?= Html::encode($t('noch_nicht')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="nx-tm-aktion">
                                <button type="button" class="btn btn-sm btn-light"
                                        data-nx-team-kopieren="<?= Html::encode(Texte::willkommen($m['sprache'], $m['vorname'], $ichName)) ?>"
                                        data-nx-team-kopiert="<?= Html::encode($t('kopiert')) ?>"><?= Html::encode($t('kopieren')) ?></button>
                                <a class="btn btn-sm btn-primary" data-bs-target="#globalModal"
                                   href="<?= Html::encode(Url::to(['/mail/mail/create', 'userGuid' => $m['guid']])) ?>"><i class="fa fa-envelope"></i> <?= Html::encode($t('schreiben')) ?></a>
                                <?php if ($m['begruesst'] === null) : ?>
                                    <?= Html::beginForm(['/nexus-team/ueberblick/begruesst'], 'post') ?>
                                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                        <input type="hidden" name="neue" value="<?= (int)$neueWoche ?>"><input type="hidden" name="offen" value="<?= (int)$offenWoche ?>">
                                        <button type="submit" class="btn btn-sm btn-success"><?= Html::encode($t('begruesst_knopf')) ?></button>
                                    <?= Html::endForm() ?>
                                <?php else : ?>
                                    <?= Html::beginForm(['/nexus-team/ueberblick/zuruecknehmen'], 'post') ?>
                                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                                        <input type="hidden" name="neue" value="<?= (int)$neueWoche ?>"><input type="hidden" name="offen" value="<?= (int)$offenWoche ?>">
                                        <button type="submit" class="btn btn-sm btn-link"><?= Html::encode($t('zuruecknehmen')) ?></button>
                                    <?= Html::endForm() ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2><?= Html::encode($t('h_meldungen')) ?></h2>
        <p class="nx-tm-leise"><?= Html::encode($t('meldungen_text')) ?></p>
        <?php if ($meldungen === []) : ?>
            <div class="nx-tm-leer"><?= Html::encode($t('keine_meldungen')) ?></div>
        <?php else : ?>
            <ul class="nx-tm-liste">
            <?php foreach ($meldungen as $r) : ?>
                <li>
                    <div class="nx-tm-vorschau">
                        <strong><?= Html::encode($t($r['art'])) ?> · <?= Html::encode($t('grund_' . $r['grund'])) ?></strong>
                        <span class="nx-tm-leise"> · <?= Html::encode($r['kreis']) ?> · <?= Html::encode($datum($r['zeit'])) ?></span>
                        <div><?= Html::encode($r['vorschau']) ?></div>
                    </div>
                    <a class="btn btn-sm btn-light" href="<?= Html::encode(Url::to(['/content/perma', 'id' => $r['content_id']])) ?>"><?= Html::encode($t('oeffnen')) ?></a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2><?= Html::encode($t('h_fragen')) ?></h2>
        <p class="nx-tm-leise"><?= Html::encode($t('fragen_text')) ?></p>
        <?= $blaettern($offenWoche, 260, fn(int $w) => $seite($neueWoche, $w)) ?>
        <?php if ($fragen === []) : ?>
            <div class="nx-tm-leer"><?= Html::encode($t('fragen_woche_leer')) ?></div>
        <?php else : ?>
            <ul class="nx-tm-liste">
            <?php foreach ($fragen as $f) : ?>
                <li>
                    <div class="nx-tm-vorschau">
                        <strong><?= Html::encode($f['kreis']) ?></strong>
                        <span class="nx-tm-leise"> · <?= Html::encode($datum($f['zeit'])) ?></span>
                        <div><?= Html::encode($f['vorschau']) ?></div>
                    </div>
                    <a class="btn btn-sm btn-light" href="<?= Html::encode(Url::to(['/content/perma', 'id' => $f['content_id']])) ?>"><?= Html::encode($t('oeffnen')) ?></a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2><?= Html::encode($t('h_support')) ?></h2>
        <p class="nx-tm-leise"><?= Html::encode($t('support_text')) ?></p>
        <?php if ($support === []) : ?>
            <div class="nx-tm-leer"><?= Html::encode($t('keine_support')) ?></div>
        <?php else : ?>
            <ul class="nx-tm-liste">
            <?php foreach ($support as $s) : ?>
                <li>
                    <div class="nx-tm-vorschau">
                        <span class="nx-tm-leise"><?= Html::encode($datum($s['zeit'])) ?></span>
                        <div><?= Html::encode($s['vorschau']) ?></div>
                    </div>
                    <a class="btn btn-sm btn-light" href="<?= Html::encode(Url::to(['/content/perma', 'id' => $s['content_id']])) ?>"><?= Html::encode($t('oeffnen')) ?></a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2><?= Html::encode($t('h_ruhig')) ?></h2>
        <p class="nx-tm-leise"><?= Html::encode($t('ruhig_text')) ?></p>
        <?php if ($ruhige === []) : ?>
            <div class="nx-tm-leer"><?= Html::encode($t('keine_ruhigen')) ?></div>
        <?php else : ?>
            <ul class="nx-tm-liste">
            <?php foreach ($ruhige as $k) : ?>
                <li>
                    <div class="nx-tm-vorschau">
                        <strong><?= Html::encode($k['name']) ?></strong>
                        <span class="nx-tm-leise"> · <?= Html::encode($k['tage'] === null ? $t('nie_etwas') : sprintf($t('vor_tagen'), $k['tage'])) ?></span>
                    </div>
                    <a class="btn btn-sm btn-light" href="<?= Html::encode(Url::to('/s/' . rawurlencode($k['url']))) ?>"><?= Html::encode($t('oeffnen')) ?></a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="nx-tm-hilfe">
            <strong><?= Html::encode($t('h_hilfe')) ?></strong>
            <ol>
                <?php foreach (['hilfe_1', 'hilfe_2', 'hilfe_3', 'hilfe_4', 'hilfe_5'] as $k) : ?>
                    <li><?= Html::encode($t($k)) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
        <p class="nx-tm-hinweis"><i class="fa fa-lock"></i> <?= Html::encode($t('datenschutz')) ?></p>
    </div>
</div>
</div>
