<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\components\UnitPills;
use app\components\Tr;

$this->title = Tr::name($classDef['name']);
Seo::page($this, Yii::t('app', '{name}: Units in This Class', ['name' => Tr::name($classDef['name'])]), ($classDef['description'] ?? '') ?: Yii::t('app', 'All {name} units in Age of Empires II with their stats and costs.', ['name' => Tr::name($classDef['name'])]));

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

<nav aria-label="<?= Html::encode(Yii::t('app', 'breadcrumb')) ?>" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a(Yii::t('app', 'Units'), ['unit/index']) ?></li>
        <li class="breadcrumb-item"><?= Html::a(Yii::t('app', 'Classes'), ['unit/classes']) ?></li>
        <li class="breadcrumb-item active"><?= Tr::nameHtml($classDef['name']) ?></li>
    </ol>
</nav>

<div class="admin5-page-header">
    <h1><?= Tr::nameHtml($classDef['name']) ?> <span class="badge bg-secondary badge-sm"><?= count($units) ?></span></h1>
    <?php if ($classDef['description']): ?>
        <p class="text-muted"><?= Html::encode($classDef['description']) ?></p>
    <?php endif; ?>
</div>

<div class="admin5-card admin5-card-border">
    <div class="table-responsive">
        <table class="table table-striped table-sm mb-0 unit-list-table">
            <thead><?= UnitPills::unitTableHead(Yii::t('app', 'Classes')) ?></thead>
            <tbody>
                <?php foreach ($units as $unit): ?>
                    <tr><?= UnitPills::unitRowCells($unit, 'classes') ?></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php UnitPills::registerTips($this); ?>
