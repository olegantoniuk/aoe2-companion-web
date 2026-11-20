<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\components\UnitPills;

$this->title = $civ->name;
Seo::page($this, $civ->name . ': Bonuses, Unique Units and How to Play', ($civ->intro ?: $civ->summary) ?: ($civ->name . ' in Age of Empires II: civilization bonuses, team bonus, unique units and technologies.'), $civ->emblemUrl);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a('Civilizations', ['civilization/index']) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($civ->name) ?></li>
    </ol>
</nav>

<div class="admin5-page-header">
    <div class="d-flex align-items-center gap-3">
        <?php if ($civ->emblemUrl): ?>
            <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="<?= Html::encode($civ->name) ?>" class="civ-emblem-lg">
        <?php endif; ?>
        <h1 style="margin: 0;"><?= Html::encode($civ->name) ?></h1>
    </div>
    <?php if ($civ->hasAttribute('intro') && $civ->intro): ?>
        <div class="civ-intro-wrap"><p class="civ-intro"><?= UnitPills::linkify(Html::encode($civ->intro), $civ->slug) ?></p></div>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Civ info -->
    <div class="col-lg-4 mb-4">
        <div class="admin5-card admin5-card-border">
            <div class="card-header"><strong>Overview</strong></div>
            <table class="table table-sm stat-table mb-0">
                <?php if ($civ->focus): ?>
                    <tr><th>Focus</th><td><?= Html::encode(implode(", ", $civ->focusList)) ?></td></tr>
                <?php endif; ?>
                <?php if ($civ->architecture_set): ?>
                    <tr><th>Architecture</th><td><?= Html::encode($civ->architecture_set) ?></td></tr>
                <?php endif; ?>
                <?php if ($civ->continent): ?>
                    <tr><th>Continent</th><td><?= Html::encode($civ->continent) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Bonuses -->
        <?php $bonuses = $civ->bonuses; ?>
        <?php if ($bonuses): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Civilization Bonuses</strong></div>
                <div class="card-content">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($bonuses as $bonus): ?>
                            <li><?= Html::encode($bonus->bonus_text) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Team Bonus -->
        <?php if ($civ->team_bonus): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Team Bonus</strong></div>
                <div class="card-content">
                    <?= Html::encode($civ->team_bonus) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Unique Technologies -->
        <?php $techs = $civ->technologies; ?>
        <?php if ($techs): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Unique Technologies</strong></div>
                <div class="card-content">
                    <?php foreach ($techs as $tech): ?>
                        <div class="mb-2 d-flex align-items-start gap-2">
                            <?php
                            $techFile = str_replace([' ', "'", '*', '(', ')', '-'], '', $tech->name) . '.png';
                            $techPath = Yii::getAlias('@webroot') . '/images/techs/' . $techFile;
                            $techSrc = file_exists($techPath) ? '/images/techs/' . $techFile : '/images/techs/UniqueTechCastle.png';
                            ?>
                            <img src="<?= Html::encode($techSrc) ?>" alt="" class="tech-icon tech-icon-bg" style="flex-shrink: 0;">
                            <div>
                                <strong><?= Html::encode($tech->name) ?></strong>
                                <?php if ($tech->description): ?>
                                    <br><small class="text-muted"><?= Html::encode($tech->description) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Units -->
    <div class="col-lg-8">
        <!-- Unique Units -->
        <?php if ($uniqueUnits): ?>
            <div class="admin5-page-header" style="margin-bottom: 16px;">
                <h3 style="margin: 0;">Unique Units</h3>
            </div>
            <div class="row g-3 mb-4">
                <?php
                // each unique unit, then its elite upgrade as a card of its own
                $uniqueCards = [];
                // real unique units with their elite versions first, civilization-only upgrades (Houfnice) last
                usort($uniqueUnits, function ($a, $b) {
                    return [(int)!$a->is_unique, $a->name] <=> [(int)!$b->is_unique, $b->name];
                });
                foreach ($uniqueUnits as $unit) {
                    $uniqueCards[] = [
                        'name' => $unit->name, 'icon' => $unit->iconUrl, 'age' => $unit->age, 'unit' => $unit,
                        'hp' => $unit->hit_points, 'melee' => $unit->melee_attack, 'pierce' => $unit->pierce_attack,
                        'marmor' => $unit->melee_armor, 'parmor' => $unit->pierce_armor, 'speed' => $unit->speed,
                        'url' => Url::to(['unit/view', 'slug' => $unit->slug]), 'elite' => false,
                    ];
                    $ev = $unit->eliteData;
                    if ($ev) {
                        $uniqueCards[] = [
                            'name' => $ev['name'], 'icon' => UnitPills::iconForName($ev['name']) ?: $unit->iconUrl,
                            'age' => 'Imperial Age', 'unit' => $unit,
                            'hp' => $ev['hit_points'] ?? $unit->hit_points,
                            'melee' => array_key_exists('melee_attack', $ev) ? $ev['melee_attack'] : $unit->melee_attack,
                            'pierce' => array_key_exists('pierce_attack', $ev) ? $ev['pierce_attack'] : $unit->pierce_attack,
                            'marmor' => $ev['melee_armor'] ?? $unit->melee_armor, 'parmor' => $ev['pierce_armor'] ?? $unit->pierce_armor,
                            'speed' => $ev['speed'] ?? $unit->speed,
                            'url' => Url::to(['unit/view', 'slug' => $unit->slug, '#' => 'combat-stats']), 'elite' => true,
                        ];
                    }
                }
                ?>
                <?php foreach ($uniqueCards as $c): ?>
                    <div class="col-md-6">
                        <a href="<?= $c['url'] ?>" class="civ-card-link">
                            <div class="admin5-card admin5-card-border unique-card<?= $c['elite'] ? ' is-elite' : '' ?>" style="margin-bottom: 0;">
                                <div class="card-content">
                                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <?= UnitPills::avatar($c['icon']) ?>
                                            <span>
                                                <span class="unit-list-name"><?= Html::encode($c['name']) ?></span>
                                                <?php if ($c['elite']): ?>
                                                    <span class="unique-card-upgrade">Upgrade of <?= Html::encode($c['unit']->name) ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <?php if ($c['age']): ?><?= UnitPills::agePill($c['age']) ?><?php endif; ?>
                                    </div>
                                    <div class="unique-card-stats">
                                        <span><img src="/images/stats/hit_points.png" alt="" class="stat-icon"><?= $c['hp'] ?? '?' ?></span>
                                        <span><img src="/images/stats/<?= $c['melee'] !== null ? 'melee_attack' : 'pierce_attack' ?>.png" alt="" class="stat-icon"><?= $c['melee'] ?? $c['pierce'] ?? '?' ?></span>
                                        <span><img src="/images/stats/melee_armor.png" alt="" class="stat-icon"><?= (int)$c['marmor'] ?>/<?= (int)$c['parmor'] ?></span>
                                        <?php if ($c['speed']): ?><span><img src="/images/stats/movement_speed.png" alt="" class="stat-icon"><?= $c['speed'] ?></span><?php endif; ?>
                                        <?php if ($c['elite']): ?>
                                            <span class="unit-list-cost" title="Elite upgrade cost"><?= $c['unit']->upgradeCostHtml ?></span>
                                        <?php else: ?>
                                            <span class="unit-list-cost"><?= $c['unit']->costHtml ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- how to play it: the guide and the civilization's own build orders -->
        <?php if ($guide || $civBuilds): ?>
            <div class="admin5-page-header" style="margin-bottom: 16px;">
                <h3 style="margin: 0;">Guide and Build Orders</h3>
            </div>
            <div class="home-kb-list civ-kb-list mb-4">
                <?php if ($guide): ?>
                    <a href="<?= Url::to(['guide/view', 'slug' => $guide->slug]) ?>" class="admin5-card admin5-card-border home-kb-item">
                        <img src="<?= Html::encode(UnitPills::pageIconUrl('guides')) ?>" alt="" class="home-kb-avatar">
                        <span class="home-kb-title"><?= Html::encode($guide->title) ?></span>
                        <?= UnitPills::eloPill($guide->elo) ?>
                    </a>
                <?php endif; ?>
                <?php foreach ($civBuilds as $b): ?>
                    <a href="<?= Url::to(['build/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border home-kb-item">
                        <img src="<?= Html::encode(UnitPills::pageIconUrl('builds')) ?>" alt="" class="home-kb-avatar">
                        <span class="home-kb-title"><?= Html::encode($b->title) ?></span>
                        <?= UnitPills::eloPill($b->elo) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Available Shared Units -->
        <?php if ($availableUnits): ?>
            <?php
            // Group units by type
            $typeOrder = ['Infantry', 'Cavalry', 'Archer', 'Siege', 'Naval', 'Monk', 'Gunpowder', 'Other'];
            $byType = [];
            foreach ($availableUnits as $unit) {
                $group = $unit->armorClassGroup;
                $byType[$group][] = $unit;
            }
            $byType = array_merge(array_flip($typeOrder), $byType);
            $byType = array_filter($byType, 'is_array');

            // Group units by building, sorted by upgrade line
            $buildingOrder = ['Barracks', 'Stable', 'Archery Range', 'Siege Workshop', 'Castle', 'Dock', 'Monastery', 'Town Center', 'Market', 'Other'];
            $byBuilding = [];
            foreach ($availableUnits as $unit) {
                $group = $unit->buildingGroup;
                $byBuilding[$group][] = $unit;
            }
            $byBuilding = array_merge(array_flip($buildingOrder), $byBuilding);
            $byBuilding = array_filter($byBuilding, 'is_array');

            // Sort each building group by upgrade line (Dark Age → Imperial Age chains)
            $ageRank = ['Dark Age' => 1, 'Feudal Age' => 2, 'Castle Age' => 3, 'Imperial Age' => 4];
            foreach ($byBuilding as $group => &$units) {
                // Build name→unit lookup
                $byName = [];
                foreach ($units as $u) {
                    $byName[$u->name] = $u;
                }

                // Find root units (no upgrades_from within this group)
                $roots = [];
                foreach ($units as $u) {
                    if (empty($u->upgrades_from) || !isset($byName[$u->upgrades_from])) {
                        $roots[] = $u;
                    }
                }

                // Sort roots by age
                usort($roots, function ($a, $b) use ($ageRank) {
                    $aAge = preg_replace('/\s+(before|since).*/', '', $a->age ?? '');
                    $bAge = preg_replace('/\s+(before|since).*/', '', $b->age ?? '');
                    $aR = $ageRank[$aAge] ?? 5;
                    $bR = $ageRank[$bAge] ?? 5;
                    return $aR !== $bR ? $aR - $bR : strcasecmp($a->name, $b->name);
                });

                // Build ordered list following chains
                $sorted = [];
                $visited = [];
                foreach ($roots as $root) {
                    $current = $root;
                    while ($current && !isset($visited[$current->name])) {
                        $visited[$current->name] = true;
                        $sorted[] = $current;
                        // Follow upgrades_to (may contain extra text like "Dragon Ship (Chinese only)")
                        $nextName = $current->upgrades_to;
                        $current = null;
                        if ($nextName) {
                            // Try exact match first, then partial
                            if (isset($byName[$nextName])) {
                                $current = $byName[$nextName];
                            } else {
                                foreach ($byName as $n => $u) {
                                    if (strpos($nextName, $n) === 0 && !isset($visited[$n])) {
                                        $current = $u;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                // Add any remaining units not in chains
                foreach ($units as $u) {
                    if (!isset($visited[$u->name])) {
                        $sorted[] = $u;
                    }
                }

                $units = $sorted;
            }
            unset($units);

            // Group by age
            $ageOrder = ['Dark Age', 'Feudal Age', 'Castle Age', 'Imperial Age'];
            $byAge = [];
            foreach ($availableUnits as $unit) {
                $cleanAge = preg_replace('/\s+(before|since).*/', '', $unit->age ?? '');
                $ageKey = in_array($cleanAge, $ageOrder) ? $cleanAge : 'Other';
                $byAge[$ageKey][] = $unit;
            }
            $byAge = array_merge(array_flip($ageOrder), $byAge);
            $byAge = array_filter($byAge, 'is_array');
            foreach ($byAge as &$ageUnits) {
                usort($ageUnits, function ($a, $b) { return strcasecmp($a->name, $b->name); });
            }
            unset($ageUnits);

            // Alphabetical
            $alphabetical = $availableUnits;
            usort($alphabetical, function ($a, $b) {
                return strcasecmp($a->name, $b->name);
            });

            // Building icon mapping
            $buildingIcons = [
                'Barracks' => '/images/buildings/Barracks.png',
                'Stable' => '/images/buildings/Stable.png',
                'Archery Range' => '/images/buildings/ArcheryRange.png',
                'Siege Workshop' => '/images/buildings/SiegeWorkshop.png',
                'Castle' => '/images/buildings/Castle.png',
                'Dock' => '/images/buildings/Dock.png',
                'Monastery' => '/images/buildings/Monastery.png',
                'Town Center' => '/images/buildings/TownCenter.png',
                'Market' => '/images/buildings/Market.png',
                'Donjon' => '/images/buildings/Donjon.png',
                'Krepost' => '/images/buildings/Krepost.png',
                'Harbor' => '/images/buildings/Harbor.png',
                'Folwark' => '/images/buildings/Folwark.png',
            ];
            ?>

            <div class="admin5-page-header" style="margin-bottom: 16px;">
                <h3 style="margin: 0;">Available Units <span class="badge bg-secondary badge-sm"><?= count($availableUnits) ?></span></h3>
            </div>

            <input type="text" class="unit-search-input mb-3" id="unitSearch" placeholder="Search units..." autocomplete="off">

            <div class="unit-tabs">
                <ul class="nav nav-tabs mb-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-class" type="button" role="tab">By Class</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-building" type="button" role="tab">By Building</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-age" type="button" role="tab">By Age</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-az" type="button" role="tab">A-Z</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- By Class -->
                    <div class="tab-pane fade show active" id="tab-class" role="tabpanel">
                        <div class="admin5-card admin5-card-border admin5-card-attached">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 unit-list-table">
                                    <thead><?= UnitPills::unitTableHead('Class', ['third']) ?></thead>
                                    <?php foreach ($byType as $group => $units): ?>
                                        <tbody class="unit-group" data-group="<?= Html::encode($group) ?>">
                                            <tr class="unit-group-row">
                                                <td colspan="<?= UnitPills::tableCols(['third']) ?>" class="unit-group-header"><span class="unit-group-name"><?= Html::encode($group) ?></span> <span class="badge bg-secondary badge-sm group-count"><?= count($units) ?></span></td>
                                            </tr>
                                            <?php foreach ($units as $unit): ?>
                                                <tr class="unit-row" data-name="<?= Html::encode(strtolower($unit->name)) ?>"><?= UnitPills::unitRowCells($unit, 'class', ['third']) ?></tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- By Building -->
                    <div class="tab-pane fade" id="tab-building" role="tabpanel">
                        <div class="admin5-card admin5-card-border admin5-card-attached">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 unit-list-table">
                                    <thead><?= UnitPills::unitTableHead('Building', ['third']) ?></thead>
                                    <?php foreach ($byBuilding as $group => $units): ?>
                                        <tbody class="unit-group" data-group="<?= Html::encode($group) ?>">
                                            <tr class="unit-group-row">
                                                <td colspan="<?= UnitPills::tableCols(['third']) ?>" class="unit-group-header">
                                                    <?php if (isset($buildingIcons[$group])): ?>
                                                        <img src="<?= Html::encode($buildingIcons[$group]) ?>" alt="" class="unit-image-xs" style="margin-right: 4px;">
                                                    <?php endif; ?>
                                                    <span class="unit-group-name"><?= Html::encode($group) ?></span> <span class="badge bg-secondary badge-sm group-count"><?= count($units) ?></span>
                                                </td>
                                            </tr>
                                            <?php foreach ($units as $unit): ?>
                                                <tr class="unit-row" data-name="<?= Html::encode(strtolower($unit->name)) ?>"><?= UnitPills::unitRowCells($unit, 'building', ['third']) ?></tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- By Age -->
                    <div class="tab-pane fade" id="tab-age" role="tabpanel">
                        <div class="admin5-card admin5-card-border admin5-card-attached">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 unit-list-table">
                                    <thead><?= UnitPills::unitTableHead('Class', ['age']) ?></thead>
                                    <?php foreach ($byAge as $group => $units): ?>
                                        <tbody class="unit-group" data-group="<?= Html::encode($group) ?>">
                                            <tr class="unit-group-row">
                                                <td colspan="<?= UnitPills::tableCols(['age']) ?>" class="unit-group-header">
                                                    <?= UnitPills::agePill($group) ?>
                                                    <span class="badge bg-secondary badge-sm group-count"><?= count($units) ?></span>
                                                </td>
                                            </tr>
                                            <?php foreach ($units as $unit): ?>
                                                <tr class="unit-row" data-name="<?= Html::encode(strtolower($unit->name)) ?>"><?= UnitPills::unitRowCells($unit, 'class', ['age']) ?></tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- A-Z -->
                    <div class="tab-pane fade" id="tab-az" role="tabpanel">
                        <div class="admin5-card admin5-card-border admin5-card-attached">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm mb-0 unit-list-table">
                                    <thead><?= UnitPills::unitTableHead('Class') ?></thead>
                                    <tbody>
                                        <?php foreach ($alphabetical as $unit): ?>
                                            <tr class="unit-row" data-name="<?= Html::encode(strtolower($unit->name)) ?>"><?= UnitPills::unitRowCells($unit) ?></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Unavailable Shared Units -->
        <?php if ($unavailableUnits): ?>
            <details class="mt-3">
                <summary class="text-muted mb-2" style="cursor: pointer; font-weight: 600;">
                    Unavailable Units (<?= count($unavailableUnits) ?>)
                </summary>
                <div class="admin5-card admin5-card-border">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php foreach ($unavailableUnits as $unit): ?>
                                    <tr class="text-muted">
                                        <td class="unit-unavailable-name">
                                            <a href="<?= Url::to(['unit/view', 'slug' => $unit->slug]) ?>" class="unit-avatar-link"><?= UnitPills::avatar($unit->iconUrl) ?></a>
                                            <?= Html::a(Html::encode($unit->name), ['unit/view', 'slug' => $unit->slug], ['class' => 'text-muted']) ?>
                                        </td>
                                        <td><?php $mc = UnitPills::mainClass($unit); ?><?= $mc ? UnitPills::classPill($mc) : '' ?></td>
                                        <td><span class="avail-no">Unavailable</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>
        <?php endif; ?>
    </div>
</div>

<?php UnitPills::registerTips($this); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var search = document.getElementById('unitSearch');
    if (!search) return;

    search.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        // Filter all unit rows across all tabs
        document.querySelectorAll('.unit-row').forEach(function (row) {
            var name = row.getAttribute('data-name') || '';
            row.style.display = name.indexOf(q) !== -1 ? '' : 'none';
        });
        // Hide group headers if all their rows are hidden
        document.querySelectorAll('.unit-group').forEach(function (group) {
            var rows = group.querySelectorAll('.unit-row');
            var visible = 0;
            rows.forEach(function (r) { if (r.style.display !== 'none') visible++; });
            var header = group.querySelector('.unit-group-row');
            if (header) {
                header.style.display = visible > 0 ? '' : 'none';
            }
            // Update count in header
            if (header && visible > 0 && q) {
                var countEl = header.querySelector('.group-count');
                if (countEl) countEl.textContent = visible;
            } else if (header && !q) {
                var countEl = header.querySelector('.group-count');
                if (countEl) countEl.textContent = rows.length;
            }
        });
    });
});
</script>
