<?php

use yii\helpers\Html;
use app\components\Seo;
use app\components\UnitPills;
use yii\helpers\Url;
use app\components\Tr;

$this->title = Yii::t('app', 'Unit Classes');
Seo::page($this, Yii::t('app', 'Unit Classes and Armor Classes in Age of Empires II'), Yii::t('app', 'The armor classes of Age of Empires II and the units in each, so you can see which bonuses hit which units.'), '/images/icons/classes.png');

$mainClasses = array_filter($classes, function ($c) { return $c['isMain']; });
$subClasses = array_filter($classes, function ($c) { return !$c['isMain']; });

// Alphabetical sort, by the name as shown
$alpha = $classes;
uasort($alpha, function ($a, $b) { return strcasecmp(Tr::short($a['name']), Tr::short($b['name'])); });

// By unit count (descending)
$byCount = $classes;
uasort($byCount, function ($a, $b) { return $b['unitCount'] - $a['unitCount']; });
?>

<nav aria-label="<?= Html::encode(Yii::t('app', 'breadcrumb')) ?>" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a(Yii::t('app', 'Units'), ['unit/index']) ?></li>
        <li class="breadcrumb-item active"><?= Yii::t('app', 'Classes') ?></li>
    </ol>
</nav>

<div class="admin5-page-header">
    <div class="page-title">
        <?= UnitPills::pageIcon('classes') ?>
        <h1><?= Yii::t('app', 'Unit Classes') ?> <span class="badge bg-secondary badge-sm"><?= count($classes) ?></span></h1>
    </div>
    <p class="text-muted"><?= Yii::t('app', 'Units grouped by armor class, the system the game uses for attack bonuses.') ?></p>
</div>

<div class="unit-tabs">
    <ul class="nav nav-tabs mb-0" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-category" type="button" role="tab"><?= Yii::t('app', 'By Category') ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-alpha" type="button" role="tab"><?= Yii::t('app', 'Alphabetical') ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-count" type="button" role="tab"><?= Yii::t('app', 'By Unit Count') ?></button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- By Category -->
        <div class="tab-pane fade show active" id="tab-category" role="tabpanel">
            <div class="admin5-card admin5-card-border admin5-card-attached">
                <div class="card-content">
                    <h5 class="mb-3"><?= Yii::t('app', 'Main Classes') ?></h5>
                    <div class="row g-3 mb-4">
                        <?php foreach ($mainClasses as $slug => $cls): ?>
                            <?= $this->render('_class_card', ['slug' => $slug, 'cls' => $cls, 'iconMap' => $iconMap]) ?>
                        <?php endforeach; ?>
                    </div>
                    <h5 class="mb-3"><?= Yii::t('app', 'Sub Classes') ?></h5>
                    <div class="row g-3">
                        <?php foreach ($subClasses as $slug => $cls): ?>
                            <?= $this->render('_class_card', ['slug' => $slug, 'cls' => $cls, 'iconMap' => $iconMap]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alphabetical -->
        <div class="tab-pane fade" id="tab-alpha" role="tabpanel">
            <div class="admin5-card admin5-card-border admin5-card-attached">
                <div class="card-content">
                    <div class="row g-3">
                        <?php foreach ($alpha as $slug => $cls): ?>
                            <?= $this->render('_class_card', ['slug' => $slug, 'cls' => $cls, 'iconMap' => $iconMap]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- By Unit Count -->
        <div class="tab-pane fade" id="tab-count" role="tabpanel">
            <div class="admin5-card admin5-card-border admin5-card-attached">
                <div class="card-content">
                    <div class="row g-3">
                        <?php foreach ($byCount as $slug => $cls): ?>
                            <?= $this->render('_class_card', ['slug' => $slug, 'cls' => $cls, 'iconMap' => $iconMap]) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
