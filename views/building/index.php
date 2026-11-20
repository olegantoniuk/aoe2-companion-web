<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\Building;
use app\components\UnitPills;

$this->title = 'Buildings';
Seo::page($this, 'All Buildings in Age of Empires II', 'Every building in Age of Empires II with its cost, hit points, armor, the units it trains and the technologies researched there.', '/images/icons/buildings.png');
?>

<div class="admin5-page-header">
    <div class="page-title">
        <?= UnitPills::pageIcon('buildings') ?>
        <h1>Buildings <span class="badge bg-secondary badge-sm"><?= $total ?></span></h1>
    </div>
</div>

<?php foreach ($byCategory as $cat => $buildings): ?>
    <section class="civ-group">
        <h2 class="civ-group-title"><?= Html::encode(Building::CATEGORIES[$cat] ?? ucfirst($cat)) ?></h2>
        <div class="building-grid">
            <?php foreach ($buildings as $b): ?>
                <a href="<?= Url::to(['building/view', 'slug' => $b->slug]) ?>" class="admin5-card admin5-card-border building-card">
                    <span class="building-card-icon">
                        <?php if ($b->iconUrl): ?><img src="<?= Html::encode($b->iconUrl) ?>" alt=""><?php endif; ?>
                    </span>
                    <span class="building-card-text">
                        <span class="building-card-name"><?= Html::encode($b->name) ?></span>
                        <span class="building-card-meta">
                            <?php if ($b->age): ?><?= UnitPills::agePill($b->age) ?><?php endif; ?>
                            <span class="unit-list-cost"><?= $b->costHtml ?></span>
                        </span>
                        <?php [$mode, $names] = $b->availabilityNote; ?>
                        <?php if ($mode === 'only'): ?>
                            <span class="building-card-civs"><?= Html::encode(count($names) > 4 ? count($names) . ' civilizations' : implode(', ', $names) . ' only') ?></span>
                        <?php elseif ($mode === 'except'): ?>
                            <span class="building-card-civs">All except <?= Html::encode(count($names) > 4 ? count($names) . ' civilizations' : implode(', ', $names)) ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
