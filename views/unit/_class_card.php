<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\Tr;

?>
<div class="col-md-4 col-lg-3">
    <a href="<?= Url::to(['unit/class', 'slug' => $slug]) ?>" class="admin5-card admin5-card-border home-tile class-tile">
        <?php if (isset($iconMap[$cls['icon']])): ?>
            <img src="<?= Html::encode($iconMap[$cls['icon']]) ?>" alt="" class="home-tile-icon class-tile-icon">
        <?php endif; ?>
        <span>
            <span class="home-tile-name"><?= Tr::nameHtml($cls['name']) ?> <span class="badge bg-secondary badge-sm ms-1"><?= $cls['unitCount'] ?></span></span>
            <span class="home-tile-meta"><?= Html::encode($cls['description']) ?></span>
        </span>
    </a>
</div>
