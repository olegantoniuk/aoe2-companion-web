<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\Elo;
use app\components\TextFormat;
use app\components\UnitPills;
use app\components\BuildScene;
use app\components\EloHistogram;
use app\components\Tr;

$title = Tr::text($build->title, 'builds');
$summary = Tr::text($build->summary, 'builds');
$this->title = $title;
Seo::page($this, Yii::t('app', '{title} Build Order', ['title' => $title]), $summary ?: Yii::t('app', '{title}: a step by step Age of Empires II build order.', ['title' => $title]),(count($build->civModels) === 1 && $build->civ_specific) ? $build->civModels[0]->emblemUrl : '/images/icons/builds.png');

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

// villagers on each resource after a step, shown when the reader turns the columns on
$vills = function ($s) {
    $v = $s['vills'] ?? null;
    if (!is_array($v)) return '';
    $approx = !empty($v['approx']);
    $h = '<span class="build-step-vills' . ($approx ? ' is-approx' : '') . '"'
        . ($approx ? ' title="' . Html::encode(Yii::t('app', 'An estimate: the step does not give exact numbers')) . '"' : '') . '>';
    $on = [
        'food' => '{n} on food',
        'wood' => '{n} on wood',
        'gold' => '{n} on gold',
        'stone' => '{n} on stone',
    ];
    foreach (['food' => 'Food', 'wood' => 'Wood', 'gold' => 'Gold', 'stone' => 'Stone'] as $k => $res) {
        $n = (int)($v[$k] ?? 0);
        $h .= '<span class="build-vill' . ($n ? '' : ' is-zero') . '" aria-label="' . Html::encode(Yii::t('app', $on[$k], ['n' => $n])) . '">'
            . '<img src="/images/resources/' . $res . '.png" alt="" class="res-icon">' . $n . '</span>';
    }
    return $h . '</span>';
};
?>

<nav aria-label="<?= Html::encode(Yii::t('app', 'breadcrumb')) ?>" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a(Yii::t('app', 'Build Orders'), ['build/index']) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($title) ?></li>
    </ol>
</nav>

<div class="admin5-page-header kb-head">
    <div class="page-title">
        <?php if (count($build->civModels) === 1 && $build->civ_specific && $build->civModels[0]->emblemUrl): ?>
            <img src="<?= Html::encode($build->civModels[0]->emblemUrl) ?>" alt="" class="unit-title-icon page-title-icon">
        <?php else: ?>
            <?= UnitPills::pageIcon('builds') ?>
        <?php endif; ?>
        <h1><?= Html::encode($title) ?></h1>
    </div>
    <div class="kb-article-level">
        <span class="pill-row">
            <?= UnitPills::eloPill($build->elo, ['href' => Url::to(['build/index', 'elo' => $build->elo])]) ?>
            <?= UnitPills::tagPill($build->category, Yii::t('app', $build->categoryLabel),Url::to(['build/index', 'category' => $build->category])) ?>
            <?php foreach ($build->listOf('map_types') as $m): ?><?= UnitPills::mapPill($m) ?><?php endforeach; ?>
            <?= UnitPills::difficulty($build->difficulty) ?>
        </span>
        <?= EloHistogram::render($build->elo, ['width' => 200, 'height' => 34]) ?>
    </div>
</div>

<?php
$steps = $build->listOf('steps');
$hasScenes = (bool)array_filter($steps, function ($s) { return !empty($s['scene']['actions']); });
$hasVills = (bool)array_filter($steps, function ($s) { return !empty($s['vills']); });
?>
<div class="row g-4 build-layout">
    <div class="col-lg-7 build-main">
        <div class="unit-tabs build-step-tabs">
            <ul class="nav nav-tabs mb-0" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#steps-short" type="button" role="tab"><?= Yii::t('app', 'Steps') ?></button>
                </li>
                <?php if ($hasScenes): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#steps-visual" type="button" role="tab"><?= Yii::t('app', 'Visual') ?></button>
                    </li>
                <?php endif; ?>
                <?php if ($hasVills): ?>
                    <li class="build-vills-toggle">
                        <label class="build-step-check" title="<?= Html::encode(Yii::t('app', 'Show how many villagers work on food, wood, gold and stone after each step')) ?>">
                            <input type="checkbox" id="build-vills-toggle">
                            <span><?= Yii::t('app', 'Villagers on each resource') ?></span>
                        </label>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="steps-short" role="tabpanel">
                <div class="admin5-card admin5-card-border admin5-card-attached">
                    <div class="build-steps-tools">
                        <span><?= Yii::t('app', 'Tick the steps as you play them.') ?><span class="build-vills-note"> <?= Yii::t('app', 'Villagers are counted after each step.') ?></span></span>
                        <button type="button" class="btn btn-link btn-sm build-steps-reset"><?= Yii::t('app', 'Clear the ticks') ?></button>
                    </div>
                    <ol class="build-steps">
                        <?php foreach ($steps as $i => $s): ?>
                            <?php $type = $s['type'] ?? 'note'; $icons = BuildScene::stepIcons($s); ?>
                            <li class="build-step build-step-<?= Html::encode($type) ?>">
                                <label class="build-step-check" title="<?= Html::encode(Yii::t('app', 'Done')) ?>">
                                    <input type="checkbox" data-step="<?= $i ?>">
                                    <span class="build-step-num"><?= Yii::t('app', 'Step {n}', ['n' => $i + 1]) ?></span>
                                </label>
                                <span class="build-step-pop" title="<?= Html::encode(Yii::t('app', 'Population')) ?>"><?= isset($s['pop']) && $s['pop'] !== null ? (int)$s['pop'] : '' ?></span>
                                <span class="build-step-icons">
                                    <?php foreach ($icons as [$src, $kind]): ?>
                                        <img src="<?= Html::encode($src) ?>" alt="" class="build-step-avatar is-<?= $kind ?>">
                                    <?php endforeach; ?>
                                </span>
                                <span class="build-step-text"><?= UnitPills::linkify(Html::encode(Tr::text($s['text'] ?? '', 'builds')), 'villager') ?></span>
                                <?= $vills($s) ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
            <?php if ($hasScenes): ?>
                <div class="tab-pane fade" id="steps-visual" role="tabpanel">
                    <div class="admin5-card admin5-card-border admin5-card-attached">
                        <div class="build-steps-tools">
                            <span><?= Yii::t('app', 'Tick the steps as you play them.') ?><span class="build-vills-note"> <?= Yii::t('app', 'Villagers are counted after each step.') ?></span></span>
                            <button type="button" class="btn btn-link btn-sm build-steps-reset"><?= Yii::t('app', 'Clear the ticks') ?></button>
                        </div>
                        <ol class="build-scenes">
                            <?php foreach ($steps as $i => $s): ?>
                                <?php $scene = BuildScene::render($s, $i === 0); ?>
                                <li class="build-scene-step build-step-<?= Html::encode($s['type'] ?? 'note') ?>">
                                    <div class="build-scene-head">
                                        <label class="build-step-check" title="<?= Html::encode(Yii::t('app', 'Done')) ?>">
                                            <input type="checkbox" data-step="<?= $i ?>">
                                            <span class="build-step-num"><?= Yii::t('app', 'Step {n}', ['n' => $i + 1]) ?></span>
                                        </label>
                                        <?php if (isset($s['pop']) && $s['pop'] !== null): ?>
                                            <span class="build-scene-pop"><?= Yii::t('app', '{n} pop', ['n' => (int)$s['pop']]) ?></span>
                                        <?php endif; ?>
                                        <?= $vills($s) ?>
                                    </div>
                                    <?= $scene ?>
                                    <p class="build-scene-text"><?= UnitPills::linkify(Html::encode(Tr::text($s['text'] ?? '', 'builds')), 'villager') ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5 build-side">
        <div class="kb-prose">
            <?= TextFormat::render(Tr::text($build->description, 'builds'), null, 'h3') ?>
        </div>
        <?php if ($build->after_feudal): ?>
            <h3 class="kb-side-title"><?= Yii::t('app', 'After the age up') ?></h3>
            <div class="kb-prose"><?= TextFormat::render(Tr::text($build->after_feudal, 'builds'), null, 'h4') ?></div>
        <?php endif; ?>
        <?php if ($build->listOf('tips')): ?>
            <h3 class="kb-side-title"><?= Yii::t('app', 'Tips') ?></h3>
            <ul class="kb-tips">
                <?php foreach (Tr::texts($build->listOf('tips'), 'builds') as $t): ?>
                    <li><?= UnitPills::linkify(Html::encode($t)) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($build->beaten_by): ?>
            <h3 class="kb-side-title"><?= Yii::t('app', 'What beats it') ?></h3>
            <div class="kb-prose"><?= TextFormat::render(Tr::text($build->beaten_by, 'builds'), null, 'h4') ?></div>
        <?php endif; ?>
        <?php if ($build->civModels): ?>
            <h3 class="kb-side-title"><?= Yii::t('app', 'Good civilizations for it') ?></h3>
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
    <h2 class="unit-section-title"><?= Html::encode(Yii::t('app', 'More {category} builds', ['category' => Tr::isEnglish() ? strtolower($build->categoryLabel) : Yii::t('app', $build->categoryLabel)])) ?></h2>
    <div class="kb-grid">
        <?php foreach ($related as $b): ?>
            <a href="<?= Url::to(['build/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border kb-card">
                <div class="kb-card-meta"><?= UnitPills::eloPill($b->elo) ?></div>
                <h2 class="kb-card-title"><?= Html::encode($b->shortTitle) ?></h2>
                <p class="kb-card-summary"><?= Html::encode(Tr::text($b->summary, 'builds')) ?></p>
                <?php if ($b->ageUpPop): ?>
                    <div class="kb-card-foot"><span class="kb-pop"><?= Yii::t('app', 'Click up at {pop} pop', ['pop' => '<strong>' . $b->ageUpPop . '</strong>']) ?></span></div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php UnitPills::registerTips($this); ?>
<?php if ($hasScenes) $this->registerJsFile('/js/build-scenes.js?v=' . filemtime(Yii::getAlias('@webroot') . '/js/build-scenes.js'), ['defer' => true]); ?>

<script>
(function () {
    // ticks: each step has a box in Steps and one in Visual, both show the same state
    var key = 'build-ticks:<?= Html::encode($build->slug) ?>';
    var boxes = [].slice.call(document.querySelectorAll('.build-step-check input[data-step]'));
    var rowOf = function (b) { return b.closest('.build-step, .build-scene-step'); };
    var mark = function (step, on) {
        boxes.forEach(function (b) {
            if (+b.dataset.step !== step) return;
            b.checked = on;
            rowOf(b).classList.toggle('is-done', on);
        });
    };
    var save = function () {
        var done = [];
        boxes.forEach(function (b) { if (b.checked && done.indexOf(+b.dataset.step) === -1) done.push(+b.dataset.step); });
        try { localStorage.setItem(key, JSON.stringify(done)); } catch (e) {}
    };
    var saved = [];
    try { saved = JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) {}
    saved.forEach(function (step) { mark(step, true); });
    boxes.forEach(function (b) {
        b.addEventListener('change', function () { mark(+b.dataset.step, b.checked); save(); });
        // a click anywhere on the row ticks it too, except on links and on the box itself
        rowOf(b).addEventListener('click', function (e) {
            if (e.target.closest('a, label, input, button')) return;
            if (window.getSelection && String(window.getSelection())) return;
            mark(+b.dataset.step, !b.checked);
            save();
        });
    });
    document.querySelectorAll('.build-steps-reset').forEach(function (reset) {
        reset.addEventListener('click', function () {
            boxes.forEach(function (b) { mark(+b.dataset.step, false); });
            try { localStorage.removeItem(key); } catch (e) {}
        });
    });

    // villager columns: the steps take the whole width and the text on the right is hidden
    var toggle = document.getElementById('build-vills-toggle');
    var layout = document.querySelector('.build-layout');
    if (toggle && layout) {
        var vkey = 'build-vills';
        var set = function (on) {
            layout.classList.toggle('is-vills', on);
            toggle.checked = on;
        };
        try { set(localStorage.getItem(vkey) === '1'); } catch (e) {}
        toggle.addEventListener('change', function () {
            set(toggle.checked);
            try { localStorage.setItem(vkey, toggle.checked ? '1' : '0'); } catch (e) {}
        });
    }
})();
</script>
