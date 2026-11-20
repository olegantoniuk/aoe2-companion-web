<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\Civilization;
use app\components\UnitPills;

$this->title = $building->name;
Seo::page($this, $building->name . ': Stats, Units and Technologies', $building->description ?: ($building->name . ' in Age of Empires II: cost, stats, units and technologies.'), $building->iconUrl);

$stat = function ($icon, $label, $value) {
    if ($value === null || $value === '') return '';
    $img = is_file(Yii::getAlias('@webroot') . '/images/stats/' . $icon . '.png')
        ? '<img src="/images/stats/' . $icon . '.png" alt="" class="stat-icon">' : '';
    return '<tr><th>' . Html::encode($label) . '</th><td>' . $img . Html::encode($value) . '</td></tr>';
};
$units = $building->trainedUnits;
$techs = $building->technologies;
[$availMode, $availNames] = $building->availabilityNote;
$civs = $availNames ? Civilization::find()->where(['name' => $availNames])->orderBy(['name' => SORT_ASC])->all() : [];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a('Buildings', ['building/index']) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($building->name) ?></li>
    </ol>
</nav>

<div class="unit-title">
    <?php if ($building->iconUrl): ?>
        <img src="<?= Html::encode($building->iconUrl) ?>" alt="" class="building-title-icon">
    <?php endif; ?>
    <div class="unit-title-text">
        <h1><?= Html::encode($building->name) ?></h1>
        <div class="pill-row">
            <?php if ($building->age): ?><?= UnitPills::agePill($building->age) ?><?php endif; ?>
            <?php if ($availMode === 'only' && count($civs) <= 6): ?>
                <?php foreach ($civs as $c): ?><?= UnitPills::civPill($c) ?><?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <?php
        $preview = $building->preview;
        $meta = json_decode(@file_get_contents(Yii::getAlias('@app/components/data/building_preview.json')) ?: '{}', true)[$building->slug] ?? null;
        ?>
        <?php if ($preview): ?>
            <?php
            [$pUrl, $pW, $pH] = $preview;
            $s = min(1.4, 240 / $pH, 330 / $pW);
            $foot = $meta['foot'] ?? [$pW / 2, $pH * 0.8];
            $groundY = round($foot[1] * $s) + 12;
            $stageH = $groundY + max(round(($pH - $foot[1]) * $s), 70) + 14;
            // the grass tile is drawn at 1.4 px per game pixel; scale it like the building
            $grass = $meta['scale'] ?? null ? round(512 / 1.4 * $meta['scale'] * $s) : 160;
            ?>
            <!-- the building standing on a patch of game grass, like the unit pages -->
            <div class="building-stage" style="height: <?= $stageH ?>px">
                <span class="building-ground" style="top: <?= $groundY - 75 ?>px; background-size: <?= $grass ?>px"></span>
                <img src="<?= Html::encode($pUrl) ?>" alt="<?= Html::encode($building->name) ?>"
                     width="<?= round($pW * $s) ?>" height="<?= round($pH * $s) ?>"
                     style="left: calc(50% - <?= round($foot[0] * $s) ?>px); top: <?= $groundY - round($foot[1] * $s) ?>px">
            </div>
        <?php endif; ?>
        <div class="admin5-card admin5-card-border">
            <div class="card-header"><strong>Stats</strong></div>
            <table class="table table-sm stat-table mb-0">
                <tr><th>Cost</th><td class="unit-list-cost"><?= $building->costHtml ?></td></tr>
                <?php if ($building->build_time): ?>
                    <tr><th>Build Time</th><td><img src="/images/stats/time.png" alt="" class="stat-icon"><?= (int)$building->build_time ?>s</td></tr>
                <?php endif; ?>
                <?= $stat('hit_points', 'Hit Points', $building->hit_points) ?>
                <?php if ($building->melee_armor !== null || $building->pierce_armor !== null): ?>
                    <?= $stat('melee_armor', 'Melee Armor', $building->melee_armor) ?>
                    <?= $stat('pierce_armor', 'Pierce Armor', $building->pierce_armor) ?>
                <?php endif; ?>
                <?= $stat('melee_attack', 'Attack', $building->attack) ?>
                <?= $stat('range', 'Range', $building->range_val) ?>
                <?= $stat('line_of_sight', 'Line of Sight', $building->line_of_sight) ?>
                <?= $stat('garrison', 'Garrison', $building->garrison) ?>
                <?php if ($building->size): ?><tr><th>Size</th><td><?= Html::encode($building->size) ?></td></tr><?php endif; ?>
                <?php if ($building->replaces): ?><tr><th>Replaces</th><td><?= Html::encode($building->replaces) ?></td></tr><?php endif; ?>
                <?php if ($building->requires): ?><tr><th>Requires</th><td><?= Html::encode($building->requires) ?></td></tr><?php endif; ?>
            </table>
        </div>
        <?php if ($civs && ($availMode === 'except' || count($civs) > 6)): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong><?= $availMode === 'except' ? 'Every civilization except' : 'Available to' ?></strong></div>
                <div class="card-content pill-row">
                    <?php foreach ($civs as $c): ?><?= UnitPills::civPill($c) ?><?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-8">
        <?php if ($building->description): ?>
            <div class="unit-description kb-prose"><p><?= UnitPills::linkify(Html::encode($building->description)) ?></p></div>
        <?php endif; ?>

        <?php if ($units): ?>
            <div class="admin5-card admin5-card-border building-trains">
                <div class="card-header"><strong>Trains</strong> <span class="badge bg-secondary badge-sm"><?= count($units) ?></span></div>
                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0 unit-list-table">
                        <thead><?= UnitPills::unitTableHead('Class') ?></thead>
                        <tbody>
                            <?php foreach ($units as $u): ?>
                                <tr><?= UnitPills::unitRowCells($u) ?></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($techs): ?>
    <h2 class="unit-section-title">Technologies <span class="badge bg-secondary badge-sm"><?= count($techs) ?></span></h2>
    <?= $this->render('//technology/_cards', ['techs' => $techs]) ?>
<?php endif; ?>

<?php UnitPills::registerTips($this); ?>
