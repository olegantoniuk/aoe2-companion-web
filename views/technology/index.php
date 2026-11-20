<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\components\UnitPills;

$this->title = 'Technologies';
Seo::page($this, 'All Technologies in Age of Empires II', 'Every technology in Age of Empires II grouped by the building that researches it, with age, cost, research time and effect.', '/images/icons/technologies.png');
?>

<div class="admin5-page-header">
    <div class="page-title">
        <?= UnitPills::pageIcon('technologies') ?>
        <h1>Technologies <span class="badge bg-secondary badge-sm"><?= $total ?></span></h1>
    </div>
</div>

<nav class="kb-filters tech-jump">
    <div class="kb-filter-row">
        <span class="kb-filter-label">Researched at</span>
        <?php foreach ($groups as $name => $techs): ?>
            <a href="#at-<?= Html::encode(preg_replace('/[^a-z0-9]+/', '-', strtolower($name))) ?>" class="kb-chip">
                <?php if (isset($buildings[$name]) && $buildings[$name]->iconUrl): ?><img src="<?= Html::encode($buildings[$name]->iconUrl) ?>" alt=""><?php endif; ?>
                <?= Html::encode($name) ?> <small><?= count($techs) ?></small>
            </a>
        <?php endforeach; ?>
    </div>
</nav>

<?php foreach ($groups as $name => $techs): ?>
    <section class="tech-group" id="at-<?= Html::encode(preg_replace('/[^a-z0-9]+/', '-', strtolower($name))) ?>">
        <h2 class="unit-section-title tech-group-title">
            <?php if (isset($buildings[$name])): ?>
                <?php if ($buildings[$name]->iconUrl): ?><img src="<?= Html::encode($buildings[$name]->iconUrl) ?>" alt=""><?php endif; ?>
                <a href="<?= Url::to(['building/view', 'slug' => $buildings[$name]->slug]) ?>"><?= Html::encode($name) ?></a>
            <?php else: ?>
                <?= Html::encode($name) ?>
            <?php endif; ?>
            <span class="badge bg-secondary badge-sm"><?= count($techs) ?></span>
        </h2>
        <?= $this->render('_cards', ['techs' => $techs]) ?>
    </section>
<?php endforeach; ?>
