<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Elo;
use app\components\UnitPills;

$this->title = 'Home';
?>

<section class="home-hero">
    <div class="home-intro">
        <p>A reference for Age of Empires II: Definitive Edition. Every civilization comes with its bonuses, team bonus and unique units. Every unit has its stats in each age and after each upgrade, what it is strong and weak against, and a short guide on how to use it.</p>
        <p>The Matchup page takes two civilizations and shows which of your units answer each unit the other side can build.</p>
        <p>New to the game or stuck at a rating? The <?= Html::a('build orders', ['build/index']) ?> and <?= Html::a('guides', ['guide/index']) ?> are sorted by Elo, from your first ladder games to the top of it.</p>
    </div>
    <?php if (is_file(Yii::getAlias('@webroot') . '/images/hero/hero.webp')): ?>
        <div class="home-hero-art">
            <img src="/images/hero/hero.webp?v=<?= filemtime(Yii::getAlias('@webroot') . '/images/hero/hero.webp') ?>" alt="Castles, knights, samurai, war elephants and villagers of the Age of Empires II world">
        </div>
    <?php endif; ?>
</section>

<div class="row mb-4 g-3">
    <?php foreach ([
        [$civCount, 'Civilizations', ['civilization/index']],
        [$unitCount, 'Units', ['unit/index']],
        [$buildingCount, 'Buildings', ['building/index']],
        [$techCount, 'Technologies', ['technology/index']],
    ] as [$count, $label, $route]): ?>
        <div class="col-6 col-md-3">
            <a href="<?= Url::to($route) ?>" class="admin5-card admin5-card-border text-center home-stat">
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
            <?= Html::a('All build orders →', ['build/index'], ['class' => 'home-more']) ?>
        </div>
        <div class="home-kb-list">
            <?php foreach ($builds as $b): ?>
                <a href="<?= Url::to(['build/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border home-kb-item">
                    <span class="kb-elo kb-elo-<?= Html::encode($b->elo) ?>"><?= Elo::label($b->elo) ?></span>
                    <span class="home-kb-title"><?= Html::encode($b->title) ?></span>
                    <span class="home-kb-meta"><?= Html::encode($b->categoryLabel) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="home-section-head">
            <h2>Guides</h2>
            <?= Html::a('All guides →', ['guide/index'], ['class' => 'home-more']) ?>
        </div>
        <div class="home-kb-list">
            <?php foreach ($guides as $g): ?>
                <a href="<?= Url::to(['guide/view', 'slug' => $g->slug]) ?>" class="admin5-card admin5-card-border home-kb-item">
                    <span class="kb-elo kb-elo-<?= Html::encode($g->elo) ?>"><?= Elo::label($g->elo) ?></span>
                    <span class="home-kb-title"><?= Html::encode($g->title) ?></span>
                    <span class="home-kb-meta"><?= Html::encode($g->categoryLabel) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- civilizations -->
<section class="home-section">
    <div class="home-section-head">
        <h2>Civilizations</h2>
        <?= Html::a('View all civilizations →', ['civilization/index'], ['class' => 'home-more']) ?>
    </div>
    <div class="row g-2">
        <?php foreach ($recentCivs as $civ): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['civilization/view', 'slug' => $civ->slug]) ?>" class="admin5-card admin5-card-border home-tile">
                    <?php if ($civ->emblemUrl): ?>
                        <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="civ-emblem-sm">
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
        <?= Html::a('View all units →', ['unit/index'], ['class' => 'home-more']) ?>
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
        <?= Html::a('View all technologies →', ['technology/index'], ['class' => 'home-more']) ?>
    </div>
    <div class="row g-2">
        <?php foreach ($techs as $t): ?>
            <?php $icon = '/images/techs/' . str_replace([' ', "'", '*', '(', ')', '-'], '', $t->name) . '.png'; ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['technology/index', '#' => 'tech-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($t->name))]) ?>" class="admin5-card admin5-card-border home-tile">
                    <?php if (is_file(Yii::getAlias('@webroot') . $icon)): ?><img src="<?= $icon ?>" alt="" class="home-tile-icon"><?php endif; ?>
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
        <?= Html::a('View all buildings →', ['building/index'], ['class' => 'home-more']) ?>
    </div>
    <div class="row g-2">
        <?php foreach ($buildings as $b): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="<?= Url::to(['building/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border home-tile">
                    <?php if ($b->iconUrl): ?><img src="<?= Html::encode($b->iconUrl) ?>" alt="" class="home-tile-icon"><?php endif; ?>
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
        <?php foreach ([
            [['Build Orders', ['build/index']], ['Guides by Elo', ['guide/index']], ['Matchup', ['matchup/index']]],
            [['All Units', ['unit/index']], ['Unique Units', ['unit/index', 'unique' => '1']], ['Unit Classes', ['unit/classes']]],
            [['Cavalry', ['unit/class', 'slug' => 'cavalry']], ['Infantry', ['unit/class', 'slug' => 'infantry']], ['Archers', ['unit/class', 'slug' => 'archers']]],
        ] as $column): ?>
            <div class="col-md-4 d-flex flex-column gap-2">
                <?php foreach ($column as [$label, $route]): ?>
                    <?= Html::a($label, $route, ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
