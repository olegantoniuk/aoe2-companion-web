<?php

use yii\helpers\Html;
use app\components\Tr;

/**
 * Search and age filter that work as the reader types (web/js/live-filter.js), shared by the list pages.
 * @var string $target selector of the element that holds the [data-filter-item] elements
 * @var string $placeholder
 * @var bool $unique also show the Shared / Unique select (units)
 */
$request = Yii::$app->request;
$q = (string)$request->get('q', '');
$age = (string)$request->get('age', '');
$uniq = (string)$request->get('unique', '');
$unique = $unique ?? false;
$this->registerJsFile('/js/live-filter.js?v=' . filemtime(Yii::getAlias('@webroot') . '/js/live-filter.js'), ['defer' => true]);
?>
<div class="admin5-card admin5-card-border filter-card" style="margin-bottom: 20px;">
    <div class="card-content" style="padding: 16px;">
        <form method="get" class="row g-2 align-items-end" data-live-filter="<?= Html::encode($target) ?>" role="search">
            <div class="col-md-3">
                <label class="form-label" for="live-q"><?= Yii::t('app', 'Search') ?></label>
                <div class="live-filter-search">
                    <input type="text" id="live-q" name="q" class="form-control form-control-sm" value="<?= Html::encode($q) ?>" placeholder="<?= Html::encode($placeholder) ?>" autocomplete="off">
                    <span class="live-filter-spinner spinner-border spinner-border-sm" role="status" aria-label="<?= Html::encode(Yii::t('app', 'Loading')) ?>" hidden></span>
                    <button type="button" class="live-filter-clear" aria-label="<?= Html::encode(Yii::t('app', 'Clear search')) ?>" title="<?= Html::encode(Yii::t('app', 'Clear search')) ?>" hidden>
                        <svg viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M3.5 3.5l9 9m0-9l-9 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
                    </button>
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="live-age"><?= Yii::t('app', 'Age') ?></label>
                <select id="live-age" name="age" class="form-select form-select-sm">
                    <option value=""><?= Yii::t('app', 'All ages') ?></option>
                    <?php foreach (Tr::AGES as $a): ?>
                        <option value="<?= Html::encode($a) ?>" <?= $age === $a ? 'selected' : '' ?>><?= Html::encode(Tr::name($a)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($unique): ?>
                <div class="col-md-2">
                    <label class="form-label" for="live-unique"><?= Yii::t('app', 'Category') ?></label>
                    <select id="live-unique" name="unique" class="form-select form-select-sm">
                        <option value=""><?= Yii::t('app', 'All') ?></option>
                        <option value="0" <?= $uniq === '0' ? 'selected' : '' ?>><?= Yii::t('app', 'Shared') ?></option>
                        <option value="1" <?= $uniq === '1' ? 'selected' : '' ?>><?= Yii::t('app', 'Unique') ?></option>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-live-reset><?= Yii::t('app', 'Reset') ?></button>
            </div>
        </form>
    </div>
</div>
