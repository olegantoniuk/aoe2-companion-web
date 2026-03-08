<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Civilizations';
?>

<div class="admin5-page-header">
    <h1>Civilizations <span class="badge bg-secondary badge-sm"><?= count($civs) ?></span></h1>
</div>

<div class="row g-3">
    <?php foreach ($civs as $civ): ?>
        <div class="col-md-4 col-lg-3">
            <a href="<?= Url::to(['civilization/view', 'slug' => $civ->slug]) ?>" class="civ-card-link">
                <div class="admin5-card admin5-card-border" style="margin-bottom: 0;">
                    <div class="card-content">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <?php if ($civ->emblemUrl): ?>
                                <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="civ-emblem-md">
                            <?php endif; ?>
                            <div>
                                <div style="font-weight: 600; font-size: 1.05rem;"><?= Html::encode($civ->name) ?></div>
                                <?php if ($civ->focus): ?>
                                    <small class="text-muted"><?= Html::encode($civ->focus) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($civ->architecture_set): ?>
                            <div><small class="text-muted"><?= Html::encode($civ->architecture_set) ?></small></div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
