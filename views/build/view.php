<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Elo;
use app\components\TextFormat;
use app\components\UnitPills;

$this->title = $build->title;

$stepIcons = [
    'villager' => '/images/units/villager_icon.png',
    'build' => '/images/buildings/House.png',
    'research' => '/images/techs/Loom.png',
    'age' => '/images/ages/FeudalAge.png',
    'military' => '/images/units/scout_cavalry_icon.png',
];
$stepIcon = function ($type) use ($stepIcons) {
    $f = $stepIcons[$type] ?? null;
    return $f && is_file(Yii::getAlias('@webroot') . $f) ? $f : null;
};
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a('Build Orders', ['build/index']) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($build->title) ?></li>
    </ol>
</nav>

<div class="admin5-page-header kb-head">
    <h1><?= Html::encode($build->title) ?></h1>
    <div class="kb-card-meta">
        <a href="<?= Url::to(['build/index', 'elo' => $build->elo]) ?>" class="kb-elo kb-elo-<?= Html::encode($build->elo) ?>"><?= Elo::label($build->elo) ?></a>
        <span class="kb-elo-range"><?= Html::encode(Elo::range($build->elo)) ?></span>
        <a href="<?= Url::to(['build/index', 'category' => $build->category]) ?>" class="kb-category"><?= Html::encode($build->categoryLabel) ?></a>
        <span class="kb-difficulty" title="Difficulty"><?= str_repeat('●', (int)$build->difficulty) . str_repeat('○', max(0, 3 - (int)$build->difficulty)) ?></span>
        <?php foreach ($build->listOf('map_types') as $m): ?>
            <span class="kb-category"><?= Html::encode(ucfirst($m)) ?> maps</span>
        <?php endforeach; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="admin5-card admin5-card-border">
            <div class="card-header"><strong>Steps</strong></div>
            <ol class="build-steps">
                <?php foreach ($build->listOf('steps') as $s): ?>
                    <?php $type = $s['type'] ?? 'note'; $icon = $stepIcon($type); ?>
                    <li class="build-step build-step-<?= Html::encode($type) ?>">
                        <span class="build-step-pop"><?= isset($s['pop']) && $s['pop'] !== null ? (int)$s['pop'] : '' ?></span>
                        <span class="build-step-icon"><?php if ($icon): ?><img src="<?= $icon ?>" alt=""><?php endif; ?></span>
                        <span class="build-step-text"><?= UnitPills::linkify(Html::encode($s['text'] ?? ''), 'villager') ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="kb-prose">
            <?= TextFormat::render($build->description, null, 'h3') ?>
        </div>
        <?php if ($build->after_feudal): ?>
            <h3 class="kb-side-title">After the age up</h3>
            <div class="kb-prose"><?= TextFormat::render($build->after_feudal, null, 'h4') ?></div>
        <?php endif; ?>
        <?php if ($build->listOf('tips')): ?>
            <h3 class="kb-side-title">Tips</h3>
            <ul class="kb-tips">
                <?php foreach ($build->listOf('tips') as $t): ?>
                    <li><?= UnitPills::linkify(Html::encode($t)) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($build->beaten_by): ?>
            <h3 class="kb-side-title">What beats it</h3>
            <div class="kb-prose"><?= TextFormat::render($build->beaten_by, null, 'h4') ?></div>
        <?php endif; ?>
        <?php if ($build->civModels): ?>
            <h3 class="kb-side-title">Good civilizations for it</h3>
            <div class="pill-row">
                <?php foreach ($build->civModels as $civ): ?>
                    <?= UnitPills::civPill($civ) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->render('//guide/_media', ['videos' => $build->listOf('videos'), 'sources' => $build->listOf('sources')]) ?>

<?php if ($related): ?>
    <h2 class="unit-section-title">More <?= Html::encode(strtolower($build->categoryLabel)) ?> builds</h2>
    <div class="kb-grid">
        <?php foreach ($related as $b): ?>
            <a href="<?= Url::to(['build/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border kb-card">
                <div class="kb-card-meta"><span class="kb-elo kb-elo-<?= Html::encode($b->elo) ?>"><?= Elo::label($b->elo) ?></span></div>
                <h2 class="kb-card-title"><?= Html::encode($b->title) ?></h2>
                <p class="kb-card-summary"><?= Html::encode($b->summary) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php UnitPills::registerTips($this); ?>
