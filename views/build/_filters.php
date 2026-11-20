<?php

use yii\helpers\Html;
use app\models\Elo;
use app\components\UnitPills;

/**
 * Level, category and civilization filters shared by the build and guide lists.
 * @var callable $filterUrl ($elo, $category, $civ = null keeps the current one, false clears it)
 */
?>
<div class="kb-filters">
    <div class="kb-filter-group">
        <span class="kb-filter-label">Level</span>
        <div class="kb-filter-chips">
        <a href="<?= $filterUrl(null, $currentCategory) ?>" class="kb-chip<?= !$currentElo ? ' is-active' : '' ?>">All</a>
        <?php foreach (Elo::BANDS as $key => [$label, $range]): ?>
            <a href="<?= $filterUrl($key, $currentCategory) ?>" class="kb-chip kb-chip-elo<?= $currentElo === $key ? ' is-active' : '' ?>">
                <img src="/images/icons/<?= $key ?>.png" alt=""><?= $label ?> <small><?= Html::encode($range) ?></small>
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <div class="kb-filter-group">
        <span class="kb-filter-label"><?= Html::encode($categoryLabel) ?></span>
        <div class="kb-filter-chips">
        <a href="<?= $filterUrl($currentElo, null) ?>" class="kb-chip<?= !$currentCategory ? ' is-active' : '' ?>">All</a>
        <?php foreach ($categories as $key => $label): ?>
            <a href="<?= $filterUrl($currentElo, $key) ?>" class="kb-chip<?= $currentCategory === $key ? ' is-active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
        </div>
    </div>
    <div class="kb-filter-row">
        <span class="kb-filter-label">Civilization</span>
        <div class="kb-civ-select">
            <?php if ($currentCiv && $currentCiv->emblemUrl): ?>
                <img src="<?= Html::encode($currentCiv->emblemUrl) ?>" alt="">
            <?php endif; ?>
            <select class="form-select form-select-sm" onchange="location.href = this.value">
                <option value="<?= $filterUrl($currentElo, $currentCategory, false) ?>">All civilizations</option>
                <?php foreach ($civs as $c): ?>
                    <option value="<?= $filterUrl($currentElo, $currentCategory, $c->slug) ?>"<?= $currentCiv && $currentCiv->id === $c->id ? ' selected' : '' ?>><?= Html::encode($c->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($currentCiv): ?>
            <a href="<?= $filterUrl($currentElo, $currentCategory, false) ?>" class="kb-chip">Clear</a>
        <?php endif; ?>
    </div>
</div>
