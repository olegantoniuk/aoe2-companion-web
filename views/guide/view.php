<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Elo;
use app\models\Unit;
use app\components\TextFormat;
use app\components\UnitPills;

$this->title = $article->title;

$units = [];
$names = $article->listOf('related_units');
if ($names) {
    $byName = [];
    foreach (Unit::find()->where(['name' => $names])->all() as $u) {
        $byName[$u->name] = $u;
    }
    foreach ($names as $n) {
        if (isset($byName[$n])) $units[] = $byName[$n];
    }
}
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a('Guides', ['guide/index']) ?></li>
        <li class="breadcrumb-item"><?= Html::a(Elo::label($article->elo), ['guide/index', 'elo' => $article->elo]) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($article->title) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <article class="col-lg-8">
        <header class="kb-head">
            <div class="kb-card-meta">
                <a href="<?= Url::to(['guide/index', 'elo' => $article->elo]) ?>" class="kb-elo kb-elo-<?= Html::encode($article->elo) ?>"><?= Elo::label($article->elo) ?></a>
        <span class="kb-elo-range"><?= Html::encode(Elo::range($article->elo)) ?></span>
                <a href="<?= Url::to(['guide/index', 'category' => $article->category]) ?>" class="kb-category"><?= Html::encode($article->categoryLabel) ?></a>
                <?php if ($article->reading_minutes): ?>
                    <span class="kb-minutes"><?= (int)$article->reading_minutes ?> min read</span>
                <?php endif; ?>
            </div>
            <h1 class="kb-article-title"><?= Html::encode($article->title) ?></h1>
            <?php if ($article->summary): ?>
                <p class="kb-article-summary"><?= Html::encode($article->summary) ?></p>
            <?php endif; ?>
        </header>
        <div class="kb-prose kb-article-body">
            <?= TextFormat::render($article->body) ?>
        </div>
        <?= $this->render('_media', ['videos' => $article->listOf('videos'), 'sources' => $article->listOf('sources')]) ?>
    </article>

    <aside class="col-lg-4">
        <?php if ($units): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>Units in this guide</strong></div>
                <div class="card-content pill-row">
                    <?php foreach ($units as $u): ?>
                        <?= UnitPills::pill($u->name, ['href' => Url::to(['unit/view', 'slug' => $u->slug]), 'icon' => $u->iconUrl, 'unitId' => $u->id]) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($more): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong>More for <?= Html::encode(Elo::label($article->elo)) ?> players</strong></div>
                <ul class="kb-more">
                    <?php foreach ($more as $m): ?>
                        <li>
                            <a href="<?= Url::to(['guide/view', 'slug' => $m->slug]) ?>"><?= Html::encode($m->title) ?></a>
                            <span><?= Html::encode($m->categoryLabel) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="admin5-card admin5-card-border">
            <div class="card-content">
                <a href="<?= Url::to(['build/index', 'elo' => $article->elo]) ?>" class="kb-aside-link">Build orders for this level →</a>
            </div>
        </div>
    </aside>
</div>

<?php UnitPills::registerTips($this); ?>
