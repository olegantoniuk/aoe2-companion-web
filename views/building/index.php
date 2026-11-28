<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\Building;
use app\components\UnitPills;
use app\components\Tr;
use app\components\UnitLine;

$this->title = Yii::t('app', 'Buildings');
Seo::page($this, Yii::t('app', 'All Buildings in Age of Empires II'), Yii::t('app', 'Every building in Age of Empires II with its cost, hit points, armor, the units it trains and the technologies researched there.'), '/images/icons/buildings.png');
?>

<div class="admin5-page-header">
    <div class="page-title">
        <?= UnitPills::pageIcon('buildings') ?>
        <h1><?= Yii::t('app', 'Buildings') ?> <span class="badge bg-secondary badge-sm" data-live-count><?= $total ?></span></h1>
    </div>
</div>

<?= $this->render('@app/views/site/_live_filter', [
    'target' => '#building-list',
    'placeholder' => Yii::t('app', 'Building name...'),
]) ?>

<div id="building-list">

<?php foreach ($byCategory as $cat => $buildings): ?>
    <section class="civ-group" data-filter-group>
        <h2 class="civ-group-title"><?= Html::encode(Yii::t('app', Building::CATEGORIES[$cat] ?? ucfirst($cat))) ?></h2>
        <div class="building-grid">
            <?php foreach ($buildings as $b): ?>
                <a href="<?= Url::to(['building/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border building-card" data-filter-item data-q="<?= Html::encode(Tr::short($b->name) . ' ' . $b->name) ?>" data-age="<?= Html::encode($b->age ? UnitLine::age($b->age) : '') ?>">
                    <span class="building-card-icon">
                        <?php if ($b->iconUrl): ?><img src="<?= Html::encode($b->iconUrl) ?>" alt=""><?php endif; ?>
                    </span>
                    <span class="building-card-text">
                        <span class="building-card-name"><?= Tr::nameHtml($b->name) ?></span>
                        <span class="building-card-meta">
                            <?php if ($b->age): ?><?= UnitPills::ageLabel($b->age) ?><?php endif; ?>
                            <span class="unit-list-cost"><?= $b->costHtml ?></span>
                        </span>
                        <?php [$mode, $names] = $b->availabilityNote; ?>
                        <?php $civList = implode(', ', array_map([Tr::class, 'name'], (array)$names)); ?>
                        <?php if ($mode === 'only'): ?>
                            <span class="building-card-civs"><?= Html::encode(count($names) > 4 ? count($names) . ' ' . Tr::plural(count($names), 'civilization|civilizations') : Yii::t('app', '{civs} only', ['civs' => $civList])) ?></span>
                        <?php elseif ($mode === 'except'): ?>
                            <span class="building-card-civs"><?= Html::encode(count($names) > 4 ? Yii::t('app', 'All except {n} civilizations', ['n' => count($names)]) : Yii::t('app', 'All except {civs}', ['civs' => $civList])) ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
<p class="text-center text-muted py-4" data-live-empty hidden><?= Yii::t('app', 'No buildings found.') ?></p>
</div>
