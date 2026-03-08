<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

$this->title = $unit->name;

$attackBonuses = $unit->attackBonuses;
$armorClasses = $unit->armorClasses;
$techBoosts = $unit->technologyBoosts;
$civBoosts = $unit->civilizationBoosts;
$teamBoosts = $unit->teamBoosts;
$availability = $unit->availability;
$eliteVariant = $unit->elite_variant ? json_decode($unit->elite_variant, true) : null;
$strongAgainst = $unit->strong_against ? json_decode($unit->strong_against, true) : [];
$weakAgainst = $unit->weak_against ? json_decode($unit->weak_against, true) : [];

// Build name→unit lookup for counter links
$counterNames = array_merge($strongAgainst, $weakAgainst);
$counterUnits = [];
if ($counterNames) {
    $allUnits = \app\models\Unit::find()->select(['id', 'name', 'slug', 'image_icon'])->all();
    $byName = [];
    foreach ($allUnits as $u) {
        $byName[strtolower($u->name)] = $u;
    }
    foreach ($counterNames as $name) {
        $key = strtolower($name);
        if (isset($byName[$key])) {
            $counterUnits[$name] = $byName[$key];
        } elseif (substr($key, -3) === 'men' && isset($byName[substr($key, 0, -3) . 'man'])) {
            // Crossbowmen → Crossbowman, Swordsmen → Swordsman
            $counterUnits[$name] = $byName[substr($key, 0, -3) . 'man'];
        } elseif (substr($key, -1) === 's' && isset($byName[substr($key, 0, -1)])) {
            // Boyars → Boyar, Kamayuks → Kamayuk
            $counterUnits[$name] = $byName[substr($key, 0, -1)];
        }
    }
}

// Category mapping for counter links
$counterCategoryMap = \app\controllers\UnitController::getCounterCategoryMap();
$categoryDefs = \app\controllers\UnitController::getCategoryDefinitions();

// Build category icon URLs from representative units
$categoryIcons = [];
$iconSlugs = [];
foreach ($categoryDefs as $catSlug => $cat) {
    if (isset($cat['icon'])) {
        $iconSlugs[$catSlug] = $cat['icon'];
    }
}
if ($iconSlugs) {
    $iconUnits = \app\models\Unit::find()->where(['slug' => array_values($iconSlugs)])->all();
    $iconBySlug = [];
    foreach ($iconUnits as $iu) {
        $iconBySlug[$iu->slug] = $iu->iconUrl;
    }
    foreach ($iconSlugs as $catSlug => $unitSlug) {
        if (isset($iconBySlug[$unitSlug])) {
            $categoryIcons[$catSlug] = $iconBySlug[$unitSlug];
        }
    }
}

// Resource icons
$resIcons = [
    'Food' => '/images/resources/Food.png',
    'Wood' => '/images/resources/Wood.png',
    'Gold' => '/images/resources/Gold.png',
    'Stone' => '/images/resources/Stone.png',
];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a('Units', ['unit/index']) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($unit->name) ?></li>
    </ol>
</nav>

<div class="admin5-page-header">
    <h1>
        <?= Html::encode($unit->name) ?>
        <?php if ($unit->is_unique): ?>
            <span class="badge bg-warning"><?= $unit->civilization ? Html::encode($unit->civilization->name) : 'Unique' ?></span>
        <?php endif; ?>
    </h1>
</div>

<div class="row">
    <!-- Left Column -->
    <div class="col-lg-5 mb-4">
        <!-- Unit Image -->
        <?php if ($unit->iconUrl): ?>
            <div class="text-center mb-4">
                <div class="unit-portrait-card">
                    <img src="<?= Html::encode($unit->iconUrl) ?>" alt="<?= Html::encode($unit->name) ?>" class="unit-image-lg">
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Stats -->
        <div class="admin5-card admin5-card-border">
            <div class="card-header"><strong>General</strong></div>
            <table class="table table-sm stat-table mb-0">
                <?php if ($unit->type): ?>
                    <tr><th>Type</th><td><span class="badge bg-secondary badge-sm"><?= Html::encode($unit->type) ?></span></td></tr>
                <?php endif; ?>
                <?php if ($unit->age): ?>
                    <tr><th>Age</th><td><span class="badge badge-age age-<?= strtolower(explode(' ', $unit->age)[0]) ?>"><?= Html::encode($unit->age) ?></span></td></tr>
                <?php endif; ?>
                <?php if ($unit->training_building): ?>
                    <tr><th>Trained at</th><td><?= Html::encode($unit->training_building) ?></td></tr>
                <?php endif; ?>
                <?php if ($unit->civilization): ?>
                    <tr><th>Civilization</th><td>
                        <div class="d-flex align-items-center gap-1">
                            <?php if ($unit->civilization->emblemUrl): ?>
                                <img src="<?= Html::encode($unit->civilization->emblemUrl) ?>" alt="" class="civ-emblem-sm">
                            <?php endif; ?>
                            <?= Html::a(Html::encode($unit->civilization->name), ['civilization/view', 'slug' => $unit->civilization->slug]) ?>
                        </div>
                    </td></tr>
                <?php endif; ?>
                <tr><th>Cost</th><td>
                    <?php
                    $costParts = [];
                    if ($unit->cost_food) $costParts[] = '<img src="' . $resIcons['Food'] . '" alt="Food" class="res-icon"> ' . $unit->cost_food;
                    if ($unit->cost_wood) $costParts[] = '<img src="' . $resIcons['Wood'] . '" alt="Wood" class="res-icon"> ' . $unit->cost_wood;
                    if ($unit->cost_gold) $costParts[] = '<img src="' . $resIcons['Gold'] . '" alt="Gold" class="res-icon"> ' . $unit->cost_gold;
                    if ($unit->cost_stone) $costParts[] = '<img src="' . $resIcons['Stone'] . '" alt="Stone" class="res-icon"> ' . $unit->cost_stone;
                    echo $costParts ? implode(' &nbsp; ', $costParts) : 'Free';
                    ?>
                </td></tr>
                <?php if ($unit->training_time): ?>
                    <tr><th>Training Time</th><td><?= $unit->training_time ?>s</td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Combat Stats -->
        <div class="admin5-card admin5-card-border">
            <div class="card-header"><strong>Combat Stats</strong></div>
            <table class="table table-sm stat-table mb-0">
                <tr><th>Hit Points</th><td><?= $unit->hit_points ?? '-' ?></td></tr>
                <?php if ($unit->melee_attack !== null): ?>
                    <tr><th>Melee Attack</th><td><?= $unit->melee_attack ?></td></tr>
                <?php endif; ?>
                <?php if ($unit->pierce_attack !== null): ?>
                    <tr><th>Pierce Attack</th><td><?= $unit->pierce_attack ?></td></tr>
                <?php endif; ?>
                <tr><th>Melee Armor</th><td><?= $unit->melee_armor ?? 0 ?></td></tr>
                <tr><th>Pierce Armor</th><td><?= $unit->pierce_armor ?? 0 ?></td></tr>
                <?php if ($unit->range_val): ?>
                    <tr><th>Range</th><td><?= $unit->range_val ?></td></tr>
                <?php endif; ?>
                <?php if ($unit->accuracy): ?>
                    <tr><th>Accuracy</th><td><?= $unit->accuracy ?>%</td></tr>
                <?php endif; ?>
                <tr><th>Rate of Fire</th><td><?= $unit->rate_of_fire ?? '-' ?></td></tr>
                <tr><th>Speed</th><td><?= $unit->speed ?? '-' ?></td></tr>
                <?php if ($unit->line_of_sight): ?>
                    <tr><th>Line of Sight</th><td><?= $unit->line_of_sight ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Upgrade Path -->
        <?php if ($unit->upgrades_to || $unit->upgrades_from): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Upgrade Path</strong></div>
                <div class="card-content">
                    <?php if ($unit->upgrades_from): ?>
                        <div class="mb-2">
                            <small class="text-muted">Upgrades from:</small>
                            <strong><?= Html::encode($unit->upgrades_from) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if ($unit->upgrades_to): ?>
                        <div>
                            <small class="text-muted">Upgrades to:</small>
                            <strong><?= Html::encode($unit->upgrades_to) ?></strong>
                            <?php if ($unit->upgrade_cost_food || $unit->upgrade_cost_gold): ?>
                                <br><small class="text-muted">
                                    Cost: <?php
                                    $parts = [];
                                    if ($unit->upgrade_cost_food) $parts[] = '<img src="' . $resIcons['Food'] . '" alt="Food" class="res-icon"> ' . $unit->upgrade_cost_food;
                                    if ($unit->upgrade_cost_gold) $parts[] = '<img src="' . $resIcons['Gold'] . '" alt="Gold" class="res-icon"> ' . $unit->upgrade_cost_gold;
                                    echo implode(' &nbsp; ', $parts);
                                    ?>
                                    <?php if ($unit->upgrade_time): ?> | Time: <?= $unit->upgrade_time ?>s<?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column -->
    <div class="col-lg-7">
        <!-- Attack Bonuses -->
        <?php if ($attackBonuses): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Attack Bonuses</strong></div>
                <table class="table table-sm mb-0">
                    <?php foreach ($attackBonuses as $ab): ?>
                        <tr>
                            <td>+<?= $ab->bonus ?> vs <?= Html::encode($ab->vs) ?></td>
                            <?php if ($ab->variant): ?>
                                <td><small class="text-muted">(<?= Html::encode($ab->variant) ?>)</small></td>
                            <?php else: ?>
                                <td></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>

        <!-- Armor Classes -->
        <?php if ($armorClasses): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Armor Classes</strong></div>
                <div class="card-content">
                    <?php foreach ($armorClasses as $ac): ?>
                        <span class="badge bg-secondary badge-sm me-1 mb-1">
                            <?= Html::encode($ac->name) ?>
                            <?php if ($ac->modifier): ?>(<?= $ac->modifier > 0 ? '+' : '' ?><?= $ac->modifier ?>)<?php endif; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Counters -->
        <?php if ($strongAgainst || $weakAgainst): ?>
            <?php
            // Collect popup data for matched counter units
            $formatType = function ($type) {
                if (empty($type)) return '';
                $type = preg_replace_callback('/([a-z])([A-Z])/', function ($m) {
                    return $m[1] . ' ' . strtolower($m[2]);
                }, $type);
                $type = preg_replace('/\s+unit$/i', '', $type);
                return $type;
            };
            $popupUnits = [];
            // Load full unit data for matched counter units
            $counterUnitIds = [];
            foreach ($counterUnits as $cu) {
                $counterUnitIds[] = $cu->id;
            }
            $fullCounterUnits = [];
            if ($counterUnitIds) {
                $fullCounterUnits = \app\models\Unit::find()->where(['id' => $counterUnitIds])->all();
            }
            foreach ($fullCounterUnits as $cu) {
                $cost = [];
                if ($cu->cost_food) $cost['Food'] = (int)$cu->cost_food;
                if ($cu->cost_wood) $cost['Wood'] = (int)$cu->cost_wood;
                if ($cu->cost_gold) $cost['Gold'] = (int)$cu->cost_gold;
                if ($cu->cost_stone) $cost['Stone'] = (int)$cu->cost_stone;
                $bonuses = [];
                foreach ($cu->attackBonuses as $ab) {
                    if ((int)$ab->bonus > 0) {
                        $bonuses[] = ['vs' => $ab->vs, 'bonus' => (int)$ab->bonus];
                    }
                }
                usort($bonuses, function ($a, $b) { return $b['bonus'] - $a['bonus']; });
                $strong = $cu->strong_against ? json_decode($cu->strong_against, true) : [];
                $weak = $cu->weak_against ? json_decode($cu->weak_against, true) : [];
                $popupUnits[$cu->id] = [
                    'name' => $cu->name,
                    'type' => $formatType($cu->type),
                    'icon' => $cu->iconUrl,
                    'hp' => (int)$cu->hit_points,
                    'attack' => (int)($cu->melee_attack ?: $cu->pierce_attack),
                    'attackType' => $cu->melee_attack ? 'Melee' : 'Pierce',
                    'meleeArmor' => (int)$cu->melee_armor,
                    'pierceArmor' => (int)$cu->pierce_armor,
                    'speed' => $cu->speed ? round((float)$cu->speed, 2) : null,
                    'range' => $cu->range_val ? (int)$cu->range_val : null,
                    'cost' => $cost,
                    'strong' => $strong ? array_slice($strong, 0, 4) : [],
                    'weak' => $weak ? array_slice($weak, 0, 4) : [],
                    'bonuses' => array_slice($bonuses, 0, 6),
                ];
            }

            // Render a single counter item with 3-level matching
            $renderCounter = function ($name) use ($counterUnits, $counterCategoryMap, $categoryDefs, $categoryIcons) {
                $key = strtolower(trim($name));
                if ($key === 'buildings') {
                    return '<span class="counter-unit-text">Buildings</span>';
                }
                // Level 1: category match
                if (isset($counterCategoryMap[$key])) {
                    $catSlug = $counterCategoryMap[$key];
                    $catName = isset($categoryDefs[$catSlug]) ? $categoryDefs[$catSlug]['name'] : $name;
                    $icon = isset($categoryIcons[$catSlug]) ? '<img src="' . Html::encode($categoryIcons[$catSlug]) . '" alt="" class="unit-image-xs">' : '';
                    return '<a href="' . Url::to(['unit/category', 'slug' => $catSlug]) . '" class="counter-unit-link">' . $icon . Html::encode($catName) . '</a>';
                }
                // Level 2: exact or plural unit match
                if (isset($counterUnits[$name])) {
                    $cu = $counterUnits[$name];
                    $icon = $cu->iconUrl ? '<img src="' . Html::encode($cu->iconUrl) . '" alt="" class="unit-image-xs">' : '';
                    return '<a href="' . Url::to(['unit/view', 'slug' => $cu->slug]) . '" class="counter-unit-link" data-unit-id="' . $cu->id . '">' . $icon . Html::encode($cu->name) . '</a>';
                }
                // Level 3: plain text
                return '<span class="counter-unit-text">' . Html::encode(ucfirst($name)) . '</span>';
            };
            ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Counters</strong></div>
                <div class="card-content">
                    <?php if ($strongAgainst): ?>
                        <div class="mb-3">
                            <div class="mb-1"><span class="badge bg-success badge-sm">Strong against</span></div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($strongAgainst as $name): ?>
                                    <?= $renderCounter($name) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($weakAgainst): ?>
                        <div>
                            <div class="mb-1"><span class="badge bg-danger badge-sm">Weak against</span></div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($weakAgainst as $name): ?>
                                    <?= $renderCounter($name) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Unit popup element -->
            <div class="unit-popup" id="unitPopup"></div>

            <?php
            $popupDataJson = Json::encode($popupUnits);
            $this->registerJs(<<<JS
(function() {
    var popupData = $popupDataJson;
    var popup = document.getElementById('unitPopup');
    var hideTimer = null, showTimer = null;

    function buildPopup(uid) {
        var u = popupData[uid];
        if (!u) return '';
        var h = '<div class="unit-popup-header">';
        if (u.icon) h += '<div class="unit-popup-portrait"><img src="' + u.icon + '" alt=""></div>';
        h += '<div class="unit-popup-info">';
        h += '<div class="unit-popup-name">' + u.name + '</div>';
        if (u.type) h += '<div class="unit-popup-type">' + u.type + '</div>';
        if (u.cost && Object.keys(u.cost).length) {
            h += '<div class="unit-popup-cost">';
            for (var res in u.cost) h += '<span><img src="/images/resources/' + res + '.png" alt="" class="res-icon"> ' + u.cost[res] + '</span>';
            h += '</div>';
        }
        h += '</div></div><div class="unit-popup-body">';
        h += '<div class="unit-popup-stats-grid">';
        h += '<div class="unit-popup-stat"><span class="stat-label">HP</span><span class="stat-value">' + u.hp + '</span></div>';
        h += '<div class="unit-popup-stat"><span class="stat-label">Atk</span><span class="stat-value">' + u.attack + ' <small>' + u.attackType[0] + '</small></span></div>';
        h += '<div class="unit-popup-stat"><span class="stat-label">Armor</span><span class="stat-value">' + u.meleeArmor + '/' + u.pierceArmor + '</span></div>';
        if (u.range) h += '<div class="unit-popup-stat"><span class="stat-label">Range</span><span class="stat-value">' + u.range + '</span></div>';
        else if (u.speed) h += '<div class="unit-popup-stat"><span class="stat-label">Speed</span><span class="stat-value">' + u.speed + '</span></div>';
        h += '</div>';
        if (u.bonuses && u.bonuses.length) {
            h += '<div class="unit-popup-bonuses"><div class="unit-popup-section-title">Attack Bonuses</div>';
            for (var i = 0; i < u.bonuses.length; i++) h += '<span class="unit-popup-bonus">+' + u.bonuses[i].bonus + ' vs ' + u.bonuses[i].vs + '</span>';
            h += '</div>';
        }
        if ((u.strong && u.strong.length) || (u.weak && u.weak.length)) {
            h += '<div class="unit-popup-matchups">';
            if (u.strong && u.strong.length) h += '<div class="unit-popup-matchup"><span class="matchup-good">Strong:</span> ' + u.strong.join(', ') + '</div>';
            if (u.weak && u.weak.length) h += '<div class="unit-popup-matchup"><span class="matchup-bad">Weak:</span> ' + u.weak.join(', ') + '</div>';
            h += '</div>';
        }
        h += '</div>';
        return h;
    }

    function showPopup(link, uid) {
        popup.innerHTML = buildPopup(uid);
        if (!popup.innerHTML) return;
        popup.classList.add('visible');
        var rect = link.getBoundingClientRect();
        var scrollY = window.pageYOffset || document.documentElement.scrollTop;
        var scrollX = window.pageXOffset || document.documentElement.scrollLeft;
        var popupW = 320;
        var left = rect.left + scrollX + rect.width / 2 - popupW / 2;
        if (left < 8) left = 8;
        if (left + popupW > document.documentElement.clientWidth - 8) left = document.documentElement.clientWidth - popupW - 8;
        popup.style.left = left + 'px';
        popup.style.top = (rect.top + scrollY - popup.offsetHeight - 8) + 'px';
        if (rect.top - popup.offsetHeight - 8 < 0) popup.style.top = (rect.bottom + scrollY + 8) + 'px';
    }

    function hidePopup() { popup.classList.remove('visible'); }

    document.addEventListener('mouseenter', function(e) {
        var link = e.target.closest('.counter-unit-link[data-unit-id]');
        if (!link) return;
        clearTimeout(hideTimer); clearTimeout(showTimer);
        showTimer = setTimeout(function() { showPopup(link, link.getAttribute('data-unit-id')); }, 200);
    }, true);
    document.addEventListener('mouseleave', function(e) {
        var link = e.target.closest('.counter-unit-link[data-unit-id]');
        if (!link) return;
        clearTimeout(showTimer);
        hideTimer = setTimeout(hidePopup, 150);
    }, true);
})();
JS
            );
            ?>
        <?php endif; ?>

        <!-- Elite Variant -->
        <?php if ($eliteVariant): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Elite Variant: <?= Html::encode($eliteVariant['name'] ?? 'Elite') ?></strong></div>
                <table class="table table-sm stat-table mb-0">
                    <?php if (isset($eliteVariant['hit_points'])): ?>
                        <tr><th>Hit Points</th><td><?= $eliteVariant['hit_points'] ?></td></tr>
                    <?php endif; ?>
                    <?php if (isset($eliteVariant['melee_attack'])): ?>
                        <tr><th>Melee Attack</th><td><?= $eliteVariant['melee_attack'] ?></td></tr>
                    <?php endif; ?>
                    <?php if (isset($eliteVariant['pierce_attack'])): ?>
                        <tr><th>Pierce Attack</th><td><?= $eliteVariant['pierce_attack'] ?></td></tr>
                    <?php endif; ?>
                    <?php if (isset($eliteVariant['melee_armor'])): ?>
                        <tr><th>Melee Armor</th><td><?= $eliteVariant['melee_armor'] ?></td></tr>
                    <?php endif; ?>
                    <?php if (isset($eliteVariant['pierce_armor'])): ?>
                        <tr><th>Pierce Armor</th><td><?= $eliteVariant['pierce_armor'] ?></td></tr>
                    <?php endif; ?>
                    <?php if (isset($eliteVariant['range'])): ?>
                        <tr><th>Range</th><td><?= $eliteVariant['range'] ?></td></tr>
                    <?php endif; ?>
                    <?php if (isset($eliteVariant['speed'])): ?>
                        <tr><th>Speed</th><td><?= $eliteVariant['speed'] ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>

        <!-- Technology Boosts -->
        <?php if ($techBoosts): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Technology Boosts</strong></div>
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr><th>Technology</th><th>Stat</th><th>Effect</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($techBoosts as $b): ?>
                            <tr>
                                <td><?= Html::encode($b->name) ?></td>
                                <td><small class="text-muted"><?= Html::encode($b->stat) ?></small></td>
                                <td><?= Html::encode($b->effect) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Civilization Boosts -->
        <?php if ($civBoosts): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Civilization Boosts</strong></div>
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr><th>Civilization</th><th>Stat</th><th>Effect</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($civBoosts as $b): ?>
                            <tr>
                                <td><?= Html::encode($b->name) ?></td>
                                <td><small class="text-muted"><?= Html::encode($b->stat) ?></small></td>
                                <td><?= Html::encode($b->effect) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Availability -->
        <?php if ($availability): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Civilization Availability</strong></div>
                <div class="card-content">
                    <div class="row g-1">
                        <?php
                        $sorted = [];
                        foreach ($availability as $a) {
                            $sorted[] = $a;
                        }
                        usort($sorted, function ($a, $b) {
                            return $a->civilization->name <=> $b->civilization->name;
                        });
                        ?>
                        <?php foreach ($sorted as $a): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <small class="d-flex align-items-center gap-1">
                                    <?php if ($a->available): ?>
                                        <span class="avail-yes">&#10003;</span>
                                    <?php else: ?>
                                        <span class="avail-no">&#10007;</span>
                                    <?php endif; ?>
                                    <?php if ($a->civilization->emblemUrl): ?>
                                        <img src="<?= Html::encode($a->civilization->emblemUrl) ?>" alt="" style="width: 16px; height: 16px; object-fit: contain;">
                                    <?php endif; ?>
                                    <?= Html::a(Html::encode($a->civilization->name), ['civilization/view', 'slug' => $a->civilization->slug], ['class' => $a->available ? '' : 'text-muted']) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
