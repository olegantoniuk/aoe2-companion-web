<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\components\UnitPills;

$this->title = $classDef['name'];
Seo::page($this, $classDef['name'] . ': Units in This Class', ($classDef['description'] ?? '') ?: ('All ' . $classDef['name'] . ' units in Age of Empires II with their stats and costs.'));

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

<?php UnitPills::registerTips($this); ?>
