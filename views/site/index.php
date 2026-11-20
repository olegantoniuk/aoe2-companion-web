<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\Elo;
use app\components\UnitPills;

$this->title = 'Home';
Seo::page($this, null, 'Every Age of Empires II civilization, unit, building and technology, with counters, matchups, build orders and guides for each Elo level.');
?>

<section class="home-hero">
    <div class="home-intro">
        <h1 class="home-title" data-strokes="/images/logo/wordmark-strokes.png?v=<?= filemtime(Yii::getAlias('@webroot') . '/images/logo/wordmark-strokes.png') ?>"><?= file_get_contents(__DIR__ . '/_wordmark.svg') ?></h1>
        <p class="home-lead"><img src="/images/brand/initial-a.webp?v=<?= filemtime(Yii::getAlias('@webroot') . '/images/brand/initial-a.webp') ?>" alt="A" class="home-initial" width="160" height="160"> reference for Age of Empires II: Definitive Edition. Every civilization comes with its bonuses, team bonus and unique units. Every unit has its stats in each age and after each upgrade, what it is strong and weak against, and a short guide on how to use it.</p>
        <p>The Matchup page takes two civilizations and shows which of your units answer each unit the other side can build.</p>
        <p>New to the game or stuck at a rating? The <?= Html::a('build orders', ['build/index']) ?> and <?= Html::a('guides', ['guide/index']) ?> are sorted by Elo, from your first ladder games to the top of it.</p>
    </div>
    <?php if (is_file(Yii::getAlias('@webroot') . '/images/hero/hero.webp')): ?>
        <div class="home-hero-art">
            <img src="/images/hero/hero.webp?v=<?= filemtime(Yii::getAlias('@webroot') . '/images/hero/hero.webp') ?>" alt="Castles, knights, samurai, war elephants and villagers of the Age of Empires II world">
            <canvas class="hero-fx" aria-hidden="true" data-flags="/images/hero/flags.png?v=<?= filemtime(Yii::getAlias('@webroot') . '/images/hero/flags.png') ?>"></canvas>
        </div>
    <?php endif; ?>
</section>

<div class="row mb-4 g-3">
    <?php foreach ([
        [$civCount, 'Civilizations', ['civilization/index'], 'civilizations'],
        [$unitCount, 'Units', ['unit/index'], 'units'],
        [$techCount, 'Technologies', ['technology/index'], 'technologies'],
        [$buildingCount, 'Buildings', ['building/index'], 'buildings'],
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
            <h2>Build Orders</h2>
            <a href="<?= Url::to(['build/index']) ?>" class="home-more">All build orders<?= UnitPills::chevron() ?></a>
        </div>
        <div class="home-kb-list">
            <?php foreach ($builds as $b): ?>
                <a href="<?= Url::to(['build/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border home-kb-item">
                    <img src="<?= Html::encode(UnitPills::pageIconUrl('builds')) ?>" alt="" class="home-kb-avatar">
                    <span class="home-kb-main">
                        <span class="home-kb-title"><?= Html::encode($b->title) ?></span>
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
            <h2>Guides</h2>
            <a href="<?= Url::to(['guide/index']) ?>" class="home-more">All guides<?= UnitPills::chevron() ?></a>
        </div>
        <div class="home-kb-list">
            <?php foreach ($guides as $g): ?>
                <a href="<?= Url::to(['guide/view', 'slug' => $g->slug]) ?>" class="admin5-card admin5-card-border home-kb-item">
                    <img src="<?= Html::encode($g->avatarUrl) ?>" alt="" class="home-kb-avatar">
                    <span class="home-kb-main">
                        <span class="home-kb-title"><?= Html::encode($g->title) ?></span>
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
        <h2>Civilizations</h2>
        <a href="<?= Url::to(['civilization/index']) ?>" class="home-more">View all civilizations<?= UnitPills::chevron() ?></a>
    </div>
    <div class="row g-2">
        <?php foreach ($recentCivs as $civ): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['civilization/view', 'slug' => $civ->slug]) ?>" class="admin5-card admin5-card-border home-tile home-tile-civ">
                    <?php if ($civ->emblemUrl): ?>
                        <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="home-civ-emblem">
                    <?php endif; ?>
                    <span>
                        <span class="home-tile-name"><?= Html::encode($civ->name) ?></span>
                        <span class="home-tile-meta"><?= Html::encode(implode(', ', $civ->focusList)) ?></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- units -->
<section class="home-section">
    <div class="home-section-head">
        <h2>Units</h2>
        <a href="<?= Url::to(['unit/index']) ?>" class="home-more">View all units<?= UnitPills::chevron() ?></a>
    </div>
    <div class="row g-2">
        <?php foreach ($units as $u): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['unit/view', 'slug' => $u->slug]) ?>" class="admin5-card admin5-card-border home-tile">
                    <?= UnitPills::avatar($u->iconUrl) ?>
                    <span>
                        <span class="home-tile-name"><?= Html::encode($u->name) ?></span>
                        <span class="home-tile-meta"><?= Html::encode($u->age) ?></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- technologies -->
<section class="home-section">
    <div class="home-section-head">
        <h2>Technologies</h2>
        <a href="<?= Url::to(['technology/index']) ?>" class="home-more">View all technologies<?= UnitPills::chevron() ?></a>
    </div>
    <div class="row g-2">
        <?php foreach ($techs as $t): ?>
            <?php $icon = '/images/techs/' . str_replace([' ', "'", '*', '(', ')', '-'], '', $t->name) . '.png'; ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['technology/index', '#' => 'tech-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($t->name))]) ?>" class="admin5-card admin5-card-border home-tile">
                    <?php if (is_file(Yii::getAlias('@webroot') . $icon)): ?><img src="<?= $icon ?>" alt="" class="home-tile-icon is-tech"><?php endif; ?>
                    <span>
                        <span class="home-tile-name"><?= Html::encode($t->name) ?></span>
                        <span class="home-tile-meta"><?= Html::encode(trim($t->building . ($t->age ? ', ' . $t->age : ''), ', ')) ?></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- buildings -->
<section class="home-section">
    <div class="home-section-head">
        <h2>Buildings</h2>
        <a href="<?= Url::to(['building/index']) ?>" class="home-more">View all buildings<?= UnitPills::chevron() ?></a>
    </div>
    <div class="row g-2">
        <?php foreach ($buildings as $b): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['building/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border home-tile">
                    <?php if ($b->iconUrl): ?><img src="<?= Html::encode($b->iconUrl) ?>" alt="" class="home-tile-icon is-tech"><?php endif; ?>
                    <span>
                        <span class="home-tile-name"><?= Html::encode($b->name) ?></span>
                        <span class="home-tile-meta"><?= Html::encode($b->age) ?></span>
                    </span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- quick links, full width, three columns of three -->
<section class="home-section">
    <div class="home-section-head"><h2>Quick Links</h2></div>
    <div class="row g-3 home-quick">
        <?php
        $classIcon = function ($name) { return UnitPills::iconForSlug(['cavalry' => 'knight', 'infantry' => 'long-swordsman', 'archers' => 'archer'][$name]); };
        foreach ([
            [['Build Orders', ['build/index'], '/images/icons/builds.png'], ['Guides by Elo', ['guide/index'], '/images/icons/guides.png'], ['Matchup', ['matchup/index'], '/images/icons/matchup.png']],
            [['All Units', ['unit/index'], '/images/icons/units.png'], ['Unique Units', ['unit/index', 'unique' => '1'], UnitPills::iconForSlug('samurai')], ['Unit Classes', ['unit/classes'], '/images/icons/classes.png']],
            [['Cavalry', ['unit/class', 'slug' => 'cavalry'], $classIcon('cavalry')], ['Infantry', ['unit/class', 'slug' => 'infantry'], $classIcon('infantry')], ['Archers', ['unit/class', 'slug' => 'archers'], $classIcon('archers')]],
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
