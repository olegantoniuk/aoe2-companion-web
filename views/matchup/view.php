<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

$this->title = $yourCiv->name . ' vs ' . $enemyCiv->name;

// Count land lines for All tab
$allLandCount = 0;
foreach ($upgradeLines as $line) {
    $allLandCount += count($line['units']);
}
$allNavalCount = 0;
foreach ($navalLines as $line) {
    $allNavalCount += count($line['units']);
}

// Helper: format unit type display (fix "CavalryScout unit" → "Cavalry scout")
$formatType = function ($type) {
    if (empty($type)) return '';
    $type = preg_replace_callback('/([a-z])([A-Z])/', function ($m) {
        return $m[1] . ' ' . strtolower($m[2]);
    }, $type);
    $type = preg_replace('/\s+unit$/i', '', $type);
    return $type;
};

// Collect all unique counter units for popup data
$popupUnits = [];
$collectCounters = function ($counterList) use (&$popupUnits, $formatType) {
    foreach ($counterList as $cu) {
        if (!isset($popupUnits[$cu->id])) {
            $cost = [];
            if ($cu->cost_food) $cost['Food'] = (int)$cu->cost_food;
            if ($cu->cost_wood) $cost['Wood'] = (int)$cu->cost_wood;
            if ($cu->cost_gold) $cost['Gold'] = (int)$cu->cost_gold;
            if ($cu->cost_stone) $cost['Stone'] = (int)$cu->cost_stone;
            $strong = $cu->strong_against ? json_decode($cu->strong_against, true) : [];
            $weak = $cu->weak_against ? json_decode($cu->weak_against, true) : [];
            // Attack bonuses
            $bonuses = [];
            foreach ($cu->attackBonuses as $ab) {
                if ((int)$ab->bonus > 0) {
                    $bonuses[] = ['vs' => $ab->vs, 'bonus' => (int)$ab->bonus];
                }
            }
            usort($bonuses, function ($a, $b) { return $b['bonus'] - $a['bonus']; });
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
                'isNaval' => $cu->typeGroup === 'Naval',
            ];
        }
    }
};

// Collect from all sources
foreach ($upgradeLines as $line) $collectCounters($line['counters']);
foreach ($navalLines as $line) $collectCounters($line['counters']);
foreach ($counters as $cuList) $collectCounters($cuList);

// Also collect enemy units for popup on Enemy Unit column
$allEnemyUnits = [];
foreach ($upgradeLines as $line) $allEnemyUnits = array_merge($allEnemyUnits, $line['units']);
foreach ($navalLines as $line) $allEnemyUnits = array_merge($allEnemyUnits, $line['units']);
$collectCounters($allEnemyUnits);
// Collect building counter units for popup
$collectCounters($buildingCounters['units']);

// Helper to render counter link with bonus badge and naval class
$renderCounterLink = function ($cu, $enemyIds = [], $counterBonuses = []) {
    $icon = $cu->iconUrl ? '<img src="' . Html::encode($cu->iconUrl) . '" alt="" class="unit-image-xs">' : '';
    $bonusBadge = '';
    $extraClass = '';
    // Check if this counter has a direct attack bonus vs any of the enemy units
    foreach ($enemyIds as $eid) {
        if (isset($counterBonuses[$eid][$cu->id])) {
            $b = $counterBonuses[$eid][$cu->id];
            $bonusBadge = ' <span class="counter-bonus-badge">+' . $b['bonus'] . ' vs ' . Html::encode($b['vs']) . '</span>';
            $extraClass = ' counter-has-bonus';
            break;
        }
    }
    $navalClass = ($cu->typeGroup === 'Naval') ? ' counter-naval' : '';
    return '<a href="' . Url::to(['unit/view', 'slug' => $cu->slug]) . '" class="counter-unit-link' . $extraClass . $navalClass . '" data-unit-id="' . $cu->id . '">'
        . $icon . Html::encode($cu->name) . $bonusBadge . '</a>';
};

// Helper to render building counter link
$renderBuildingCounterLink = function ($cu) use ($buildingCounters) {
    $icon = $cu->iconUrl ? '<img src="' . Html::encode($cu->iconUrl) . '" alt="" class="unit-image-xs">' : '';
    $bonusBadge = '';
    $extraClass = '';
    if (isset($buildingCounters['bonuses'][$cu->id])) {
        $b = $buildingCounters['bonuses'][$cu->id];
        $bonusBadge = ' <span class="counter-bonus-badge">+' . $b['bonus'] . '</span>';
        $extraClass = ' counter-has-bonus';
    }
    $navalClass = ($cu->typeGroup === 'Naval') ? ' counter-naval' : '';
    return '<a href="' . Url::to(['unit/view', 'slug' => $cu->slug]) . '" class="counter-unit-link' . $extraClass . $navalClass . '" data-unit-id="' . $cu->id . '">'
        . $icon . Html::encode($cu->name) . $bonusBadge . '</a>';
};
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= Url::to(['matchup/index']) ?>">Matchup</a></li>
        <li class="breadcrumb-item active"><?= Html::encode($yourCiv->name) ?> vs <?= Html::encode($enemyCiv->name) ?></li>
    </ol>
</nav>

<!-- Header -->
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <?php if ($yourCiv->emblemUrl): ?>
        <img src="<?= Html::encode($yourCiv->emblemUrl) ?>" alt="" class="civ-emblem-lg">
    <?php endif; ?>
    <div>
        <h1 class="mb-0" style="font-size: 1.75rem;">
            <a href="<?= Url::to(['civilization/view', 'slug' => $yourCiv->slug]) ?>"><?= Html::encode($yourCiv->name) ?></a>
            <span style="color: #6c757d; font-weight: 400;"> vs </span>
            <a href="<?= Url::to(['civilization/view', 'slug' => $enemyCiv->slug]) ?>"><?= Html::encode($enemyCiv->name) ?></a>
        </h1>
        <p class="text-muted mb-0">Your counters against enemy units</p>
    </div>
    <?php if ($enemyCiv->emblemUrl): ?>
        <img src="<?= Html::encode($enemyCiv->emblemUrl) ?>" alt="" class="civ-emblem-lg">
    <?php endif; ?>
</div>

<!-- Civ selector -->
<div class="admin5-card admin5-card-border mb-4">
    <div class="card-content">
        <div class="row g-3 align-items-end">
            <div class="col-sm-5">
                <label class="form-label fw-semibold" for="yourCivSel">Your Civilization</label>
                <select id="yourCivSel" class="form-select">
                    <?php foreach ($civs as $civ): ?>
                        <option value="<?= Html::encode($civ->slug) ?>" <?= $civ->slug === $yourCiv->slug ? 'selected' : '' ?>><?= Html::encode($civ->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2 text-center" style="font-weight: 700; font-size: 1.25rem; color: #6c757d; padding-bottom: 6px;">
                vs
            </div>
            <div class="col-sm-5">
                <label class="form-label fw-semibold" for="enemyCivSel">Enemy Civilization</label>
                <select id="enemyCivSel" class="form-select">
                    <?php foreach ($civs as $civ): ?>
                        <option value="<?= Html::encode($civ->slug) ?>" <?= $civ->slug === $enemyCiv->slug ? 'selected' : '' ?>><?= Html::encode($civ->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Naval toggle -->
<div class="mb-3">
    <label class="form-check-label" style="cursor: pointer; font-size: 14px; font-weight: 500; color: #6c757d;">
        <input type="checkbox" id="showNaval" class="form-check-input" style="cursor: pointer;">
        Show Naval Units
    </label>
</div>

<!-- Age tabs -->
<div class="unit-tabs mb-3">
    <ul class="nav nav-tabs" id="ageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-all" data-bs-toggle="tab" data-bs-target="#pane-all" type="button" role="tab">
                All
                <span class="badge badge-sm bg-secondary" id="allCount"><?= $allLandCount ?></span>
            </button>
        </li>
        <?php foreach ($byAge as $age => $units): ?>
            <?php
            $tabId = strtolower(str_replace(' ', '-', $age));
            $ageClass = '';
            if (strpos($age, 'Feudal') !== false) $ageClass = 'age-feudal';
            elseif (strpos($age, 'Castle') !== false) $ageClass = 'age-castle';
            elseif (strpos($age, 'Imperial') !== false) $ageClass = 'age-imperial';
            $navalCount = isset($navalByAge[$age]) ? count($navalByAge[$age]) : 0;
            ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-<?= $tabId ?>" data-bs-toggle="tab" data-bs-target="#pane-<?= $tabId ?>"
                        type="button" role="tab"
                        data-land-count="<?= count($units) ?>" data-naval-count="<?= $navalCount ?>">
                    <?= Html::encode($age) ?>
                    <span class="badge badge-sm <?= $ageClass ?> age-count"><?= count($units) ?></span>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Tab content -->
<div class="tab-content">

    <!-- ALL tab (grouped upgrade lines) -->
    <div class="tab-pane fade show active" id="pane-all" role="tabpanel">
        <div class="admin5-card admin5-card-border">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th style="width: 48px;"></th>
                        <th>Enemy Unit</th>
                        <th>Your Counters</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upgradeLines as $line): ?>
                        <?php
                        $lineUnits = $line['units'];
                        $lineCounters = $line['counters'];
                        $lastUnit = end($lineUnits);
                        $isMulti = count($lineUnits) > 1;
                        ?>
                        <tr>
                            <td>
                                <?php if ($lastUnit->iconUrl): ?>
                                    <img src="<?= Html::encode($lastUnit->iconUrl) ?>" alt="" class="unit-image-md enemy-unit-hover" data-unit-id="<?= $lastUnit->id ?>">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isMulti): ?>
                                    <div style="font-weight: 600;">
                                        <?php foreach ($lineUnits as $i => $lu): ?>
                                            <?php if ($i > 0): ?> <span style="color: #6c757d; font-weight: 400;">&rarr;</span> <?php endif; ?>
                                            <a href="<?= Url::to(['unit/view', 'slug' => $lu->slug]) ?>" class="enemy-unit-hover" data-unit-id="<?= $lu->id ?>" style="font-weight: 600;"><?= Html::encode($lu->name) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <a href="<?= Url::to(['unit/view', 'slug' => $lastUnit->slug]) ?>" class="enemy-unit-hover" data-unit-id="<?= $lastUnit->id ?>" style="font-weight: 600;">
                                        <?= Html::encode($lastUnit->name) ?>
                                    </a>
                                    <?php if ($lastUnit->is_unique): ?>
                                        <span class="badge bg-warning badge-sm ms-1">Unique</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($lastUnit->type): ?>
                                    <br><small class="text-muted"><?= Html::encode($formatType($lastUnit->type)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($lineCounters)): ?>
                                    <?php $lineEnemyIds = array_map(function ($u) { return $u->id; }, $lineUnits); ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($lineCounters as $cu): ?>
                                            <?= $renderCounterLink($cu, $lineEnemyIds, $counterBonuses) ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!empty($buildingCounters['units'])): ?>
                        <tr>
                            <td>
                                <img src="/images/buildings/House.png" alt="Building" class="unit-image-md">
                            </td>
                            <td>
                                <span style="font-weight: 600;">Building</span>
                                <br><small class="text-muted">Structures</small>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($buildingCounters['units'] as $cu): ?>
                                        <?= $renderBuildingCounterLink($cu) ?>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($navalLines as $line): ?>
                        <?php
                        $lineUnits = $line['units'];
                        $lineCounters = $line['counters'];
                        $lastUnit = end($lineUnits);
                        $isMulti = count($lineUnits) > 1;
                        ?>
                        <tr class="naval-unit-row" style="display: none;">
                            <td>
                                <?php if ($lastUnit->iconUrl): ?>
                                    <img src="<?= Html::encode($lastUnit->iconUrl) ?>" alt="" class="unit-image-md enemy-unit-hover" data-unit-id="<?= $lastUnit->id ?>">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isMulti): ?>
                                    <div style="font-weight: 600;">
                                        <?php foreach ($lineUnits as $i => $lu): ?>
                                            <?php if ($i > 0): ?> <span style="color: #6c757d; font-weight: 400;">&rarr;</span> <?php endif; ?>
                                            <a href="<?= Url::to(['unit/view', 'slug' => $lu->slug]) ?>" class="enemy-unit-hover" data-unit-id="<?= $lu->id ?>" style="font-weight: 600;"><?= Html::encode($lu->name) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <a href="<?= Url::to(['unit/view', 'slug' => $lastUnit->slug]) ?>" class="enemy-unit-hover" data-unit-id="<?= $lastUnit->id ?>" style="font-weight: 600;">
                                        <?= Html::encode($lastUnit->name) ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($lastUnit->type): ?>
                                    <br><small class="text-muted"><?= Html::encode($formatType($lastUnit->type)) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($lineCounters)): ?>
                                    <?php $lineEnemyIds = array_map(function ($u) { return $u->id; }, $lineUnits); ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($lineCounters as $cu): ?>
                                            <?= $renderCounterLink($cu, $lineEnemyIds, $counterBonuses) ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Age tabs (Feudal/Castle/Imperial) -->
    <?php foreach ($byAge as $age => $units): ?>
        <?php $tabId = strtolower(str_replace(' ', '-', $age)); ?>
        <div class="tab-pane fade" id="pane-<?= $tabId ?>" role="tabpanel">
            <div class="admin5-card admin5-card-border">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 48px;"></th>
                            <th>Enemy Unit</th>
                            <th>Your Counters</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units as $eu): ?>
                            <tr>
                                <td>
                                    <?php if ($eu->iconUrl): ?>
                                        <img src="<?= Html::encode($eu->iconUrl) ?>" alt="" class="unit-image-md enemy-unit-hover" data-unit-id="<?= $eu->id ?>">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= Url::to(['unit/view', 'slug' => $eu->slug]) ?>" class="enemy-unit-hover" data-unit-id="<?= $eu->id ?>" style="font-weight: 600;">
                                        <?= Html::encode($eu->name) ?>
                                    </a>
                                    <?php if ($eu->is_unique): ?>
                                        <span class="badge bg-warning badge-sm ms-1">Unique</span>
                                    <?php endif; ?>
                                    <?php if ($eu->type): ?>
                                        <br><small class="text-muted"><?= Html::encode($formatType($eu->type)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($counters[$eu->id])): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($counters[$eu->id] as $cu): ?>
                                                <?= $renderCounterLink($cu, [$eu->id], $counterBonuses) ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (isset($navalByAge[$age])): ?>
                            <?php foreach ($navalByAge[$age] as $eu): ?>
                                <tr class="naval-unit-row" style="display: none;">
                                    <td>
                                        <?php if ($eu->iconUrl): ?>
                                            <img src="<?= Html::encode($eu->iconUrl) ?>" alt="" class="unit-image-md enemy-unit-hover" data-unit-id="<?= $eu->id ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= Url::to(['unit/view', 'slug' => $eu->slug]) ?>" class="enemy-unit-hover" data-unit-id="<?= $eu->id ?>" style="font-weight: 600;">
                                            <?= Html::encode($eu->name) ?>
                                        </a>
                                        <?php if ($eu->type): ?>
                                            <br><small class="text-muted"><?= Html::encode($formatType($eu->type)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($counters[$eu->id])): ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($counters[$eu->id] as $cu): ?>
                                                    <?= $renderCounterLink($cu, [$eu->id], $counterBonuses) ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Unit popup element -->
<div class="unit-popup" id="unitPopup"></div>

<?php
$baseUrl = Url::to(['matchup/view', 'yourCiv' => '__YOUR__', 'enemyCiv' => '__ENEMY__']);
$allLandCountJs = $allLandCount;
$allNavalCountJs = $allNavalCount;
$popupDataJson = Json::encode($popupUnits);
$this->registerJs(<<<JS
(function() {
    // Civ selector
    var yourSel = document.getElementById('yourCivSel');
    var enemySel = document.getElementById('enemyCivSel');
    function go() {
        if (!yourSel.value || !enemySel.value) return;
        var url = '$baseUrl'.replace('__YOUR__', yourSel.value).replace('__ENEMY__', enemySel.value);
        window.location.href = url;
    }
    yourSel.addEventListener('change', go);
    enemySel.addEventListener('change', go);

    // Naval toggle
    var navalCheckbox = document.getElementById('showNaval');
    var navalRows = document.querySelectorAll('.naval-unit-row');
    var allCountBadge = document.getElementById('allCount');
    var landCount = $allLandCountJs;
    var navalCount = $allNavalCountJs;

    var navalCounters = document.querySelectorAll('.counter-naval');
    navalCheckbox.addEventListener('change', function() {
        var show = this.checked;
        navalRows.forEach(function(row) {
            row.style.display = show ? '' : 'none';
        });
        navalCounters.forEach(function(el) {
            el.style.display = show ? '' : 'none';
        });
        allCountBadge.textContent = show ? (landCount + navalCount) : landCount;
        document.querySelectorAll('#ageTabs .nav-link[data-land-count]').forEach(function(btn) {
            var lc = parseInt(btn.getAttribute('data-land-count'));
            var nc = parseInt(btn.getAttribute('data-naval-count'));
            btn.querySelector('.age-count').textContent = show ? (lc + nc) : lc;
        });
    });

    // Unit popup on hover
    var popupData = $popupDataJson;
    var popup = document.getElementById('unitPopup');
    var hideTimer = null;
    var showTimer = null;

    function buildPopup(uid) {
        var u = popupData[uid];
        if (!u) return '';
        var h = '';
        // Portrait + name row
        h += '<div class="unit-popup-header">';
        if (u.icon) h += '<div class="unit-popup-portrait"><img src="' + u.icon + '" alt=""></div>';
        h += '<div class="unit-popup-info">';
        h += '<div class="unit-popup-name">' + u.name + '</div>';
        if (u.type) h += '<div class="unit-popup-type">' + u.type + '</div>';
        // Cost inline
        if (u.cost && Object.keys(u.cost).length) {
            h += '<div class="unit-popup-cost">';
            for (var res in u.cost) {
                h += '<span><img src="/images/resources/' + res + '.png" alt="" class="res-icon"> ' + u.cost[res] + '</span>';
            }
            h += '</div>';
        }
        h += '</div></div>';
        // Stats grid (2 columns)
        h += '<div class="unit-popup-body">';
        h += '<div class="unit-popup-stats-grid">';
        h += '<div class="unit-popup-stat"><span class="stat-label">HP</span><span class="stat-value">' + u.hp + '</span></div>';
        h += '<div class="unit-popup-stat"><span class="stat-label">Atk</span><span class="stat-value">' + u.attack + ' <small>' + u.attackType[0] + '</small></span></div>';
        h += '<div class="unit-popup-stat"><span class="stat-label">Armor</span><span class="stat-value">' + u.meleeArmor + '/' + u.pierceArmor + '</span></div>';
        if (u.range) h += '<div class="unit-popup-stat"><span class="stat-label">Range</span><span class="stat-value">' + u.range + '</span></div>';
        else if (u.speed) h += '<div class="unit-popup-stat"><span class="stat-label">Speed</span><span class="stat-value">' + u.speed + '</span></div>';
        h += '</div>';
        // Attack bonuses
        if (u.bonuses && u.bonuses.length) {
            h += '<div class="unit-popup-bonuses">';
            h += '<div class="unit-popup-section-title">Attack Bonuses</div>';
            for (var i = 0; i < u.bonuses.length; i++) {
                h += '<span class="unit-popup-bonus">+' + u.bonuses[i].bonus + ' vs ' + u.bonuses[i].vs + '</span>';
            }
            h += '</div>';
        }
        // Strong/Weak
        if ((u.strong && u.strong.length) || (u.weak && u.weak.length)) {
            h += '<div class="unit-popup-matchups">';
            if (u.strong && u.strong.length) {
                h += '<div class="unit-popup-matchup"><span class="matchup-good">Strong:</span> ' + u.strong.join(', ') + '</div>';
            }
            if (u.weak && u.weak.length) {
                h += '<div class="unit-popup-matchup"><span class="matchup-bad">Weak:</span> ' + u.weak.join(', ') + '</div>';
            }
            h += '</div>';
        }
        h += '</div>';
        return h;
    }

    function showPopup(link, uid) {
        popup.innerHTML = buildPopup(uid);
        if (!popup.innerHTML) return;
        popup.classList.add('visible');
        // Position popup
        var rect = link.getBoundingClientRect();
        var scrollY = window.pageYOffset || document.documentElement.scrollTop;
        var scrollX = window.pageXOffset || document.documentElement.scrollLeft;
        var popupW = 320;
        var left = rect.left + scrollX + rect.width / 2 - popupW / 2;
        // Keep within viewport
        if (left < 8) left = 8;
        if (left + popupW > document.documentElement.clientWidth - 8) {
            left = document.documentElement.clientWidth - popupW - 8;
        }
        popup.style.left = left + 'px';
        popup.style.top = (rect.top + scrollY - popup.offsetHeight - 8) + 'px';
        // If popup goes above viewport, show below
        if (rect.top - popup.offsetHeight - 8 < 0) {
            popup.style.top = (rect.bottom + scrollY + 8) + 'px';
        }
    }

    function hidePopup() {
        popup.classList.remove('visible');
    }

    document.addEventListener('mouseenter', function(e) {
        var link = e.target.closest('.counter-unit-link[data-unit-id], .enemy-unit-hover[data-unit-id]');
        if (!link) return;
        clearTimeout(hideTimer);
        clearTimeout(showTimer);
        showTimer = setTimeout(function() {
            showPopup(link, link.getAttribute('data-unit-id'));
        }, 200);
    }, true);

    document.addEventListener('mouseleave', function(e) {
        var link = e.target.closest('.counter-unit-link[data-unit-id], .enemy-unit-hover[data-unit-id]');
        if (!link) return;
        clearTimeout(showTimer);
        hideTimer = setTimeout(hidePopup, 150);
    }, true);
})();
JS
);
?>
