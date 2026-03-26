<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Unit Classes';

$mainClasses = array_filter($classes, function ($c) { return $c['isMain']; });
$subClasses = array_filter($classes, function ($c) { return !$c['isMain']; });

// Alphabetical sort
$alpha = $classes;
uasort($alpha, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });

// By unit count (descending)
$byCount = $classes;
uasort($byCount, function ($a, $b) { return $b['unitCount'] - $a['unitCount']; });
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a('Units', ['unit/index']) ?></li>
        <li class="breadcrumb-item active">Classes</li>
    </ol>
</nav>

<div class="admin5-page-header">
    <h1>Unit Classes <span class="badge bg-secondary badge-sm"><?= count($classes) ?></span></h1>
    <p class="text-muted">Units classified by their armor classes — the same system the game uses for attack bonuses.</p>
</div>

<div class="unit-tabs">
    <ul class="nav nav-tabs mb-0" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-category" type="button" role="tab">By Category</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-alpha" type="button" role="tab">Alphabetical</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-count" type="button" role="tab">By Unit Count</button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- By Category -->
        <div class="tab-pane fade show active" id="tab-category" role="tabpanel">
            <div class="admin5-card admin5-card-border" style="border-top: none; border-radius: 0 0 2px 2px;">
                <div class="card-content">
                    <h5 class="mb-3">Main Classes</h5>
                    <div class="row g-3 mb-4">
                        <?php foreach ($mainClasses as $slug => $cls): ?>
                            <?= $this->render('_class_card', ['slug' => $slug, 'cls' => $cls, 'iconMap' => $iconMap]) ?>
                        <?php endforeach; ?>
                    </div>
                    <h5 class="mb-3">Sub Classes</h5>
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
            <div class="admin5-card admin5-card-border" style="border-top: none; border-radius: 0 0 2px 2px;">
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
            <div class="admin5-card admin5-card-border" style="border-top: none; border-radius: 0 0 2px 2px;">
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
