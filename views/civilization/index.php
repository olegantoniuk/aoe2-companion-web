<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Civilization;
use app\components\UnitPills;

$this->title = 'Civilizations';

$bySlug = [];
foreach ($civs as $civ) {
    $bySlug[$civ->slug] = $civ;
}
// civilizations that no region lists still show up, in a last group
$placed = [];
$groups = [];
$regionOf = [];
foreach (Civilization::regions() as [$title, $note, $slugs, $icon]) {
    $members = [];
    foreach ($slugs as $s) {
        if (isset($bySlug[$s])) {
            $members[] = $bySlug[$s];
            $placed[$s] = true;
            $regionOf[$s] = [$title, Civilization::regionIcon($icon)];
        }
    }
    if ($members) $groups[] = [$title, $note, $members, Civilization::regionIcon($icon)];
}
$rest = array_values(array_filter($civs, function ($c) use ($placed) { return !isset($placed[$c->slug]); }));
if ($rest) $groups[] = ['Other', '', $rest, null];

$uniquesOf = function ($civ) use ($uniqueByCiv) {
    return array_filter($uniqueByCiv[$civ->id] ?? [], function ($u) { return $u->iconUrl; });
};
?>

<div class="admin5-page-header">
    <h1>Civilizations <span class="badge bg-secondary badge-sm"><?= count($civs) ?></span></h1>
</div>

<ul class="nav nav-tabs civ-list-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#civ-tab-az" type="button" role="tab">A–Z</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#civ-tab-regions" type="button" role="tab">By Region</button>
    </li>
</ul>

<div class="tab-content">
    <!-- every civilization, one card per row, filtered as you type -->
    <div class="tab-pane fade show active" id="civ-tab-az" role="tabpanel">
        <div class="civ-search">
            <input type="search" id="civSearch" class="form-control" placeholder="Find a civilization, a unique unit or a focus" autocomplete="off">
        </div>
        <div class="civ-rows">
            <?php foreach ($civs as $civ): ?>
                <?php
                $uniques = $uniquesOf($civ);
                $region = $regionOf[$civ->slug] ?? null;
                $haystack = strtolower(implode(' ', array_merge(
                    [$civ->name, $region[0] ?? ''],
                    $civ->focusList,
                    array_map(function ($u) { return $u->name; }, $uniques)
                )));
                $url = Url::to(['civilization/view', 'slug' => $civ->slug]);
                ?>
                <div class="admin5-card admin5-card-border civ-row" data-search="<?= Html::encode($haystack) ?>">
                    <a href="<?= $url ?>" class="civ-row-emblem" aria-label="<?= Html::encode($civ->name) ?>">
                        <?php if ($civ->emblemUrl): ?>
                            <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="civ-emblem-md">
                        <?php endif; ?>
                    </a>
                    <div class="civ-row-main">
                        <div class="civ-row-title">
                            <a href="<?= $url ?>" class="civ-card-name"><?= Html::encode($civ->name) ?></a>
                            <?php if ($region): ?>
                                <span class="civ-row-region">
                                    <?php if ($region[1]): ?><img src="<?= Html::encode($region[1]) ?>" alt=""><?php endif; ?>
                                    <?= Html::encode($region[0]) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($civ->focusList): ?>
                            <div class="civ-card-focus"><?= Html::encode(implode(', ', $civ->focusList)) ?></div>
                        <?php endif; ?>
                        <?php if ($civ->hasAttribute('summary') && $civ->summary): ?>
                            <p class="civ-row-summary"><?= Html::encode($civ->summary) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($uniques): ?>
                        <div class="civ-row-units">
                            <?php foreach ($uniques as $u): ?>
                                <?= UnitPills::unitAvatarLink($u) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <p class="civ-search-empty text-muted" hidden>No civilization matches.</p>
        </div>
    </div>

    <!-- grouped by region -->
    <div class="tab-pane fade" id="civ-tab-regions" role="tabpanel">
        <?php foreach ($groups as [$title, $note, $members, $icon]): ?>
            <section class="civ-group">
                <div class="civ-group-head">
                    <?php if ($icon): ?>
                        <img src="<?= Html::encode($icon) ?>" alt="" class="civ-group-icon">
                    <?php endif; ?>
                    <div>
                        <h2 class="civ-group-title"><?= Html::encode($title) ?></h2>
                        <?php if ($note): ?>
                            <p class="civ-group-note"><?= Html::encode($note) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="row g-3">
                    <?php foreach ($members as $civ): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="admin5-card admin5-card-border civ-card">
                                <div class="card-content">
                                    <a href="<?= Url::to(['civilization/view', 'slug' => $civ->slug]) ?>" class="civ-card-head">
                                        <?php if ($civ->emblemUrl): ?>
                                            <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="civ-emblem-md">
                                        <?php endif; ?>
                                        <div>
                                            <div class="civ-card-name"><?= Html::encode($civ->name) ?></div>
                                            <?php if ($civ->focusList): ?>
                                                <div class="civ-card-focus"><?= Html::encode(implode(', ', $civ->focusList)) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <?php if ($civ->hasAttribute('summary') && $civ->summary): ?>
                                        <p class="civ-card-summary"><?= Html::encode($civ->summary) ?></p>
                                    <?php endif; ?>
                                    <?php $uniques = $uniquesOf($civ); ?>
                                    <?php if ($uniques): ?>
                                        <div class="civ-card-units">
                                            <?php foreach ($uniques as $u): ?>
                                                <?= UnitPills::unitAvatarLink($u) ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('civSearch');
    if (!input) return;
    var rows = document.querySelectorAll('.civ-row');
    var empty = document.querySelector('.civ-search-empty');
    input.addEventListener('input', function () {
        var words = this.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
        var shown = 0;
        rows.forEach(function (row) {
            var text = row.getAttribute('data-search');
            var hit = words.every(function (w) { return text.indexOf(w) !== -1; });
            row.hidden = !hit;
            if (hit) shown++;
        });
        empty.hidden = shown > 0;
    });
});
</script>

<?php UnitPills::registerTips($this); ?>
