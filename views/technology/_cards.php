<?php

use yii\helpers\Html;
use app\components\UnitPills;

/** @var app\models\Technology[] $techs */
$icon = function ($name) {
    $file = str_replace([' ', "'", '*', '(', ')', '-'], '', $name) . '.png';
    return is_file(Yii::getAlias('@webroot') . '/images/techs/' . $file) ? '/images/techs/' . $file : '/images/techs/UniqueTechCastle.png';
};
$cost = function ($t) {
    $parts = [];
    foreach (['Food' => $t->cost_food, 'Wood' => $t->cost_wood, 'Gold' => $t->cost_gold, 'Stone' => $t->cost_stone] as $res => $n) {
        if ($n) $parts[] = '<img src="/images/resources/' . $res . '.png" alt="' . $res . '" class="res-icon"> ' . (int)$n;
    }
    return $parts ? implode(' &nbsp; ', $parts) : 'Free';
};
?>
<div class="tech-grid">
    <?php foreach ($techs as $t): ?>
        <div class="admin5-card admin5-card-border tech-card-box" id="tech-<?= Html::encode(preg_replace('/[^a-z0-9]+/', '-', strtolower($t->name))) ?>">
            <div class="tech-card tech-card-stacked">
                <div class="tech-card-head">
                    <img src="<?= Html::encode($icon($t->name)) ?>" alt="" class="tech-card-icon">
                    <div class="tech-card-head-text">
                        <div class="tech-card-name">
                            <?= Html::encode($t->name) ?>
                            <?php if ($t->civilization): ?><?= UnitPills::civPill($t->civilization) ?><?php endif; ?>
                        </div>
                        <div class="tech-card-cost">
                            <?= $cost($t) ?>
                            <?php if ($t->research_time): ?>
                                &nbsp;<img src="/images/stats/time.png" alt="Time" title="Research time" class="res-icon"> <?= (int)$t->research_time ?>s
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="tech-card-body">
                    <?php if ($t->age): ?>
                        <div class="tech-card-age"><?= UnitPills::agePill($t->age) ?></div>
                    <?php endif; ?>
                    <?php if ($t->description): ?>
                        <div class="tech-card-desc"><?= nl2br(Html::encode($t->description)) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
