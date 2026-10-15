<?php
/**
 * Kreis-Uebersicht mit Gruppen-Ueberschriften (Theme-Ueberschreibung von
 * modules/space/views/spaces/index.php).
 *
 * Die Gruppe ergibt sich aus der Sortierzahl (sort_order) des Kreises -- die gibt es ohnehin,
 * damit die Reihenfolge fest ist. Ein neuer Kreis landet von selbst in der passenden Gruppe.
 * Ueberschriften erscheinen nur in der Standard-Sortierung ("sortOrder"); wer nach Name oder
 * Datum sortiert, sieht die normale Kachelreihe.
 *
 * Grenzen anpassen: $gruppen unten (ab welcher Sortierzahl beginnt welche Gruppe).
 */

use humhub\assets\CardsAsset;
use humhub\helpers\Html;
use humhub\helpers\ThemeHelper;
use humhub\modules\space\components\SpaceDirectoryQuery;
use humhub\modules\space\widgets\SpaceDirectoryCard;
use humhub\modules\space\widgets\SpaceDirectoryFilters;
use yii\web\View;

/* @var $this View */
/* @var $spaces SpaceDirectoryQuery */

CardsAsset::register($this);

// [ab Sortierzahl => Ueberschrift], aufsteigend.
$gruppen = [
    0 => 'Ankommen & Orientieren',
    10 => 'Arbeitskreise',
    110 => 'Themen',
    200 => 'Vor Ort',
];

$gruppeVon = static function (int $sortOrder) use ($gruppen): string {
    $name = '';
    foreach ($gruppen as $ab => $titel) {
        if ($sortOrder >= $ab) {
            $name = $titel;
        }
    }
    return $name;
};
$mitGruppen = SpaceDirectoryFilters::getValue('sort') === 'sortOrder';
$letzteGruppe = null;
?>
<style>
    .nexus-kreis-gruppe {
        width: 100%;
        margin: 1.6rem 0 .4rem;
        padding: 0 .75rem .4rem;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        border-bottom: 2px solid #D4AF37;
    }
    .nexus-kreis-gruppe:first-child { margin-top: .4rem; }
</style>
<div class="panel panel-default">

    <div class="panel-heading">
        <?= Yii::t('SpaceModule.base', '<strong>Spaces</strong>'); ?>
    </div>

    <div class="panel-body">
        <?= SpaceDirectoryFilters::widget(); ?>
    </div>

</div>

<div class="container<?= ThemeHelper::isFluid() ? '-fluid' : '' ?> gx-0 overflow-x-hidden">
    <div class="row cards">
        <?php if (!$spaces->exists()): ?>
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <strong><?= Yii::t('SpaceModule.base', 'No results found!'); ?></strong><br/>
                        <?= Yii::t('SpaceModule.base', 'Try other keywords or remove filters.'); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($spaces->with('contentContainerRecord')->all() as $space) : ?>
            <?php if ($mitGruppen) : ?>
                <?php $gruppe = $gruppeVon((int)$space->sort_order); ?>
                <?php if ($gruppe !== '' && $gruppe !== $letzteGruppe) : ?>
                    <div class="col-12 nexus-kreis-gruppe"><?= Html::encode($gruppe) ?></div>
                    <?php $letzteGruppe = $gruppe; ?>
                <?php endif; ?>
            <?php endif; ?>
            <?= SpaceDirectoryCard::widget(['space' => $space]); ?>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$spaces->isLastPage()) : ?>
    <?= Html::tag('div', '', [
        'class' => 'cards-end',
        'data-current-page' => $spaces->pagination->getPage() + 1,
        'data-total-pages' => $spaces->pagination->getPageCount(),
    ]) ?>
<?php endif; ?>
