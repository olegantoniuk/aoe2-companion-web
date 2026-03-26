<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Units';
$models = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
?>

<div class="admin5-page-header">
    <h1>Units <span class="badge bg-secondary badge-sm"><?= $dataProvider->getTotalCount() ?></span></h1>
</div>

<!-- Filters -->
<div class="admin5-card admin5-card-border filter-card" style="margin-bottom: 20px;">
    <div class="card-content" style="padding: 16px;">
        <form method="get" action="<?= Url::to(['unit/index']) ?>" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" value="<?= Html::encode($currentSearch) ?>" placeholder="Unit name...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Age</label>
                <select name="age" class="form-select form-select-sm">
                    <option value="">All ages</option>
                    <?php foreach ($ages as $a): ?>
                        <option value="<?= Html::encode($a) ?>" <?= $currentAge === $a ? 'selected' : '' ?>><?= Html::encode($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="unique" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="0" <?= $currentUnique === '0' ? 'selected' : '' ?>>Shared</option>
                    <option value="1" <?= $currentUnique === '1' ? 'selected' : '' ?>>Unique</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?= Html::a('Reset', ['unit/index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            </div>
        </form>
    </div>
</div>

<!-- Unit Table -->
<div class="admin5-card admin5-card-border">
    <div class="table-responsive">
        <table class="table table-striped table-sm mb-0">
            <thead>
                <tr>
                    <th style="width: 40px"></th>
                    <th>Name</th>
                    <th>Class</th>
                    <th>Age</th>
                    <th><img src="/images/stats/hit_points.png" alt="HP" class="stat-icon" title="Hit Points"></th>
                    <th><img src="/images/stats/melee_attack.png" alt="Atk" class="stat-icon" title="Attack"></th>
                    <th><img src="/images/stats/melee_armor.png" alt="Armor" class="stat-icon" title="Armor"></th>
                    <th><img src="/images/stats/movement_speed.png" alt="Speed" class="stat-icon" title="Speed"></th>
                    <th>Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($models as $unit): ?>
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
                        <td><?= $unit->speed ?? '-' ?></td>
                        <td><small><?= Html::encode($unit->costString) ?></small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= LinkPager::widget([
    'pagination' => $pagination,
    'options' => ['class' => 'pagination justify-content-center'],
    'linkContainerOptions' => ['class' => 'page-item'],
    'linkOptions' => ['class' => 'page-link'],
    'disabledListItemSubTagOptions' => ['class' => 'page-link'],
]) ?>
