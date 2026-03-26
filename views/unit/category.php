<?php

use yii\helpers\Html;

$this->title = $categoryName;
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a('Units', ['unit/index']) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($categoryName) ?></li>
    </ol>
</nav>

<div class="admin5-page-header">
    <h1><?= Html::encode($categoryName) ?> <span class="badge bg-secondary badge-sm"><?= count($units) ?></span></h1>
</div>

<?php if ($description): ?>
    <p class="text-muted mb-3"><?= Html::encode($description) ?></p>
<?php endif; ?>

<?php if ($units): ?>
    <div class="admin5-card admin5-card-border">
        <div class="table-responsive">
            <table class="table table-striped table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width: 40px"></th>
                        <th>Unit</th>
                        <th>Type</th>
                        <th>Age</th>
                        <th>HP</th>
                        <th>Atk</th>
                        <th>Armor</th>
                        <th>Cost</th>
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
                            <td><span class="badge bg-secondary badge-sm"><?= Html::encode($unit->armorClassGroup) ?></span></td>
                            <td>
                                <?php if ($unit->age): ?>
                                    <span class="badge badge-age age-<?= strtolower(explode(' ', $unit->age)[0]) ?>"><?= Html::encode($unit->age) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= $unit->hit_points ?? '-' ?></td>
                            <td><?= $unit->melee_attack ?? $unit->pierce_attack ?? '-' ?></td>
                            <td><?= $unit->armorString ?></td>
                            <td><small><?= Html::encode($unit->costString) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <p class="text-muted">No units found in this category.</p>
<?php endif; ?>
