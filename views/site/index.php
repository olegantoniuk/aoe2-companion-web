<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\Elo;
use app\components\UnitPills;
use app\components\Tr;

$this->title = Yii::t('app', 'Home');
Seo::page($this, null, Yii::t('app', 'Every Age of Empires II civilization, unit, building and technology, with counters, matchups, build orders and guides for each Elo level.'));
// the lead opens with a drawn initial "A"; a translation that starts otherwise is shown as plain text
$lead = Yii::t('app', 'A reference for Age of Empires II: Definitive Edition. Every civilization comes with its bonuses, team bonus and unique units. Every unit has its stats in each age and after each upgrade, what it is strong and weak against, and a short guide on how to use it.');
?>

<section class="home-hero">
    <div class="home-intro">
        <h1 class="home-title" data-strokes="/images/logo/wordmark-strokes.png?v=<?= filemtime(Yii::getAlias('@webroot') . '/images/logo/wordmark-strokes.png') ?>"><?= file_get_contents(__DIR__ . '/_wordmark.svg') ?></h1>
        <?php if (strncmp($lead, 'A ', 2) === 0): ?>
        <p class="home-lead"><img src="/images/brand/initial-a.webp?v=<?= filemtime(Yii::getAlias('@webroot') . '/images/brand/initial-a.webp') ?>" alt="A" class="home-initial" width="160" height="160"><?= Html::encode(substr($lead, 1)) ?></p>
        <?php else: ?>
        <p class="home-lead"><?= Html::encode($lead) ?></p>
        <?php endif; ?>
        <p><?= Yii::t('app', 'The Matchup page takes two civilizations and shows which of your units answer each unit the other side can build.') ?></p>
        <p><?= Yii::t('app', 'New to the game or stuck at a rating? The {builds} and {guides} are sorted by Elo, from your first ladder games to the top of it.', [
            'builds' => Html::a(Yii::t('app', 'build orders'), ['build/index']),
            'guides' => Html::a(Yii::t('app', 'guides'), ['guide/index']),
        ]) ?></p>
    </div>
    <?php if (is_file(Yii::getAlias('@webroot') . '/images/hero/hero.webp')): ?>
        <div class="home-hero-art">
            <img src="/images/hero/hero.webp?v=<?= filemtime(Yii::getAlias('@webroot') . '/images/hero/hero.webp') ?>" alt="<?= Html::encode(Yii::t('app', 'Castles, knights, samurai, war elephants and villagers of the Age of Empires II world')) ?>">
            <canvas class="hero-fx" aria-hidden="true" data-flags="/images/hero/flags.png?v=<?= filemtime(Yii::getAlias('@webroot') . '/images/hero/flags.png') ?>"></canvas>
        </div>
    <?php endif; ?>
</section>

<div class="row mb-4 g-3">
    <?php foreach ([
        [$civCount, Yii::t('app', 'Civilizations'), ['civilization/index'], 'civilizations'],
        [$unitCount, Yii::t('app', 'Units'), ['unit/index'], 'units'],
        [$techCount, Yii::t('app', 'Technologies'), ['technology/index'], 'technologies'],
        [$buildingCount, Yii::t('app', 'Buildings'), ['building/index'], 'buildings'],
    ] as [$count, $label, $route, $icon]): ?>
        <div class="col-6 col-md-3">
            <a href="<?= Url::to($route) ?>" class="admin5-card admin5-card-border text-center home-stat">
                <img src="/images/icons/<?= $icon ?>.png" alt="" class="home-stat-icon">
                <span class="home-stat-count"><?= $count ?></span>
                <span class="home-stat-label"><?= $label ?></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<!-- knowledge base -->
<div class="row g-4 home-section">
    <div class="col-lg-6">
        <div class="home-section-head">
            <h2><?= Yii::t('app', 'Build Orders') ?></h2>
            <a href="<?= Url::to(['build/index']) ?>" class="home-more"><?= Yii::t('app', 'All build orders') ?><?= UnitPills::chevron() ?></a>
        </div>
        <div class="home-kb-list">
            <?php foreach ($builds as $b): ?>
                <a href="<?= Url::to(['build/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border home-kb-item">
                    <img src="<?= Html::encode(UnitPills::pageIconUrl('builds')) ?>" alt="" class="home-kb-avatar">
                    <span class="home-kb-main">
                        <span class="home-kb-title"><?= Html::encode(Tr::text($b->title, 'builds')) ?></span>
                        <span class="pill-row">
                            <?= UnitPills::eloPill($b->elo) ?>
                            <?= UnitPills::tagPill($b->category, $b->categoryLabel) ?>
                        </span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="home-section-head">
            <h2><?= Yii::t('app', 'Guides') ?></h2>
            <a href="<?= Url::to(['guide/index']) ?>" class="home-more"><?= Yii::t('app', 'All guides') ?><?= UnitPills::chevron() ?></a>
        </div>
        <div class="home-kb-list">
            <?php foreach ($guides as $g): ?>
                <a href="<?= Url::to(['guide/view', 'slug' => $g->slug]) ?>" class="admin5-card admin5-card-border home-kb-item">
                    <img src="<?= Html::encode($g->avatarUrl) ?>" alt="" class="home-kb-avatar">
                    <span class="home-kb-main">
                        <span class="home-kb-title"><?= Html::encode(Tr::text($g->title, 'guides')) ?></span>
                        <span class="pill-row">
                            <?= UnitPills::eloPill($g->elo) ?>
                            <?= UnitPills::tagPill($g->category, $g->categoryLabel) ?>
                        </span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- civilizations -->
<section class="home-section">
    <div class="home-section-head">
        <h2><?= Yii::t('app', 'Civilizations') ?></h2>
        <a href="<?= Url::to(['civilization/index']) ?>" class="home-more"><?= Yii::t('app', 'View all civilizations') ?><?= UnitPills::chevron() ?></a>
    </div>
    <div class="row g-2">
        <?php foreach ($recentCivs as $civ): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['civilization/view', 'slug' => $civ->slug]) ?>" class="admin5-card admin5-card-border home-tile home-tile-civ">
                    <?php if ($civ->emblemUrl): ?>
                        <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="home-civ-emblem">
                    <?php endif; ?>
                    <span>
                        <span class="home-tile-name"><?= Tr::nameHtml($civ->name) ?></span>
                        <span class="home-tile-meta"><?= implode(', ', array_map([Tr::class, 'nameHtml'], $civ->focusList)) ?></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- units -->
<section class="home-section">
    <div class="home-section-head">
        <h2><?= Yii::t('app', 'Units') ?></h2>
        <a href="<?= Url::to(['unit/index']) ?>" class="home-more"><?= Yii::t('app', 'View all units') ?><?= UnitPills::chevron() ?></a>
    </div>
    <div class="row g-2">
        <?php foreach ($units as $u): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['unit/view', 'slug' => $u->slug]) ?>" class="admin5-card admin5-card-border home-tile">
                    <?= UnitPills::avatar($u->iconUrl) ?>
                    <span>
                        <span class="home-tile-name"><?= Tr::nameHtml($u->name) ?></span>
                        <span class="home-tile-meta"><?= Tr::nameHtml($u->age) ?></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- technologies -->
<section class="home-section">
    <div class="home-section-head">
        <h2><?= Yii::t('app', 'Technologies') ?></h2>
        <a href="<?= Url::to(['technology/index']) ?>" class="home-more"><?= Yii::t('app', 'View all technologies') ?><?= UnitPills::chevron() ?></a>
    </div>
    <div class="row g-2">
        <?php foreach ($techs as $t): ?>
            <?php $icon = '/images/techs/' . str_replace([' ', "'", '*', '(', ')', '-'], '', $t->name) . '.png'; ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['technology/index', '#' => 'tech-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($t->name))]) ?>" class="admin5-card admin5-card-border home-tile">
                    <?php if (is_file(Yii::getAlias('@webroot') . $icon)): ?><img src="<?= $icon ?>" alt="" class="home-tile-icon is-tech"><?php endif; ?>
                    <span>
                        <span class="home-tile-name"><?= Tr::nameHtml($t->name) ?></span>
                        <span class="home-tile-meta"><?= implode(', ', array_map([Tr::class, 'nameHtml'], array_filter([$t->building, $t->age]))) ?></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- buildings -->
<section class="home-section">
    <div class="home-section-head">
        <h2><?= Yii::t('app', 'Buildings') ?></h2>
        <a href="<?= Url::to(['building/index']) ?>" class="home-more"><?= Yii::t('app', 'View all buildings') ?><?= UnitPills::chevron() ?></a>
    </div>
    <div class="row g-2">
        <?php foreach ($buildings as $b): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['building/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border home-tile">
                    <?php if ($b->iconUrl): ?><img src="<?= Html::encode($b->iconUrl) ?>" alt="" class="home-tile-icon is-tech"><?php endif; ?>
                    <span>
                        <span class="home-tile-name"><?= Tr::nameHtml($b->name) ?></span>
                        <span class="home-tile-meta"><?= Tr::nameHtml($b->age) ?></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- quick links, full width, three columns of three -->
<section class="home-section">
    <div class="home-section-head"><h2><?= Yii::t('app', 'Quick Links') ?></h2></div>
    <div class="row g-3 home-quick">
        <?php
        $classIcon = function ($name) { return UnitPills::iconForSlug(['cavalry' => 'knight', 'infantry' => 'long-swordsman', 'archers' => 'archer'][$name]); };
        foreach ([
            [[Yii::t('app', 'Build Orders'), ['build/index'], '/images/icons/builds.png'], [Yii::t('app', 'Guides by Elo'), ['guide/index'], '/images/icons/guides.png'], [Yii::t('app', 'Matchup'), ['matchup/index'], '/images/icons/matchup.png']],
            [[Yii::t('app', 'All Units'), ['unit/index'], '/images/icons/units.png'], [Yii::t('app', 'Unique Units'), ['unit/index', 'unique' => '1'], UnitPills::iconForSlug('samurai')], [Yii::t('app', 'Unit Classes'), ['unit/classes'], '/images/icons/classes.png']],
            [[Tr::name('Cavalry'), ['unit/class', 'slug' => 'cavalry'], $classIcon('cavalry')], [Tr::name('Infantry'), ['unit/class', 'slug' => 'infantry'], $classIcon('infantry')], [Tr::name('Archers'), ['unit/class', 'slug' => 'archers'], $classIcon('archers')]],
        ] as $column): ?>
            <div class="col-md-4 d-flex flex-column gap-2">
                <?php foreach ($column as [$label, $route, $img]): ?>
                    <a href="<?= Url::to($route) ?>" class="btn btn-outline-secondary btn-sm">
                        <?php if ($img): ?><img src="<?= Html::encode($img) ?>" alt=""><?php endif; ?>
                        <?= Html::encode($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php $this->registerJsFile('/js/hero-fx.js?v=' . filemtime(Yii::getAlias('@webroot') . '/js/hero-fx.js'), ['defer' => true]); ?>
<?php $this->registerJsFile('/js/wordmark-write.js?v=' . filemtime(Yii::getAlias('@webroot') . '/js/wordmark-write.js'), ['defer' => true]); ?>
