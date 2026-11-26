<?php

use yii\helpers\Html;
use app\components\Seo;
use yii\helpers\Url;
use app\models\Elo;
use app\models\Unit;
use app\components\TextFormat;
use app\components\UnitPills;
use app\components\EloHistogram;
use app\components\Tr;

$title = Tr::text($article->title, 'guides');
$summary = Tr::text($article->summary, 'guides');
$level = Yii::t('app', Elo::label($article->elo));
$this->title = $title;
Seo::page($this, $title, $summary ?: $title, $article->avatarUrl);

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

<nav aria-label="<?= Html::encode(Yii::t('app', 'breadcrumb')) ?>" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><?= Html::a(Yii::t('app', 'Guides'), ['guide/index']) ?></li>
        <li class="breadcrumb-item"><?= Html::a(Html::encode($level), ['guide/index', 'elo' => $article->elo]) ?></li>
        <li class="breadcrumb-item active"><?= Html::encode($title) ?></li>
    </ol>
</nav>

<!-- title with the guide's avatar, then the level with the ladder histogram -->
<header class="kb-head kb-article-head">
    <div class="unit-title">
        <img src="<?= Html::encode($article->avatarUrl) ?>" alt="" class="unit-title-icon kb-article-avatar">
        <h1 class="kb-article-title"><?= Html::encode($title) ?></h1>
    </div>
    <div class="kb-article-level">
        <span class="pill-row">
            <?= UnitPills::eloPill($article->elo, ['href' => Url::to(['guide/index', 'elo' => $article->elo])]) ?>
            <?= UnitPills::tagPill($article->category, Yii::t('app', $article->categoryLabel),Url::to(['guide/index', 'category' => $article->category])) ?>
        </span>
        <?= EloHistogram::render($article->elo, ['width' => 200, 'height' => 34]) ?>
        <span class="kb-article-meta">
            <?php if ($article->reading_minutes): ?>
                <span class="kb-minutes"><?= Yii::t('app', '{n} min read', ['n' => (int)$article->reading_minutes]) ?></span>
            <?php endif; ?>
        </span>
        <?php foreach ($article->civModels as $c): ?><?= UnitPills::civPill($c) ?><?php endforeach; ?>
    </div>
</header>

<div class="row g-4">
    <article class="col-lg-8">
        <?php if ($article->summary): ?>
            <p class="kb-article-summary"><?= Html::encode($summary) ?></p>
        <?php endif; ?>
        <div class="kb-prose kb-article-body">
            <?= TextFormat::render(Tr::text($article->body, 'guides')) ?>
        </div>
        <?= $this->render('_media', ['videos' => $article->listOf('videos'), 'sources' => $article->listOf('sources')]) ?>
    </article>

    <aside class="col-lg-4">
        <?php if ($units): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong><?= Yii::t('app', 'Units in this guide') ?></strong></div>
                <div class="card-content pill-row">
                    <?php foreach ($units as $u): ?>
                        <?= UnitPills::pill($u->name, ['href' => Url::to(['unit/view', 'slug' => $u->slug]), 'icon' => $u->iconUrl, 'unitId' => $u->id, 'labelHtml' => Tr::nameHtml($u->name)]) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($more): ?>
            <div class="admin5-card admin5-card-border">
                <div class="card-header"><strong><?= Html::encode(Yii::t('app', 'More for {level} players', ['level' => $level])) ?></strong></div>
                <ul class="kb-more">
                    <?php foreach ($more as $m): ?>
                        <li>
                            <a href="<?= Url::to(['guide/view', 'slug' => $m->slug]) ?>"><?= Html::encode(Tr::text($m->title, 'guides')) ?></a>
                            <span><?= Html::encode(Yii::t('app', $m->categoryLabel)) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="admin5-card admin5-card-border">
            <div class="card-content">
                <a href="<?= Url::to(['build/index', 'elo' => $article->elo]) ?>" class="kb-aside-link"><?= Yii::t('app', 'More build orders for this level') ?><?=UnitPills::chevron() ?></a>
            </div>
        </div>
    </aside>
</div>

<?php UnitPills::registerTips($this); ?>
