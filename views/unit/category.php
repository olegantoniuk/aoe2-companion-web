<?php

use yii\helpers\Html;
use app\components\Seo;
use app\components\UnitPills;

$this->title = $categoryName;
Seo::page($this, $categoryName . ' Units in Age of Empires II', 'All ' . $categoryName . ' units in Age of Empires II with their stats, costs and classes.');
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
            <table class="table table-striped table-sm mb-0 unit-list-table">
                <thead><?= UnitPills::unitTableHead('Classes') ?></thead>
                <tbody>
                    <?php foreach ($units as $unit): ?>
                        <tr><?= UnitPills::unitRowCells($unit, 'classes') ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <p class="text-muted">No units found in this category.</p>
<?php endif; ?>

<?php UnitPills::registerTips($this); ?>
