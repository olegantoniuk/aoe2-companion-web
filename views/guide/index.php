<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\Article;
use app\models\Elo;
use app\components\UnitPills;
use app\components\EloHistogram;
use app\components\Tr;

$this->title = Yii::t('app', 'Guides');
Seo::page($this, Yii::t('app', 'Age of Empires II Guides for Every Elo'), Yii::t('app', 'Guides for Age of Empires II players at every rating: economy, military, scouting, maps, civilization guides and how to climb from one Elo band to the next.'), '/images/icons/guides.png');

$filterUrl = function ($elo, $category, $civ = null) use ($currentCiv) {
    $civ = $civ === false ? null : ($civ ?? ($currentCiv ? $currentCiv->slug : null));
    return Url::to(array_filter(['guide/index', 'elo' => $elo, 'category' => $category, 'civ' => $civ]));
};
?>

<div class="admin5-page-header">
    <div class="page-title">
        <?= UnitPills::pageIcon('guides') ?>
        <h1><?= Yii::t('app', 'Guides') ?> <span class="badge bg-secondary badge-sm"><?= $total ?></span></h1>
    </div>
</div>
<!-- intro and filters on the left, the ladder explorer on the right -->
<div class="row g-4 guides-top">
    <div class="col-lg-7">
        <p class="kb-lead"><?= Yii::t('app', 'What the community keeps telling players at each level, collected from Reddit, the official forums, Steam discussions, coaching videos and community guides, and sorted by rating. Start with your own band; the habits from the lower ones still matter.') ?></p>
        <p class="kb-lead"><?= Yii::t('app', 'Every guide is tagged with the level it helps most, from Beginner (Elo < 900) to Expert (Elo ≥ 1700), and with its topic: economy, military, scouting, maps, civilization strategy and more. The Elo and Progression section explains how the ladder works and what each level is expected to know.') ?></p>
        <p class="kb-lead"><?= Yii::t('app', 'Not sure where to start? Start with the Beginner guides, even if your rating is higher. Most games below 1300 are still decided by villager production, housing and the first fights, and those guides cover exactly that. Then drag the slider on the right to your own rating to see where you stand, how far the next level is and which guides fit you.') ?></p>

        <?= $this->render('//build/_filters', [
    'filterUrl' => $filterUrl,
    'currentElo' => $currentElo,
    'currentCategory' => $currentCategory,
    'currentCiv' => $currentCiv,
    'civs' => $civs,
    'categories' => Article::CATEGORIES,
    'categoryLabel' => Yii::t('app', 'Topic'),
    'route' => 'guide/index',
]) ?>
    </div>
    <div class="col-lg-5">
        <?= $this->render('_elo_explorer') ?>
    </div>
</div>

<?php if ($progression): ?>
    <section class="kb-band kb-progression">
        <div class="kb-band-head">
            <h2 class="kb-section-title"><?= UnitPills::pageIcon('classes') ?><?= Yii::t('app', 'Elo and Progression') ?></h2>
            <p class="kb-section-note"><?= Yii::t('app', 'How the ladder works, what players at each level can do, and what it takes to reach the next one.') ?></p>
        </div>
        <div class="kb-grid">
            <?php foreach ($progression as $a): ?>
                <a href="<?= Url::to(['guide/view', 'slug' => $a->slug]) ?>" class="admin5-card admin5-card-border kb-card">
                    <div class="kb-card-title-row">
                        <img src="<?= Html::encode($a->avatarUrl) ?>" alt="" class="kb-card-avatar">
                        <h3 class="kb-card-title"><?= Html::encode(Tr::text($a->title, 'guides')) ?></h3>
                    </div>
                    <p class="kb-card-summary"><?= Html::encode(Tr::text($a->summary, 'guides')) ?></p>
                    <div class="kb-card-meta">
                        <?= UnitPills::eloPill($a->elo) ?>
                        <?php if ($a->reading_minutes): ?>
                            <span class="kb-minutes"><?= Yii::t('app', '{n} min read', ['n' => (int)$a->reading_minutes]) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (!$byElo && !$progression): ?>
    <p class="text-muted"><?= Yii::t('app', 'No guides match these filters.') ?></p>
<?php endif; ?>

<?php foreach ($byElo as $band => $articles): ?>
    <section class="kb-band">
        <div class="kb-band-head">
            <?= UnitPills::eloPill($band, ['size' => 'lg', 'href' => $filterUrl($band, $currentCategory)]) ?>
            <?= EloHistogram::render($band, ['width' => 260, 'height' => 40]) ?>
        </div>
        <div class="kb-grid">
            <?php foreach ($articles as $a): ?>
                <a href="<?= Url::to(['guide/view', 'slug' => $a->slug]) ?>" class="admin5-card admin5-card-border kb-card">
                    <div class="kb-card-title-row">
                        <img src="<?= Html::encode($a->avatarUrl) ?>" alt="" class="kb-card-avatar">
                        <h3 class="kb-card-title"><?= Html::encode(Tr::text($a->title, 'guides')) ?></h3>
                    </div>
                    <p class="kb-card-summary"><?= Html::encode(Tr::text($a->summary, 'guides')) ?></p>
                    <div class="kb-card-meta">
                        <?= UnitPills::tagPill($a->category, Yii::t('app', $a->categoryLabel)) ?>
                        <?php if ($a->reading_minutes): ?>
                            <span class="kb-minutes"><?= Yii::t('app', '{n} min read', ['n' => (int)$a->reading_minutes]) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
