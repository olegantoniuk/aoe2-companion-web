<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\BuildOrder;
use app\models\Elo;
use app\components\UnitPills;

$this->title = 'Build Orders';

$filterUrl = function ($elo, $category) {
    return Url::to(array_filter(['build/index', 'elo' => $elo, 'category' => $category]));
};
?>

<div class="admin5-page-header">
    <h1>Build Orders <span class="badge bg-secondary badge-sm"><?= count($builds) ?></span></h1>
</div>
<p class="kb-lead">The openings most players use on the ladder, step by step from the first villager to the age up. Pick one that fits your level, learn it until it runs without thinking, then add the next.</p>

<div class="kb-filters">
    <div class="kb-filter-row">
        <span class="kb-filter-label">Level</span>
        <a href="<?= $filterUrl(null, $currentCategory) ?>" class="kb-chip<?= !$currentElo ? ' is-active' : '' ?>">All</a>
        <?php foreach (Elo::BANDS as $key => [$label, $range]): ?>
            <a href="<?= $filterUrl($key, $currentCategory) ?>" class="kb-chip kb-chip-<?= $key ?><?= $currentElo === $key ? ' is-active' : '' ?>"><?= $label ?> <small><?= Html::encode($range) ?></small></a>
        <?php endforeach; ?>
    </div>
    <div class="kb-filter-row">
        <span class="kb-filter-label">Style</span>
        <a href="<?= $filterUrl($currentElo, null) ?>" class="kb-chip<?= !$currentCategory ? ' is-active' : '' ?>">All</a>
        <?php foreach (BuildOrder::CATEGORIES as $key => $label): ?>
            <a href="<?= $filterUrl($currentElo, $key) ?>" class="kb-chip<?= $currentCategory === $key ? ' is-active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$builds): ?>
    <p class="text-muted">No build orders match these filters.</p>
<?php endif; ?>

<div class="kb-grid">
    <?php foreach ($builds as $b): ?>
        <a href="<?= Url::to(['build/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border kb-card">
            <div class="kb-card-meta">
                <span class="kb-elo kb-elo-<?= Html::encode($b->elo) ?>"><?= Elo::label($b->elo) ?></span>
                <span class="kb-category"><?= Html::encode($b->categoryLabel) ?></span>
                <span class="kb-difficulty" title="Difficulty"><?= str_repeat('●', (int)$b->difficulty) . str_repeat('○', max(0, 3 - (int)$b->difficulty)) ?></span>
            </div>
            <h2 class="kb-card-title"><?= Html::encode($b->title) ?></h2>
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
