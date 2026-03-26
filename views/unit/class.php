<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $classDef['name'];

// Build reverse map: armor class name → class slug (for badge links)
$armorToSlug = [];
foreach ($allClasses as $s => $def) {
    foreach ($def['armorClasses'] as $ac) {
        $armorToSlug[$ac] = $s;
    }
}

// Current class armor names for highlighting
$currentArmorNames = $classDef['armorClasses'];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a('Units', ['unit/index']) ?></li>
        <li class="breadcrumb-item"><?= Html::a('Classes', ['unit/classes']) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($classDef['name']) ?></li>
    </ol>
</nav>

<div class="admin5-page-header">
    <h1><?= Html::encode($classDef['name']) ?> <span class="badge bg-secondary badge-sm"><?= count($units) ?></span></h1>
    <?php if ($classDef['description']): ?>
        <p class="text-muted"><?= Html::encode($classDef['description']) ?></p>
    <?php endif; ?>
</div>

<div class="admin5-card admin5-card-border">
    <div class="table-responsive">
        <table class="table table-striped table-sm mb-0">
            <thead>
                <tr>
                    <th style="width: 40px"></th>
                    <th>Name</th>
                    <th>Age</th>
                    <th><img src="/images/stats/hit_points.png" alt="HP" class="stat-icon" title="Hit Points"></th>
                    <th><img src="/images/stats/melee_attack.png" alt="Atk" class="stat-icon" title="Attack"></th>
                    <th><img src="/images/stats/melee_armor.png" alt="Armor" class="stat-icon" title="Armor"></th>
                    <th><img src="/images/stats/movement_speed.png" alt="Speed" class="stat-icon" title="Speed"></th>
                    <th>Classes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($units as $unit): ?>
                    <tr>
                        <td>
                            <?php if ($unit->iconUrl): ?>
                                <img src="<?= Html::encode($unit->iconUrl) ?>" alt="" class="unit-image-sm">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= Html::a(Html::encode($unit->name), ['unit/view', 'slug' => $unit->slug], ['class' => 'fw-medium']) ?>
                            <?php if ($unit->is_unique): ?>
                                <span class="badge bg-warning badge-sm ms-1">Unique</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($unit->age): ?>
                                <span class="badge badge-age age-<?= strtolower(explode(' ', $unit->age)[0]) ?>"><?= Html::encode($unit->age) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $unit->hit_points ?? '-' ?></td>
                        <td><?= $unit->melee_attack ?? $unit->pierce_attack ?? '-' ?></td>
                        <td><?= $unit->armorString ?></td>
                        <td><?= $unit->speed ?? '-' ?></td>
                        <td>
                            <?php
                            $unitAC = $unitArmorClasses[$unit->id] ?? [];
                            // Filter out "Unique Units" / "Unique unit"
                            $unitAC = array_filter($unitAC, function ($ac) {
                                return !in_array($ac, ['Unique Units', 'Unique unit']);
                            });
                            foreach ($unitAC as $ac):
                                $isCurrent = in_array($ac, $currentArmorNames);
                                $badgeClass = $isCurrent ? 'bg-info' : 'bg-secondary';
                                if (isset($armorToSlug[$ac])):
                            ?>
                                    <?= Html::a(Html::encode($ac), ['unit/class', 'slug' => $armorToSlug[$ac]], ['class' => "badge badge-sm $badgeClass me-1 mb-1"]) ?>
                                <?php else: ?>
                                    <span class="badge badge-sm <?= $badgeClass ?> me-1 mb-1"><?= Html::encode($ac) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
