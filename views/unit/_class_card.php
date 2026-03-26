<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>
<div class="col-md-4 col-lg-3">
    <a href="<?= Url::to(['unit/class', 'slug' => $slug]) ?>" class="class-card-link">
        <div class="admin5-card admin5-card-border" style="margin-bottom: 0;">
            <div class="card-content">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <?php if (isset($iconMap[$cls['icon']])): ?>
                        <img src="<?= Html::encode($iconMap[$cls['icon']]) ?>" alt="" class="unit-image-sm">
                    <?php endif; ?>
                    <div>
                        <strong><?= Html::encode($cls['name']) ?></strong>
                        <span class="badge bg-secondary badge-sm ms-1"><?= $cls['unitCount'] ?></span>
                    </div>
                </div>
                <small class="text-muted"><?= Html::encode($cls['description']) ?></small>
            </div>
        </div>
    </a>
</div>
