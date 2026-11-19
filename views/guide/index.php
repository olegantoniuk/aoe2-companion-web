<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Article;
use app\models\Elo;

$this->title = 'Guides';

$filterUrl = function ($elo, $category) {
    return Url::to(array_filter(['guide/index', 'elo' => $elo, 'category' => $category]));
};
?>

<div class="admin5-page-header">
    <h1>Guides <span class="badge bg-secondary badge-sm"><?= $total ?></span></h1>
</div>
<p class="kb-lead">What the community keeps telling players at each level, collected from the official forums, Steam discussions, coaching videos and community guides, and sorted by rating. Start with your own band; the habits from the lower ones still matter.</p>

<div class="kb-filters">
    <div class="kb-filter-row">
        <span class="kb-filter-label">Level</span>
        <a href="<?= $filterUrl(null, $currentCategory) ?>" class="kb-chip<?= !$currentElo ? ' is-active' : '' ?>">All</a>
        <?php foreach (Elo::BANDS as $key => [$label, $range]): ?>
            <a href="<?= $filterUrl($key, $currentCategory) ?>" class="kb-chip kb-chip-<?= $key ?><?= $currentElo === $key ? ' is-active' : '' ?>"><?= $label ?> <small><?= Html::encode($range) ?></small></a>
        <?php endforeach; ?>
    </div>
    <div class="kb-filter-row">
        <span class="kb-filter-label">Topic</span>
        <a href="<?= $filterUrl($currentElo, null) ?>" class="kb-chip<?= !$currentCategory ? ' is-active' : '' ?>">All</a>
        <?php foreach (Article::CATEGORIES as $key => $label): ?>
            <a href="<?= $filterUrl($currentElo, $key) ?>" class="kb-chip<?= $currentCategory === $key ? ' is-active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!$byElo): ?>
    <p class="text-muted">No guides match these filters.</p>
<?php endif; ?>

<?php foreach ($byElo as $band => $articles): ?>
    <section class="kb-band">
        <h2 class="kb-band-title">
            <span class="kb-elo kb-elo-<?= $band ?>"><?= Elo::label($band) ?></span>
            <span class="kb-band-range"><?= Html::encode(Elo::range($band)) ?></span>
        </h2>
        <div class="kb-grid">
            <?php foreach ($articles as $a): ?>
                <a href="<?= Url::to(['guide/view', 'slug' => $a->slug]) ?>" class="admin5-card admin5-card-border kb-card">
                    <div class="kb-card-meta">
                        <span class="kb-category"><?= Html::encode($a->categoryLabel) ?></span>
                        <?php if ($a->reading_minutes): ?>
                            <span class="kb-minutes"><?= (int)$a->reading_minutes ?> min read</span>
                        <?php endif; ?>
                        <?php if ($a->listOf('videos')): ?>
                            <span class="kb-has-video" title="Has videos">▶</span>
                        <?php endif; ?>
                    </div>
                    <h3 class="kb-card-title"><?= Html::encode($a->title) ?></h3>
                    <p class="kb-card-summary"><?= Html::encode($a->summary) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
