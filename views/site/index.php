<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Home';
?>

<div class="admin5-page-header">
    <h1>AoE2 Companion</h1>
</div>

<p class="text-muted mb-4">Browse Age of Empires II units and civilizations</p>

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="admin5-card admin5-card-border text-center">
            <div class="card-content" style="padding: 24px 16px;">
                <div style="font-size: 2.25rem; font-weight: 700; color: var(--accent-color);"><?= $civCount ?></div>
                <div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Civilizations</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin5-card admin5-card-border text-center">
            <div class="card-content" style="padding: 24px 16px;">
                <div style="font-size: 2.25rem; font-weight: 700; color: var(--accent-color);"><?= $unitCount ?></div>
                <div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Units</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin5-card admin5-card-border text-center">
            <div class="card-content" style="padding: 24px 16px;">
                <div style="font-size: 2.25rem; font-weight: 700; color: var(--accent-color);"><?= $sharedCount ?></div>
                <div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Shared Units</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin5-card admin5-card-border text-center">
            <div class="card-content" style="padding: 24px 16px;">
                <div style="font-size: 2.25rem; font-weight: 700; color: var(--accent-color);"><?= $uniqueCount ?></div>
                <div class="text-muted" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Unique Units</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <h3>Civilizations</h3>
        <div class="row g-2">
            <?php foreach ($recentCivs as $civ): ?>
                <div class="col-6">
                    <a href="<?= Url::to(['civilization/view', 'slug' => $civ->slug]) ?>" class="civ-card-link">
                        <div class="admin5-card admin5-card-border" style="margin-bottom: 0;">
                            <div class="card-content">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($civ->emblemUrl): ?>
                                        <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="civ-emblem-sm">
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 600;"><?= Html::encode($civ->name) ?></div>
                                        <small class="text-muted"><?= Html::encode($civ->focus) ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
            <?= Html::a('View all civilizations', ['civilization/index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <h3>Quick Links</h3>
        <div class="d-flex flex-column gap-2">
            <?= Html::a('All Units', ['unit/index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('Cavalry Units', ['unit/index', 'type' => 'Cavalry'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('Infantry Units', ['unit/index', 'type' => 'Infantry'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('Archer Units', ['unit/index', 'type' => 'Archer'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('Siege Units', ['unit/index', 'type' => 'Siege'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('Unique Units Only', ['unit/index', 'unique' => '1'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
        </div>
    </div>
</div>
