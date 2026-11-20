<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\Elo;
use app\components\TextFormat;
use app\components\UnitPills;
use app\components\BuildScene;
use app\components\EloHistogram;

$this->title = $build->title;
Seo::page($this, $build->title . ' Build Order', $build->summary ?: ($build->title . ': a step by step Age of Empires II build order.'), (count($build->civModels) === 1 && $build->civ_specific) ? $build->civModels[0]->emblemUrl : '/images/icons/builds.png');

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
    <div class="page-title">
        <?php if (count($build->civModels) === 1 && $build->civ_specific && $build->civModels[0]->emblemUrl): ?>
            <img src="<?= Html::encode($build->civModels[0]->emblemUrl) ?>" alt="" class="unit-title-icon page-title-icon">
        <?php else: ?>
            <?= UnitPills::pageIcon('builds') ?>
        <?php endif; ?>
        <h1><?= Html::encode($build->title) ?></h1>
    </div>
    <div class="kb-article-level">
        <span class="pill-row">
            <?= UnitPills::eloPill($build->elo, ['href' => Url::to(['build/index', 'elo' => $build->elo])]) ?>
            <?= UnitPills::tagPill($build->category, $build->categoryLabel, Url::to(['build/index', 'category' => $build->category])) ?>
            <?php foreach ($build->listOf('map_types') as $m): ?><?= UnitPills::mapPill($m) ?><?php endforeach; ?>
            <?= UnitPills::difficulty($build->difficulty) ?>
        </span>
        <?= EloHistogram::render($build->elo, ['width' => 200, 'height' => 34]) ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <?php $steps = $build->listOf('steps'); $hasScenes = (bool)array_filter($steps, function ($s) { return !empty($s['scene']['actions']); }); ?>
        <div class="unit-tabs build-step-tabs">
            <ul class="nav nav-tabs mb-0" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#steps-short" type="button" role="tab">Steps</button>
                </li>
                <?php if ($hasScenes): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#steps-visual" type="button" role="tab">Visual</button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="steps-short" role="tabpanel">
                <div class="admin5-card admin5-card-border admin5-card-attached">
                    <div class="build-steps-tools">
                        <span>Tick the steps as you play them.</span>
                        <button type="button" class="btn btn-link btn-sm build-steps-reset">Clear the ticks</button>
                    </div>
                    <ol class="build-steps">
                        <?php foreach ($steps as $i => $s): ?>
                            <?php $type = $s['type'] ?? 'note'; $icons = BuildScene::stepIcons($s); ?>
                            <li class="build-step build-step-<?= Html::encode($type) ?>">
                                <label class="build-step-check" title="Done">
                                    <input type="checkbox" data-step="<?= $i ?>">
                                    <span class="build-step-num">Step <?= $i + 1 ?></span>
                                </label>
                                <span class="build-step-pop" title="Population"><?= isset($s['pop']) && $s['pop'] !== null ? (int)$s['pop'] : '' ?></span>
                                <span class="build-step-icons">
                                    <?php foreach ($icons as [$src, $kind]): ?>
                                        <img src="<?= Html::encode($src) ?>" alt="" class="build-step-avatar is-<?= $kind ?>">
                                    <?php endforeach; ?>
                                </span>
                                <span class="build-step-text"><?= UnitPills::linkify(Html::encode($s['text'] ?? ''), 'villager') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
            <?php if ($hasScenes): ?>
                <div class="tab-pane fade" id="steps-visual" role="tabpanel">
                    <div class="admin5-card admin5-card-border admin5-card-attached">
                        <ol class="build-scenes">
                            <?php foreach ($steps as $i => $s): ?>
                                <?php $scene = BuildScene::render($s, $i === 0); ?>
                                <li class="build-scene-step build-step-<?= Html::encode($s['type'] ?? 'note') ?>">
                                    <div class="build-scene-head">
                                        <span class="build-step-num">Step <?= $i + 1 ?></span>
                                        <?php if (isset($s['pop']) && $s['pop'] !== null): ?>
                                            <span class="build-scene-pop"><?= (int)$s['pop'] ?> pop</span>
                                        <?php endif; ?>
                                    </div>
                                    <?= $scene ?>
                                    <p class="build-scene-text"><?= UnitPills::linkify(Html::encode($s['text'] ?? ''), 'villager') ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            <?php endif; ?>
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
                <div class="kb-card-meta"><?= UnitPills::eloPill($b->elo) ?></div>
                <h2 class="kb-card-title"><?= Html::encode($b->shortTitle) ?></h2>
                <p class="kb-card-summary"><?= Html::encode($b->summary) ?></p>
                <?php if ($b->ageUpPop): ?>
                    <div class="kb-card-foot"><span class="kb-pop">Click up at <strong><?= $b->ageUpPop ?></strong> pop</span></div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php UnitPills::registerTips($this); ?>
<?php if ($hasScenes) $this->registerJsFile('/js/build-scenes.js?v=' . filemtime(Yii::getAlias('@webroot') . '/js/build-scenes.js'), ['defer' => true]); ?>

<script>
(function () {
    var key = 'build-ticks:<?= Html::encode($build->slug) ?>';
    var boxes = document.querySelectorAll('.build-step-check input');
    var saved = [];
    try { saved = JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) {}
    boxes.forEach(function (b) {
        var li = b.closest('.build-step');
        if (saved.indexOf(+b.dataset.step) !== -1) { b.checked = true; li.classList.add('is-done'); }
        b.addEventListener('change', function () {
            li.classList.toggle('is-done', b.checked);
            var done = [];
            boxes.forEach(function (x) { if (x.checked) done.push(+x.dataset.step); });
            try { localStorage.setItem(key, JSON.stringify(done)); } catch (e) {}
        });
    });
    var reset = document.querySelector('.build-steps-reset');
    if (reset) reset.addEventListener('click', function () {
        boxes.forEach(function (b) { b.checked = false; b.closest('.build-step').classList.remove('is-done'); });
        try { localStorage.removeItem(key); } catch (e) {}
    });
})();
</script>
