<?php

use yii\helpers\Html;
use app\components\Seo;
use app\components\UnitPills;
use app\components\UnitLine;
use app\components\Tr;

/** @var app\models\Unit[] $units */
$this->title = Yii::t('app', 'Units');
Seo::page($this, Yii::t('app', 'All Units in Age of Empires II: Stats and Costs'), Yii::t('app', 'Every unit in Age of Empires II with hit points, attack, armor, speed and cost, filterable by age and by unique or shared units.'), '/images/icons/units.png');
?>

<div class="admin5-page-header">
    <div class="page-title">
        <?= UnitPills::pageIcon('units') ?>
        <h1><?= Yii::t('app', 'Units') ?> <span class="badge bg-secondary badge-sm" data-live-count><?= count($units) ?></span></h1>
    </div>
</div>

<?= $this->render('@app/views/site/_live_filter', [
    'target' => '#unit-list',
    'placeholder' => Yii::t('app', 'Unit name...'),
    'unique' => true,
]) ?>

<!-- Unit Table -->
<div class="admin5-card admin5-card-border">
    <div class="table-responsive">
        <table class="table table-sm mb-0 unit-list-table live-striped" id="unit-list">
            <thead><?= UnitPills::unitTableHead() ?></thead>
            <tbody>
                <?php foreach ($units as $unit): ?>
                    <tr data-filter-item data-q="<?= Html::encode(Tr::short($unit->name) . ' ' . $unit->name) ?>" data-age="<?= Html::encode($unit->age ? UnitLine::age($unit->age) : '') ?>" data-unique="<?= (int)$unit->is_unique ?>"><?= UnitPills::unitRowCells($unit) ?></tr>
                <?php endforeach; ?>
                <tr data-live-empty hidden><td colspan="<?= UnitPills::tableCols() ?>" class="text-center text-muted py-4"><?= Yii::t('app', 'No units found.') ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php UnitPills::registerTips($this); ?>
