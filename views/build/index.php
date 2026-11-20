<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\BuildOrder;
use app\models\Elo;
use app\components\UnitPills;

$this->title = 'Build Orders';
Seo::page($this, 'Build Orders for Every Level and Civilization', 'Step by step Age of Empires II build orders from the first villager to the age up: scouts, archers, fast castle, drush and the signature openings of every civilization.', '/images/icons/builds.png');

$filterUrl = function ($elo, $category, $civ = null) use ($currentCiv) {
    $civ = $civ === false ? null : ($civ ?? ($currentCiv ? $currentCiv->slug : null));
    return Url::to(array_filter(['build/index', 'elo' => $elo, 'category' => $category, 'civ' => $civ]));
};
?>

<div class="admin5-page-header">
    <div class="page-title">
        <?= UnitPills::pageIcon('builds') ?>
        <h1>Build Orders <span class="badge bg-secondary badge-sm"><?= count($builds) ?></span></h1>
    </div>
</div>
<p class="kb-lead">The openings most players use on the ladder, step by step from the first villager to the age up, plus the signature builds of each civilization. Pick one that fits your level, learn it until it runs without thinking, then add the next.</p>

<?= $this->render('_filters', [
    'filterUrl' => $filterUrl,
    'currentElo' => $currentElo,
    'currentCategory' => $currentCategory,
    'currentCiv' => $currentCiv,
    'civs' => $civs,
    'categories' => BuildOrder::CATEGORIES,
    'categoryLabel' => 'Style',
]) ?>

<?php if (!$builds): ?>
    <p class="text-muted">No build orders match these filters.</p>
<?php endif; ?>

<div class="kb-grid">
    <?php foreach ($builds as $b): ?>
        <a href="<?= Url::to(['build/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border kb-card">
            <h2 class="kb-card-title"><?= Html::encode($b->shortTitle) ?></h2>
            <div class="pill-row">
                <?= UnitPills::eloPill($b->elo) ?>
                <?= UnitPills::tagPill($b->category, $b->categoryLabel) ?>
                <?php foreach ($b->listOf('map_types') as $m): ?><?= UnitPills::mapPill($m) ?><?php endforeach; ?>
                <?= UnitPills::difficulty($b->difficulty) ?>
            </div>
            <p class="kb-card-summary"><?= Html::encode($b->summary) ?></p>
            <div class="kb-card-foot">
                <?php if ($b->ageUpPop): ?>
                    <span class="kb-pop">Click up at <strong><?= $b->ageUpPop ?></strong> pop</span>
                <?php endif; ?>
                <span class="kb-civs">
                    <?php foreach (array_slice($b->civModels, 0, 5) as $civ): ?>
                        <?php if ($civ->emblemUrl): ?><img src="<?= Html::encode($civ->emblemUrl) ?>" alt="<?= Html::encode($civ->name) ?>" title="<?= Html::encode($civ->name) ?>"><?php endif; ?>
                    <?php endforeach; ?>
                </span>
            </div>
        </a>
    <?php endforeach; ?>
</div>
