<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use yii\helpers\Json;
use app\components\UnitPills;

$this->title = $unit->name;
Seo::page($this, $unit->name . ': Stats, Counters and Upgrades', $unit->description ?: ($unit->name . ' in Age of Empires II: stats in every age, upgrades, counters and the civilizations that can train it.'), $unit->iconUrl);

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
        } elseif ($key === 'leiciai' && isset($byName['leitis'])) {
            // Lithuanian plural of Leitis
            $counterUnits[$name] = $byName['leitis'];
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

// Stat icons
$statIcons = [
    'hp' => '/images/stats/hit_points.png',
    'melee_attack' => '/images/stats/melee_attack.png',
    'pierce_attack' => '/images/stats/pierce_attack.png',
    'melee_armor' => '/images/stats/melee_armor.png',
    'pierce_armor' => '/images/stats/pierce_armor.png',
    'range' => '/images/stats/range.png',
    'reload_time' => '/images/stats/reload_time.png',
    'speed' => '/images/stats/movement_speed.png',
    'blast_radius' => '/images/stats/blast_radius.png',
    'garrison' => '/images/stats/garrison.png',
    'line_of_sight' => '/images/stats/line_of_sight.png',
    'accuracy' => '/images/stats/accuracy.png',
];
$si = function ($key) use ($statIcons) {
    return isset($statIcons[$key]) ? '<img src="' . $statIcons[$key] . '" alt="" class="stat-icon">' : '';
};

// Civ emblem helper: "Byzantines" → "/images/civs/byzantines.png"
$civAliases = ['Mayans' => 'Maya', 'Incas' => 'Inca'];
$civIcon = function ($name) use ($civAliases) {
    $resolved = $civAliases[$name] ?? $name;
    return '/images/civs/' . strtolower($resolved) . '.png';
};

// Tech icon helper: "Blast Furnace" → "/images/techs/BlastFurnace.png"
// Falls back to generic unique tech icon if specific image not found
$techIcon = function ($name) {
    $file = str_replace([' ', "'", '*', '(', ')', '-'], '', $name) . '.png';
    $path = Yii::getAlias('@webroot') . '/images/techs/' . $file;
    if (file_exists($path)) {
        return '/images/techs/' . $file;
    }
    return '/images/techs/UniqueTechCastle.png';
};

// Pills with a round avatar and a hover card: see app\components\UnitPills
$tipTexts = UnitPills::TEXTS;
$iconForSlug = [UnitPills::class, 'iconForSlug'];
$buildingIcon = [UnitPills::class, 'buildingIcon'];
$pill = [UnitPills::class, 'pill'];
$classPill = [UnitPills::class, 'classPill'];

// Group technology boosts into one card per technology.
// Effects limited to one civ ("only") go after the general ones,
// and technologies whose every effect is civ-limited go last.
$techStatLabels = [
    'hp' => ['Hit Points', 'hp'],
    'attack' => ['Attack', 'melee_attack'],
    'armor' => ['Armor', 'melee_armor'],
    'range' => ['Range', 'range'],
    'speed' => ['Speed', 'speed'],
    'rof' => ['Rate of Fire', 'reload_time'],
    'accuracy' => ['Accuracy', 'accuracy'],
    'los' => ['Line of Sight', 'line_of_sight'],
    'conversion' => ['Conversion Resistance', null],
    'creation' => ['Training Speed', null],
    'population' => ['Population', null],
    'projectile' => ['Projectiles', null],
    'work' => ['Work Rate', null],
    'resists' => ['Damage Resistance', null],
    'resistance' => ['Damage Resistance', null],
];
$isCivLimited = function ($effect) {
    // "+5, only for the Aztecs" or "+4 min, +4 max, Sicilians only"
    return (bool)preg_match('/only for the |\bonly$/i', $effect);
};
// Sort keys pair the civ-limited flag with the original position (PHP 7.4 sorts are not stable)
$techGroups = [];
foreach ($techBoosts as $i => $b) {
    $techGroups[$b->name][] = ['key' => [$isCivLimited($b->effect), $i], 'boost' => $b];
}
$techGroupKeys = [];
foreach ($techGroups as $name => $items) {
    usort($items, function ($a, $b) {
        return $a['key'] <=> $b['key'];
    });
    $techGroupKeys[$name] = [$items[0]['key'][0], count($techGroupKeys)];
    $techGroups[$name] = array_column($items, 'boost');
}
uksort($techGroups, function ($a, $b) use ($techGroupKeys) {
    return $techGroupKeys[$a] <=> $techGroupKeys[$b];
});

// Cost, in-game description and civ availability for each technology (from import/technologies)
$technologies = $techGroups ? \app\models\Technology::find()
    ->where(['name' => array_keys($techGroups)])
    ->with(['civilization', 'availableCivs'])
    ->indexBy('name')
    ->all() : [];
$techCostHtml = function ($tech) use ($resIcons) {
    $parts = [];
    foreach (['Food' => $tech->cost_food, 'Wood' => $tech->cost_wood, 'Gold' => $tech->cost_gold, 'Stone' => $tech->cost_stone] as $res => $amount) {
        if ($amount) $parts[] = '<img src="' . $resIcons[$res] . '" alt="' . $res . '" class="res-icon"> ' . $amount;
    }
    return $parts ? implode(' &nbsp; ', $parts) : 'Free';
};

// Technology availability table: rows are civs that can train this unit,
// columns are shared technologies, unique ones go into a single last column.
$techTableCivs = [];
foreach ($availability as $a) {
    if ($a->available) $techTableCivs[] = $a->civilization;
}
if (!$techTableCivs && $unit->civilization) {
    $techTableCivs[] = $unit->civilization;
}
usort($techTableCivs, function ($a, $b) {
    return $a->name <=> $b->name;
});
$techTableShared = [];
$techTableUnique = [];
$techCivIds = [];
foreach ($techGroups as $techName => $items) {
    if (!isset($technologies[$techName])) continue;
    $tech = $technologies[$techName];
    $techCivIds[$techName] = array_flip(array_map(function ($c) { return $c->id; }, $tech->availableCivs));
    if ($tech->civilization_id) {
        $techTableUnique[$tech->civilization_id][] = $tech;
    } else {
        $techTableShared[] = $tech;
    }
}
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a('Units', ['unit/index']) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($unit->name) ?></li>
    </ol>
</nav>

<div class="admin5-page-header">
    <div class="unit-title">
        <?php if ($unit->iconUrl): ?>
            <img src="<?= Html::encode($unit->iconUrl) ?>" alt="" class="unit-title-icon">
        <?php endif; ?>
        <div class="unit-title-text">
            <h1><?= Html::encode($unit->name) ?></h1>
            <?php
            // a unique unit shared by a few civilizations (Winged Hussar) shows all of them,
            // a unit only one civilization trains (Imperial Skirmisher) shows that one
            $titleCivs = $unit->ownerCivs;
            ?>
            <?php if ($titleCivs): ?>
                <span class="unit-title-civ">
                    <?php foreach ($titleCivs as $tc): ?>
                        <?= UnitPills::civPill($tc) ?>
                    <?php endforeach; ?>
                </span>
            <?php elseif ($unit->is_unique): ?>
                <span class="badge bg-warning">Unique</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column -->
    <div class="col-lg-5 mb-4">
        <!-- Unit Image -->
        <?php if ($unit->transparentSpriteUrl): ?>
            <?php
            // Sprites are small (70–500px); scale up to 2x but keep within the portrait area
            [$spriteW, $spriteH] = getimagesize(Yii::getAlias('@webroot') . strtok($unit->transparentSpriteUrl, '?'));
            $spriteScale = min(2, 200 / $spriteH, 320 / $spriteW);
            ?>
            <?php
            // Animated game sprites stand on a patch of game grass; ships do not
            $groundUrl = null;
            if (substr(strtok($unit->transparentSpriteUrl, '?'), -5) === '.webp' && !$unit->isNaval) {
                $groundFile = '/images/ground/grass.png';
                $groundMtime = @filemtime(Yii::getAlias('@webroot') . $groundFile);
                $groundUrl = $groundMtime ? "$groundFile?v=$groundMtime" : null;
            }
            ?>
            <div class="text-center mb-4">
                <div class="unit-sprite-card">
                    <span class="unit-sprite-stage<?= $groundUrl ? ' has-ground' : '' ?>">
                        <?php if ($groundUrl): ?>
                            <span class="unit-ground" style="background-image: url('<?= Html::encode($groundUrl) ?>')"></span>
                        <?php endif; ?>
                        <img src="<?= Html::encode($unit->transparentSpriteUrl) ?>" alt="<?= Html::encode($unit->name) ?>"
                             width="<?= round($spriteW * $spriteScale) ?>" height="<?= round($spriteH * $spriteScale) ?>">
                    </span>
                </div>
            </div>
        <?php elseif ($unit->iconUrl): ?>
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
                <?php
                $classDefs = \app\controllers\UnitController::getClassDefinitions();
                $armorToSlug = [];
                foreach ($classDefs as $s => $def) {
                    foreach ($def['armorClasses'] as $ac) $armorToSlug[$ac] = $s;
                }
                $armorToSlugLc = array_change_key_case($armorToSlug, CASE_LOWER);
                $displayAC = $unit->displayArmorClasses;
                if ($displayAC): ?>
                    <tr><th>Classes</th><td>
                        <div class="pill-row">
                            <?php foreach ($displayAC as $ac): ?>
                                <?= $classPill($ac->name) ?>
                            <?php endforeach; ?>
                        </div>
                    </td></tr>
                <?php endif; ?>
                <?php if ($unit->age): ?>
                    <tr><th>Age</th><td><?= UnitPills::agePill($unit->age) ?></td></tr>
                <?php endif; ?>
                <?php if ($unit->training_building): ?>
                    <?php
                    // "CastleDonjon" -> Castle, Donjon: split on the building names that have an icon
                    $buildingNames = [];
                    // drop wiki notes stuck to the names: "(Mapuche only)", "requires …", "since update …", "*"
                    $rest = preg_replace('/\s*\([^)]*\)|requires.*$|since.*$|before update.*$|\*/i', '', $unit->training_building);
                    $rest = str_replace(' ', '', $rest);
                    while ($rest !== '') {
                        $found = null;
                        foreach (glob(Yii::getAlias('@webroot') . '/images/buildings/*.png') as $f) {
                            $b = basename($f, '.png');
                            if (stripos($rest, $b) === 0 && (!$found || strlen($b) > strlen($found))) $found = $b;
                        }
                        if (!$found) { $buildingNames = []; break; }
                        $buildingNames[] = $found;
                        $rest = substr($rest, strlen($found));
                    }
                    ?>
                    <tr><th>Trained at</th><td>
                        <?php if ($buildingNames): ?>
                            <div class="pill-row">
                                <?php foreach ($buildingNames as $b): ?>
                                    <?php $bName = trim(preg_replace('/(?<!^)([A-Z])/', ' $1', $b)); ?>
                                    <?= $pill($bName, [
                                        'variant' => 'building',
                                        'icon' => $buildingIcon($b),
                                        'tip' => isset($tipTexts['building:' . $bName])
                                            ? ['title' => $bName, 'subtitle' => 'Building', 'icon' => $buildingIcon($b), 'text' => $tipTexts['building:' . $bName]]
                                            : null,
                                    ]) ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <?= Html::encode($unit->training_building) ?>
                        <?php endif; ?>
                    </td></tr>
                <?php endif; ?>
                <?php $statCivs = $unit->civilization ? [$unit->civilization] : $unit->ownerCivs; ?>
                <?php if ($statCivs): ?>
                    <tr><th>Civilization</th><td><?= implode(' ', array_map([UnitPills::class, 'civPill'], $statCivs)) ?></td></tr>
                <?php endif; ?>
                <?php if ($unit->training_time): ?>
                    <tr><th>Training Time</th><td><img src="/images/stats/time.png" alt="" class="res-icon"> <?= $unit->training_time ?>s</td></tr>
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
            </table>
        </div>

        <!-- Attack Bonuses -->
        <?php if ($attackBonuses): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Attack Bonuses</strong></div>
                <div class="card-content">
                    <div class="pill-row">
                        <?php
                        // the elite value of the same bonus, from elite_variant
                        $eliteBonus = [];
                        foreach (($eliteVariant['attack_bonuses'] ?? []) as $eb) {
                            $eliteBonus[strtolower($eb['vs'])] = $eb['bonus'];
                        }
                        ?>
                        <?php foreach ($attackBonuses as $ab): ?>
                            <?php
                            $label = '+' . $ab->bonus;
                            $eb = $eliteBonus[strtolower($ab->vs)] ?? null;
                            if ($eb !== null && $eb != $ab->bonus) $label .= ' (Elite +' . $eb . ')';
                            elseif ($ab->variant) $label .= ' (' . $ab->variant . ')';
                            $label .= ' vs ' . $ab->vs;
                            ?>
                            <?= $classPill($ab->vs, $label) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Armor Classes -->
        <?php if ($armorClasses): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Armor Classes</strong></div>
                <div class="card-content">
                    <div class="pill-row">
                    <?php foreach ($armorClasses as $ac): ?>
                        <?= $classPill($ac->name, $ac->name . ($ac->modifier ? ' (' . ($ac->modifier > 0 ? '+' : '') . $ac->modifier . ')' : '')) ?>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Upgrade Path -->
        <?php if ($unit->upgrades_to || $unit->upgrades_from): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Upgrade Path</strong></div>
                <div class="card-content">
                    <?php if ($unit->upgrades_from): ?>
                        <div class="mb-2">
                            <small class="text-muted">Upgrades from:</small>
                            <?= UnitPills::avatar(UnitPills::iconForName($unit->upgrades_from)) ?>
                            <strong><?= Html::encode($unit->upgrades_from) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if ($unit->upgrades_to): ?>
                        <div>
                            <small class="text-muted">Upgrades to:</small>
                            <?= UnitPills::avatar(UnitPills::iconForName($unit->upgrades_to)) ?>
                            <strong><?= Html::encode($unit->upgrades_to) ?></strong>
                            <?php if ($unit->upgrade_cost_food || $unit->upgrade_cost_wood || $unit->upgrade_cost_gold): ?>
                                <br><small class="text-muted">
                                    Cost: <?php
                                    $parts = [];
                                    if ($unit->upgrade_cost_food) $parts[] = '<img src="' . $resIcons['Food'] . '" alt="Food" class="res-icon"> ' . $unit->upgrade_cost_food;
                                    if ($unit->upgrade_cost_wood) $parts[] = '<img src="' . $resIcons['Wood'] . '" alt="Wood" class="res-icon"> ' . $unit->upgrade_cost_wood;
                                    if ($unit->upgrade_cost_gold) $parts[] = '<img src="' . $resIcons['Gold'] . '" alt="Gold" class="res-icon"> ' . $unit->upgrade_cost_gold;
                                    echo implode(' &nbsp; ', $parts);
                                    ?>
                                    <?php if ($unit->upgrade_time): ?> &nbsp;<img src="/images/stats/time.png" alt="Time" title="Time" class="res-icon"> <?= $unit->upgrade_time ?>s<?php endif; ?>
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
        <?php
        // Text blocks: blank lines split paragraphs, "## " starts a subheading
        $renderText = function ($text) use ($unit) {
            $html = '';
            foreach (preg_split('/\n\s*\n/', trim(str_replace("\r", '', $text))) as $block) {
                foreach (preg_split('/\n(?=## )/', $block) as $part) {
                    if (strpos($part, '## ') === 0) {
                        [$head, $rest] = array_pad(explode("\n", $part, 2), 2, '');
                        $html .= '<h3 class="unit-text-heading">' . Html::encode(substr($head, 3)) . '</h3>';
                        if (trim($rest) !== '') $html .= '<p>' . UnitPills::linkify(Html::encode(trim($rest)), $unit->slug) . '</p>';
                    } else {
                        $html .= '<p>' . UnitPills::linkify(Html::encode(trim($part)), $unit->slug) . '</p>';
                    }
                }
            }
            return $html;
        };
        $textTabs = array_filter([
            'overview' => $unit->description ? ['Overview', $unit->description] : null,
            'tactics' => $unit->tactics ? ['Tactics', $unit->tactics] : null,
        ]);
        ?>
        <?php if ($textTabs): ?>
            <div class="unit-tabs unit-text-tabs">
                <ul class="nav nav-tabs mb-0" role="tablist">
                    <?php $first = true; foreach ($textTabs as $id => [$title]): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link<?= $first ? ' active' : '' ?>" data-bs-toggle="tab" data-bs-target="#text-<?= $id ?>" type="button" role="tab" aria-controls="text-<?= $id ?>" aria-selected="<?= $first ? 'true' : 'false' ?>"><?= $title ?></button>
                        </li>
                    <?php $first = false; endforeach; ?>
                </ul>
                <div class="tab-content">
                    <?php $first = true; foreach ($textTabs as $id => [$title, $text]): ?>
                        <div class="tab-pane fade<?= $first ? ' show active' : '' ?>" id="text-<?= $id ?>" role="tabpanel">
                            <div class="unit-description"><?= $renderText($text) ?></div>
                        </div>
                    <?php $first = false; endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <!-- Combat Stats -->
        <?php
        $ageStats = $unit->age_stats;
        if (is_string($ageStats)) {
            $ageStats = json_decode($ageStats, true);
        }
        $ageColumns = $ageStats['columns'] ?? [];
        // Units in an upgrade line (Archer, Crossbowman, Arbalester): one column for every unit of the line
        $lineCols = $ageColumns ? [] : \app\components\UnitLine::columns($unit);
        // Units without their own age data but with an elite upgrade: the age they come in, then the Imperial Age
        if (!$ageColumns && !$lineCols && $eliteVariant && $unit->age) {
            $ages = array_values(array_unique([$unit->age, 'Imperial Age']));
            $ageStats = ['columns' => [], 'elite_age' => 'Imperial Age', 'stats' => []];
            foreach ($ages as $a) {
                $ageStats['columns'][] = ['age' => $a, 'icon' => str_replace(' ', '', $a), 'label' => explode(' ', $a)[0]];
            }
            $ageColumns = $ageStats['columns'];
        }
        ?>
        <div class="admin5-card admin5-card-border">
            <div class="card-header" id="combat-stats"><strong>Combat Stats</strong></div>
            <?php if ($lineCols): ?>
                <?php
                $lineRows = [
                    ['hit_points', 'hp', 'Hit Points', ''],
                    ['melee_attack', 'melee_attack', 'Melee Attack', ''],
                    ['pierce_attack', 'pierce_attack', 'Pierce Attack', ''],
                    ['melee_armor', 'melee_armor', 'Melee Armor', ''],
                    ['pierce_armor', 'pierce_armor', 'Pierce Armor', ''],
                    ['range_val', 'range', 'Range', ''],
                    ['min_range', 'range', 'Min Range', ''],
                    ['blast_radius', 'blast_radius', 'Blast Radius', ''],
                    ['accuracy', 'accuracy', 'Accuracy', '%'],
                    ['rate_of_fire', 'reload_time', 'Rate of Fire', ''],
                    ['speed', 'speed', 'Speed', ''],
                    ['line_of_sight', 'line_of_sight', 'Line of Sight', ''],
                ];
                // a row shows when any unit of the line has the stat
                $lineRows = array_values(array_filter($lineRows, function ($r) use ($lineCols) {
                    foreach ($lineCols as $c) {
                        if ($c['stats'][$r[0]] !== null && $c['stats'][$r[0]] !== '' && !(in_array($r[0], ['range_val', 'accuracy', 'min_range', 'blast_radius'], true) && !$c['stats'][$r[0]])) return true;
                    }
                    return false;
                }));
                // consecutive columns of the same age share one age header
                $ageGroups = [];
                foreach ($lineCols as $c) {
                    $last = count($ageGroups) - 1;
                    if ($last >= 0 && $ageGroups[$last][0] === $c['age']) $ageGroups[$last][1]++;
                    else $ageGroups[] = [$c['age'], 1];
                }
                $lineDelta = function ($d, $suffix) {
                    $d = round($d, 2);
                    return '<span class="stat-delta ' . ($d > 0 ? 'stat-delta-up' : 'stat-delta-down') . '">' . ($d > 0 ? '+' : '') . $d . $suffix . '</span>';
                };
                ?>
                <div class="table-responsive">
                <table class="table table-sm stat-table age-stat-table line-stat-table mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2"></th>
                            <?php
                            // one cell per column, so the highlight of the current unit runs to the top;
                            // the age shield and name stand over the first column of each age
                            $prevAge = null;
                            ?>
                            <?php foreach ($lineCols as $c): ?>
                                <th class="age-stat-col age-stat-age<?= $c['current'] ? ' is-current' : '' ?>">
                                    <?php if ($c['age'] !== $prevAge): ?>
                                        <img src="/images/ages/<?= str_replace(' ', '', $c['age']) ?>.png" alt="" class="age-stat-icon" title="<?= Html::encode($c['age']) ?>">
                                        <span><?= Html::encode(explode(' ', $c['age'])[0]) ?></span>
                                    <?php endif; ?>
                                </th>
                                <?php $prevAge = $c['age']; ?>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($lineCols as $c): ?>
                                <?php
                                $upParts = [];
                                if ($c['upgrade']) {
                                    foreach (['Food', 'Wood', 'Gold'] as $k => $res) {
                                        if (!empty($c['upgrade'][$k])) $upParts[] = '<span class="age-stat-tip-res"><img src="' . $resIcons[$res] . '" alt="' . $res . '" class="res-icon"> ' . (int)$c['upgrade'][$k] . '</span>';
                                    }
                                }
                                $hasTip = $upParts || !empty($c['upgrade'][3]);
                                $head = UnitPills::avatar($c['icon']) . '<span>' . Html::encode($c['name']) . '</span>';
                                ?>
                                <th class="age-stat-col age-stat-unit line-stat-unit<?= $c['current'] ? ' is-current' : '' ?><?= $hasTip ? ' age-stat-has-tip' : '' ?>"<?= $hasTip ? ' tabindex="0"' : '' ?>>
                                    <?php if ($c['slug'] && !$c['current']): ?>
                                        <a href="<?= Url::to(['unit/view', 'slug' => $c['slug']]) ?>" class="line-stat-head"><?= $head ?></a>
                                    <?php else: ?>
                                        <span class="line-stat-head"><?= $head ?></span>
                                    <?php endif; ?>
                                    <?php if ($hasTip): ?>
                                        <span class="age-stat-tip" role="tooltip">
                                            <span class="age-stat-tip-title"><?= Html::encode($c['name']) ?> Upgrade</span>
                                            <?php if ($upParts): ?>
                                                <span class="age-stat-tip-row"><?= implode(' ', $upParts) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($c['upgrade'][3])): ?>
                                                <span class="age-stat-tip-row"><img src="/images/stats/time.png" alt="Time" class="age-stat-tip-icon"> <?= $c['upgrade'][3] + 0 ?>s</span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lineRows as [$attr, $icon, $label, $suffix]): ?>
                            <tr>
                                <th><?= $label ?></th>
                                <?php foreach ($lineCols as $c): ?>
                                    <?php
                                    $v = $c['stats'][$attr];
                                    $pv = $c['parent'] !== null ? $lineCols[$c['parent']]['stats'][$attr] : null;
                                    $d = ($v !== null && $pv !== null && is_numeric($v) && is_numeric($pv) && $v != $pv) ? $v - $pv : null;
                                    ?>
                                    <td class="age-stat-col<?= $c['current'] ? ' is-current' : '' ?>">
                                        <?php if ($v !== null && $v !== ''): ?>
                                            <?= $si($icon) ?><?= ($v + 0) . $suffix ?>
                                            <?php if ($d !== null): ?><?= $lineDelta($d, $suffix) ?><?php endif; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php elseif ($ageColumns): ?>
                <?php
                // [column, icon key, label, value suffix, shown when the unit has the stat]
                $combatRows = [
                    ['hit_points', 'hp', 'Hit Points', '', true],
                    ['melee_attack', 'melee_attack', 'Melee Attack', '', $unit->melee_attack !== null],
                    ['pierce_attack', 'pierce_attack', 'Pierce Attack', '', $unit->pierce_attack !== null],
                    ['melee_armor', 'melee_armor', 'Melee Armor', '', true],
                    ['pierce_armor', 'pierce_armor', 'Pierce Armor', '', true],
                    ['range_val', 'range', 'Range', '', (bool)$unit->range_val],
                    ['min_range', 'range', 'Min Range', '', (bool)$unit->min_range],
                    ['blast_radius', 'blast_radius', 'Blast Radius', '', (bool)$unit->blast_radius],
                    ['accuracy', 'accuracy', 'Accuracy', '%', (bool)$unit->accuracy],
                    ['rate_of_fire', 'reload_time', 'Rate of Fire', '', true],
                    ['speed', 'speed', 'Speed', '', true],
                    ['line_of_sight', 'line_of_sight', 'Line of Sight', '', (bool)$unit->line_of_sight],
                ];
                $fmtDelta = function ($d) {
                    $d = round($d, 2);
                    return ($d > 0 ? '+' : '') . $d;
                };
                $deltaHtml = function ($d, $suffix) use ($fmtDelta) {
                    return '<span class="stat-delta ' . ($d > 0 ? 'stat-delta-up' : 'stat-delta-down') . '">'
                        . $fmtDelta($d) . $suffix . '</span>';
                };
                // The elite upgrade goes into the column of the age where it can be researched
                $eliteColumn = null;
                if ($eliteVariant) {
                    foreach ($ageColumns as $i => $col) {
                        if (($col['age'] ?? '') === ($ageStats['elite_age'] ?? 'Imperial Age')) {
                            $eliteColumn = $i;
                        }
                    }
                }
                $eliteKeys = ['range_val' => 'range'];
                // Elite icon sits next to the regular one: "Elite Serjeant" -> /images/units/elite_serjeant_icon.png
                $eliteIconUrl = null;
                if ($eliteColumn !== null && !empty($eliteVariant['name'])) {
                    $file = '/images/units/' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $eliteVariant['name'])) . '_icon.png';
                    $mtime = @filemtime(Yii::getAlias('@webroot') . $file);
                    $eliteIconUrl = $mtime ? "$file?v=$mtime" : null;
                }
                $unitHead = function ($iconUrl, $label) {
                    $img = $iconUrl ? '<img src="' . Html::encode($iconUrl) . '" alt="" class="age-stat-avatar">' : '';
                    return '<span class="age-stat-unit-head">' . $img . '<span>' . Html::encode($label) . '</span></span>';
                };
                // The regular unit in the elite age is left out when it only repeats the age before
                $hideRegular = false;
                if ($eliteColumn) {
                    $hideRegular = true;
                    foreach ($combatRows as [$attr]) {
                        $now = $ageStats['stats'][$attr][$eliteColumn] ?? $unit->$attr;
                        $before = $ageStats['stats'][$attr][$eliteColumn - 1] ?? $unit->$attr;
                        if ($now != $before) {
                            $hideRegular = false;
                        }
                    }
                }
                ?>
                <table class="table table-sm stat-table age-stat-table mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2"></th>
                            <?php foreach ($ageColumns as $i => $col): ?>
                                <th class="age-stat-col age-stat-age"<?= $i === $eliteColumn && !$hideRegular ? ' colspan="2"' : '' ?>>
                                    <img src="/images/ages/<?= Html::encode($col['icon']) ?>.png" alt="" class="age-stat-icon" title="<?= Html::encode($col['age']) ?>">
                                    <span><?= Html::encode($col['label']) ?></span>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($ageColumns as $i => $col): ?>
                                <?php if (!($i === $eliteColumn && $hideRegular)): ?>
                                    <th class="age-stat-col age-stat-unit"><?= $unitHead($unit->iconUrl, $unit->name) ?></th>
                                <?php endif; ?>
                                <?php if ($i === $eliteColumn): ?>
                                    <?php
                                    // Hover card with the cost and time of the elite upgrade, same data as Upgrade Path
                                    $upParts = [];
                                    foreach (['Food' => $unit->upgrade_cost_food, 'Wood' => $unit->upgrade_cost_wood, 'Gold' => $unit->upgrade_cost_gold, 'Stone' => $unit->upgrade_cost_stone] as $res => $amount) {
                                        if ($amount) {
                                            $upParts[] = '<span class="age-stat-tip-res"><img src="' . $resIcons[$res] . '" alt="' . $res . '" class="res-icon"> ' . $amount . '</span>';
                                        }
                                    }
                                    $hasTip = $upParts || $unit->upgrade_time;
                                    ?>
                                    <th class="age-stat-col age-stat-unit<?= $hasTip ? ' age-stat-has-tip' : '' ?>"<?= $hasTip ? ' tabindex="0"' : '' ?>>
                                        <?= $unitHead($eliteIconUrl ?: $unit->iconUrl, 'Elite') ?>
                                        <?php if ($hasTip): ?>
                                            <span class="age-stat-tip" role="tooltip">
                                                <span class="age-stat-tip-title"><?= Html::encode($eliteVariant['name'] ?? $unit->upgrades_to ?? 'Elite') ?> Upgrade</span>
                                                <?php if ($upParts): ?>
                                                    <span class="age-stat-tip-row">
                                                        <?= implode(' ', $upParts) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($unit->upgrade_time): ?>
                                                    <span class="age-stat-tip-row">
                                                        <img src="/images/stats/time.png" alt="Time" title="Time" class="age-stat-tip-icon">
                                                        <?= $unit->upgrade_time ?>s
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($combatRows as [$attr, $icon, $label, $suffix, $show]): ?>
                            <?php if (!$show) continue; ?>
                            <tr>
                                <th><?= $label ?></th>
                                <?php $prev = null; ?>
                                <?php foreach ($ageColumns as $i => $col): ?>
                                    <?php
                                    $value = $ageStats['stats'][$attr][$i] ?? $unit->$attr;
                                    $delta = ($prev !== null && $value !== null && $value != $prev) ? $value - $prev : null;
                                    $prev = $value;
                                    ?>
                                    <?php if (!($i === $eliteColumn && $hideRegular)): ?>
                                        <td class="age-stat-col">
                                            <?= $si($icon) ?><?= $value !== null ? $value . $suffix : '-' ?>
                                            <?php if ($delta !== null): ?>
                                                <?= $deltaHtml($delta, $suffix) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <?php if ($i === $eliteColumn): ?>
                                        <?php
                                        // Elite column: the upgraded unit in the age it is researched, change against the regular unit
                                        $elite = $eliteVariant[$eliteKeys[$attr] ?? $attr] ?? $value;
                                        $eliteDelta = ($value !== null && $elite !== null && $elite != $value) ? $elite - $value : null;
                                        ?>
                                        <td class="age-stat-col age-stat-elite-col">
                                            <?= $si($icon) ?><?= $elite !== null ? $elite . $suffix : '-' ?>
                                            <?php if ($eliteDelta !== null): ?>
                                                <?= $deltaHtml($eliteDelta, $suffix) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
            <table class="table table-sm stat-table mb-0">
                <tr><th>Hit Points</th><td><?= $si('hp') ?><?= $unit->hit_points ?? '-' ?></td></tr>
                <?php if ($unit->melee_attack !== null): ?>
                    <tr><th>Melee Attack</th><td><?= $si('melee_attack') ?><?= $unit->melee_attack ?></td></tr>
                <?php endif; ?>
                <?php if ($unit->pierce_attack !== null): ?>
                    <tr><th>Pierce Attack</th><td><?= $si('pierce_attack') ?><?= $unit->pierce_attack ?></td></tr>
                <?php endif; ?>
                <tr><th>Melee Armor</th><td><?= $si('melee_armor') ?><?= $unit->melee_armor ?? 0 ?></td></tr>
                <tr><th>Pierce Armor</th><td><?= $si('pierce_armor') ?><?= $unit->pierce_armor ?? 0 ?></td></tr>
                <?php if ($unit->range_val): ?>
                    <tr><th>Range</th><td><?= $si('range') ?><?= $unit->range_val ?></td></tr>
                <?php endif; ?>
                <?php if ($unit->accuracy): ?>
                    <tr><th>Accuracy</th><td><?= $si('accuracy') ?><?= $unit->accuracy ?>%</td></tr>
                <?php endif; ?>
                <tr><th>Rate of Fire</th><td><?= $si('reload_time') ?><?= $unit->rate_of_fire ?? '-' ?></td></tr>
                <tr><th>Speed</th><td><?= $si('speed') ?><?= $unit->speed ?? '-' ?></td></tr>
                <?php if ($unit->line_of_sight): ?>
                    <tr><th>Line of Sight</th><td><?= $si('line_of_sight') ?><?= $unit->line_of_sight ?></td></tr>
                <?php endif; ?>
                <?php if ($unit->min_range): ?>
                    <tr><th>Min Range</th><td><?= $si('range') ?><?= $unit->min_range + 0 ?></td></tr>
                <?php endif; ?>
                <?php if ($unit->blast_radius): ?>
                    <tr><th>Blast Radius</th><td><?= $si('blast_radius') ?><?= $unit->blast_radius + 0 ?></td></tr>
                <?php endif; ?>
            </table>
            <?php endif; ?>
        </div>

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
                $fullCounterUnits = \app\models\Unit::find()->where(['id' => $counterUnitIds])->with('armorClasses')->all();
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
                    'type' => $cu->armorClassGroup,
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
            // Plurals the s/men rules above do not catch
            $irregularPlurals = ['leiciai' => 'leitis'];
            $seenCounters = [];
            $renderCounter = function ($name) use (&$counterUnits, &$popupUnits, &$seenCounters, $counterCategoryMap, $categoryDefs, $categoryIcons, $irregularPlurals, $pill, $tipTexts, $buildingIcon) {
                // wiki leftovers: templates, "And …", "Especially …", notes about old versions
                $orig = $name;
                $name = UnitPills::cleanPhrase($name);
                if ($name === null) return '';
                $key = strtolower(trim($name));
                // the same thing written two ways ("War Elephants", "Battle Elephants") shows once
                $once = function ($id) use (&$seenCounters) {
                    if (isset($seenCounters[$id])) return false;
                    return $seenCounters[$id] = true;
                };
                if ($key === 'buildings') {
                    if (!$once('buildings')) return '';
                    $icon = $buildingIcon('House');
                    return $pill('Buildings', ['icon' => $icon, 'tip' => ['title' => 'Buildings', 'icon' => $icon, 'text' => $tipTexts['building:Buildings']]]);
                }
                // Level 1: category match
                // "infantry with less melee armor" belongs to the infantry group
                $mapKey = isset($counterCategoryMap[$key]) ? $key : preg_replace('/ with .*$/', '', $key);
                if (isset($counterCategoryMap[$mapKey])) {
                    $catSlug = $counterCategoryMap[$mapKey];
                    // a qualified name ("infantry with less melee armor") keeps its own wording
                    $catName = isset($categoryDefs[$catSlug]) && strpos($key, ' with ') === false ? $categoryDefs[$catSlug]['name'] : ucfirst($name);
                    if (!$once('cat:' . $catSlug . ':' . $catName)) return '';
                    $icon = $categoryIcons[$catSlug] ?? null;
                    $text = $tipTexts['cat:' . $catSlug] ?? ($categoryDefs[$catSlug]['description'] ?? null);
                    return $pill($catName, [
                        'href' => Url::to(['unit/category', 'slug' => $catSlug]),
                        'icon' => $icon,
                        'tip' => $text ? ['title' => $catName, 'subtitle' => 'Unit group', 'icon' => $icon, 'text' => $text] : null,
                    ]);
                }
                // Level 2: exact or plural unit match
                $cu = $counterUnits[$orig] ?? $counterUnits[$name] ?? null;
                if (!$cu && isset($irregularPlurals[$key])) {
                    $cu = \app\models\Unit::find()->where(['slug' => $irregularPlurals[$key]])->one();
                }
                if (!$cu) {
                    // "Huskarls" left after cutting "And": find the unit by its singular name
                    $cu = \app\models\Unit::find()->where(['name' => array_unique([
                        $name, preg_replace('/s$/', '', $name), preg_replace('/es$/', '', $name), preg_replace('/men$/', 'man', $name), preg_replace('/ies$/', 'y', $name),
                    ])])->one();
                }
                if ($cu) {
                    if (!$once('unit:' . $cu->id)) return '';
                    return $pill($cu->name, [
                        'href' => Url::to(['unit/view', 'slug' => $cu->slug]),
                        'icon' => $cu->iconUrl,
                        'unitId' => $cu->id,
                    ]);
                }
                // Level 3: plain text
                if (!$once('text:' . $key)) return '';
                return $pill(ucfirst($name), ['icon' => UnitPills::guessIcon($name)]);
            };
            ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Counters</strong></div>
                <div class="card-content">
                    <?php if ($strongAgainst): ?>
                        <div class="mb-3">
                            <div class="counters-label counters-label-good">Strong against</div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($strongAgainst as $name): ?>
                                    <?= $renderCounter($name) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($weakAgainst): ?>
                        <div>
                            <div class="counters-label counters-label-bad">Weak against</div>
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
        h += '<div class="unit-popup-stat"><span class="stat-label"><img src="/images/stats/hit_points.png" alt="" class="stat-icon"> HP</span><span class="stat-value">' + u.hp + '</span></div>';
        h += '<div class="unit-popup-stat"><span class="stat-label"><img src="/images/stats/' + (u.attackType === 'Melee' ? 'melee_attack' : 'pierce_attack') + '.png" alt="" class="stat-icon"> Atk</span><span class="stat-value">' + u.attack + ' <small>' + u.attackType[0] + '</small></span></div>';
        h += '<div class="unit-popup-stat"><span class="stat-label"><img src="/images/stats/melee_armor.png" alt="" class="stat-icon"> Armor</span><span class="stat-value">' + u.meleeArmor + '/' + u.pierceArmor + '</span></div>';
        if (u.range) h += '<div class="unit-popup-stat"><span class="stat-label"><img src="/images/stats/range.png" alt="" class="stat-icon"> Range</span><span class="stat-value">' + u.range + '</span></div>';
        else if (u.speed) h += '<div class="unit-popup-stat"><span class="stat-label"><img src="/images/stats/movement_speed.png" alt="" class="stat-icon"> Speed</span><span class="stat-value">' + u.speed + '</span></div>';
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

    </div>
</div>


<!-- Civilization Availability, full width -->
<?php if ($availability): ?>
    <?php
    $sorted = [];
    foreach ($availability as $a) {
        $sorted[] = $a;
    }
    usort($sorted, function ($a, $b) {
        return $a->civilization->name <=> $b->civilization->name;
    });
    ?>
    <div class="admin5-card admin5-card-border unit-avail-card">
        <div class="card-header"><strong>Civilization Availability</strong></div>
        <div class="card-content">
            <div class="civ-avail-grid">
                <?php foreach ($sorted as $a): ?>
                    <div class="civ-avail-item<?= $a->available ? '' : ' is-no' ?>">
                        <?php if ($a->available): ?>
                            <span class="avail-yes">&#10003;</span>
                        <?php else: ?>
                            <span class="avail-no">&#10007;</span>
                        <?php endif; ?>
                        <?php if ($a->civilization->emblemUrl): ?>
                            <img src="<?= Html::encode($a->civilization->emblemUrl) ?>" alt="">
                        <?php endif; ?>
                        <?= Html::a(Html::encode($a->civilization->name), ['civilization/view', 'slug' => $a->civilization->slug], ['class' => $a->available ? '' : 'text-muted']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Technologies -->
<?php if ($techGroups): ?>
    <h2 class="unit-section-title">Technologies</h2>
    <div class="tech-grid">
        <?php foreach ($techGroups as $techName => $items): ?>
            <?php $tech = $technologies[$techName] ?? null; ?>
            <div class="admin5-card admin5-card-border tech-card-box">
                <div class="tech-card tech-card-stacked">
                    <!-- icon as tall as the name and the cost line next to it -->
                    <div class="tech-card-head">
                        <img src="<?= Html::encode($techIcon($techName)) ?>" alt="" class="tech-card-icon">
                        <div class="tech-card-head-text">
                            <div class="tech-card-name">
                                <?= Html::encode($techName) ?>
                                <?php if ($tech && $tech->civilization): ?>
                                    <?= UnitPills::civPill($tech->civilization) ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($tech): ?>
                                <div class="tech-card-cost">
                                    <?= $techCostHtml($tech) ?>
                                    <?php if ($tech->research_time): ?>
                                        &nbsp;<img src="/images/stats/time.png" alt="Time" title="Research time" class="res-icon"> <?= (int)$tech->research_time ?>s
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tech-card-body">
                        <?php if ($tech && $tech->description): ?>
                            <div class="tech-card-desc"><?= nl2br(Html::encode($tech->description)) ?></div>
                        <?php endif; ?>
                        <?php
                        $inline = count($items) <= 2 && array_sum(array_map(function ($b) { return strlen($b->effect); }, $items)) <= 24;
                        ?>
                        <ul class="tech-card-effects<?= $inline ? ' is-inline' : '' ?>">
                            <?php foreach ($items as $b): ?>
                                <?php
                                $label = $techStatLabels[$b->stat] ?? [ucfirst($b->stat), null];
                                $iconKey = $label[1] ?? null;
                                ?>
                                <li>
                                    <span class="tech-card-stat"><?= $iconKey ? $si($iconKey) . ' ' : '' ?><?= Html::encode($label[0]) ?></span>
                                    <span><?= Html::encode(str_replace('|', ', ', $b->effect)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Civilization Boosts and Technology Availability, side by side -->
<?php if ($civBoosts || ($techTableCivs && ($techTableShared || $techTableUnique))): ?>
<?php
// one table alone takes the full width (unique units often have no civilization boosts)
$hasTechAvail = $techTableCivs && ($techTableShared || $techTableUnique);
$bottomCol = $civBoosts && $hasTechAvail ? 'col-lg-6' : 'col-12';
?>
<div class="row g-3 unit-bottom-row">
    <?php if ($civBoosts): ?>
    <div class="<?= $bottomCol ?>">
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
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= Html::encode($civIcon($b->name)) ?>" alt="" class="tech-icon">
                                        <?= Html::encode($b->name) ?>
                                    </div>
                                </td>
                                <td><small class="text-muted"><?= Html::encode($b->stat) ?></small></td>
                                <td><?= Html::encode($b->effect) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>
    <?php if ($hasTechAvail): ?>
    <div class="<?= $bottomCol ?>">
        <!-- Technology Availability -->
        <?php if ($techTableCivs && ($techTableShared || $techTableUnique)): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Technology Availability</strong></div>
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0 tech-avail-table">
                        <thead>
                            <tr>
                                <th>Civilization</th>
                                <?php foreach ($techTableShared as $tech): ?>
                                    <th class="text-center" title="<?= Html::encode($tech->name) ?>">
                                        <img src="<?= Html::encode($techIcon($tech->name)) ?>" alt="<?= Html::encode($tech->name) ?>" class="tech-icon tech-icon-bg">
                                    </th>
                                <?php endforeach; ?>
                                <?php if ($techTableUnique): ?>
                                    <th>Unique</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($techTableCivs as $civ): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-1 text-nowrap">
                                            <?php if ($civ->emblemUrl): ?>
                                                <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="civ-emblem-sm">
                                            <?php endif; ?>
                                            <?= Html::a(Html::encode($civ->name), ['civilization/view', 'slug' => $civ->slug]) ?>
                                        </div>
                                    </td>
                                    <?php foreach ($techTableShared as $tech): ?>
                                        <td class="text-center" title="<?= Html::encode($tech->name) ?>">
                                            <?php if (isset($techCivIds[$tech->name][$civ->id])): ?>
                                                <span class="avail-yes">&#10003;</span>
                                            <?php else: ?>
                                                <span class="avail-no">&#10007;</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <?php if ($techTableUnique): ?>
                                        <td class="text-nowrap">
                                            <?php foreach ($techTableUnique[$civ->id] ?? [] as $tech): ?>
                                                <small class="d-flex align-items-center gap-1">
                                                    <img src="<?= Html::encode($techIcon($tech->name)) ?>" alt="" class="tech-icon tech-icon-bg" title="<?= Html::encode($tech->name) ?>">
                                                    <span class="tech-unique-name"><?= Html::encode($tech->name) ?></span>
                                                </small>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
    <?php endif; ?>
<?php endif; ?>

<?php
// Hover cards for pills that are not units: classes, unit groups, buildings, ages
UnitPills::registerTips($this);
?>
