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
        <!-- civilization picker: emblems and a search box in the list; every option is a link -->
        <div class="searchable-select kb-civ-picker">
            <button type="button" class="searchable-select-trigger" aria-haspopup="listbox" aria-expanded="false">
                <span class="searchable-select-label has-value">
                    <?php if ($currentCiv): ?>
                        <?php if ($currentCiv->emblemUrl): ?><img src="<?= Html::encode($currentCiv->emblemUrl) ?>" alt="" class="civ-emblem-sm"><?php endif; ?>
                        <?= Html::encode($currentCiv->name) ?>
                    <?php else: ?>
                        All civilizations
                    <?php endif; ?>
                </span>
                <span class="kb-civ-picker-caret"><?= UnitPills::chevron() ?></span>
            </button>
            <div class="searchable-select-dropdown">
                <input type="text" class="searchable-select-search" placeholder="Search civilizations" autocomplete="off" aria-label="Search civilizations">
                <div class="searchable-select-options" role="listbox">
                    <a href="<?= $filterUrl($currentElo, $currentCategory, false) ?>" class="searchable-select-option<?= !$currentCiv ? ' is-selected' : '' ?>" role="option">All civilizations</a>
                    <?php foreach ($civs as $c): ?>
                        <a href="<?= $filterUrl($currentElo, $currentCategory, $c->slug) ?>" class="searchable-select-option<?= $currentCiv && $currentCiv->id === $c->id ? ' is-selected' : '' ?>" role="option">
                            <?php if ($c->emblemUrl): ?><img src="<?= Html::encode($c->emblemUrl) ?>" alt="" class="civ-emblem-sm" loading="lazy"><?php endif; ?>
                            <?= Html::encode($c->name) ?>
                        </a>
                    <?php endforeach; ?>
                    <div class="kb-civ-picker-empty" hidden>No civilization found</div>
                </div>
            </div>
        </div>
        <?php if ($currentCiv): ?>
            <a href="<?= $filterUrl($currentElo, $currentCategory, false) ?>" class="kb-chip">Clear</a>
        <?php endif; ?>
    </div>
</div>

<?php $this->registerJs(<<<'JS'
(function () {
    document.querySelectorAll('.kb-civ-picker').forEach(function (wrap) {
        var trigger = wrap.querySelector('.searchable-select-trigger');
        var dropdown = wrap.querySelector('.searchable-select-dropdown');
        var search = wrap.querySelector('.searchable-select-search');
        var options = [].slice.call(wrap.querySelectorAll('.searchable-select-option'));
        var empty = wrap.querySelector('.kb-civ-picker-empty');
        var active = -1;

        function visible() { return options.filter(function (o) { return !o.hidden; }); }

        function mark(i) {
            var list = visible();
            options.forEach(function (o) { o.classList.remove('is-active'); });
            active = Math.max(0, Math.min(i, list.length - 1));
            if (list[active]) { list[active].classList.add('is-active'); list[active].scrollIntoView({ block: 'nearest' }); }
        }

        function filter() {
            var q = search.value.trim().toLowerCase();
            options.forEach(function (o) { o.hidden = q !== '' && o.textContent.trim().toLowerCase().indexOf(q) === -1; });
            empty.hidden = visible().length > 0;
            mark(0);
        }

        function open() {
            dropdown.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            search.value = '';
            filter();
            var sel = wrap.querySelector('.is-selected');
            if (sel) sel.scrollIntoView({ block: 'nearest' });
            search.focus();
        }

        function close() {
            dropdown.classList.remove('open');
            trigger.setAttribute('aria-expanded', 'false');
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.contains('open') ? close() : open();
        });
        dropdown.addEventListener('click', function (e) { e.stopPropagation(); });
        search.addEventListener('input', filter);
        search.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); mark(active + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); mark(active - 1); }
            else if (e.key === 'Enter') { e.preventDefault(); var o = visible()[active]; if (o) location.href = o.href; }
            else if (e.key === 'Escape') { close(); trigger.focus(); }
        });
        document.addEventListener('click', close);
    });
})();
JS
); ?>
