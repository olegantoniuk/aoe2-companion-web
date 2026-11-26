<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\components\UnitPills;
use app\components\Tr;

$this->title = Yii::t('app', 'Technologies');
Seo::page($this, Yii::t('app', 'All Technologies in Age of Empires II'), Yii::t('app', 'Every technology in Age of Empires II grouped by the building that researches it, with age, cost, research time and effect.'), '/images/icons/technologies.png');
// a group: the building that researches the technologies, or "Other"
$groupLabel = function ($name) {
    return $name === 'Other' ? Html::encode(Yii::t('app', 'Other')) : Tr::nameHtml($name);
};
?>

<div class="admin5-page-header">
    <div class="page-title">
        <?= UnitPills::pageIcon('technologies') ?>
        <h1><?= Yii::t('app', 'Technologies') ?> <span class="badge bg-secondary badge-sm"><?= $total ?></span></h1>
    </div>
</div>

<nav class="kb-filters tech-jump">
    <div class="kb-filter-row">
        <span class="kb-filter-label"><?= Yii::t('app', 'Researched at') ?></span>
        <?php foreach ($groups as $name => $techs): ?>
            <a href="#at-<?= Html::encode(preg_replace('/[^a-z0-9]+/', '-', strtolower($name))) ?>" class="kb-chip">
                <?php if (isset($buildings[$name]) && $buildings[$name]->iconUrl): ?><img src="<?= Html::encode($buildings[$name]->iconUrl) ?>" alt=""><?php endif; ?>
                <?= $groupLabel($name) ?> <small><?= count($techs) ?></small>
            </a>
        <?php endforeach; ?>
    </div>
</nav>

<?php foreach ($groups as $name => $techs): ?>
    <section class="tech-group" id="at-<?= Html::encode(preg_replace('/[^a-z0-9]+/', '-', strtolower($name))) ?>">
        <h2 class="unit-section-title tech-group-title">
            <?php if (isset($buildings[$name])): ?>
                <?php if ($buildings[$name]->iconUrl): ?><img src="<?= Html::encode($buildings[$name]->iconUrl) ?>" alt=""><?php endif; ?>
                <a href="<?= Url::to(['building/view', 'slug' => $buildings[$name]->slug]) ?>"><?= $groupLabel($name) ?></a>
            <?php else: ?>
                <?= $groupLabel($name) ?>
            <?php endif; ?>
            <span class="badge bg-secondary badge-sm"><?= count($techs) ?></span>
        </h2>
        <?= $this->render('_cards', ['techs' => $techs]) ?>
    </section>
<?php endforeach; ?>
