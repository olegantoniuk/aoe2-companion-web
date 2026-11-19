<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\components\UnitPills;

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
        <table class="table table-striped table-sm mb-0 unit-list-table">
            <thead><?= UnitPills::unitTableHead() ?></thead>
            <tbody>
                <?php foreach ($models as $unit): ?>
                    <tr><?= UnitPills::unitRowCells($unit) ?></tr>
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

<?php UnitPills::registerTips($this); ?>
