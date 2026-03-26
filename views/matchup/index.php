<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Matchup Analyzer';
?>

<div class="admin5-page-header">
    <h1>Matchup Analyzer</h1>
</div>

<div class="admin5-card admin5-card-border" style="max-width: 640px;">
    <div class="card-content">
        <p class="text-muted mb-3">Select two civilizations to see which of your units counter the enemy's units.</p>

        <div class="row g-3 align-items-end">
            <div class="col-sm-5">
                <label class="form-label fw-semibold">Your Civilization</label>
                <div class="searchable-select" data-target="yourCiv">
                    <input type="hidden" id="yourCiv" value="">
                    <div class="searchable-select-trigger" tabindex="0">
                        <span class="searchable-select-label">Select</span>
                    </div>
                    <div class="searchable-select-dropdown">
                        <input type="text" class="searchable-select-search" placeholder="Search..." autocomplete="off">
                        <div class="searchable-select-options">
                            <?php foreach ($civs as $civ): ?>
                                <div class="searchable-select-option" data-value="<?= Html::encode($civ->slug) ?>">
                                    <?php if ($civ->emblemUrl): ?>
                                        <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="civ-emblem-sm">
                                    <?php endif; ?>
                                    <?= Html::encode($civ->name) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-2 text-center" style="font-weight: 700; font-size: 1.25rem; color: #6c757d; padding-bottom: 6px;">
                vs
            </div>

            <div class="col-sm-5">
                <label class="form-label fw-semibold">Enemy Civilization</label>
                <div class="searchable-select" data-target="enemyCiv">
                    <input type="hidden" id="enemyCiv" value="">
                    <div class="searchable-select-trigger" tabindex="0">
                        <span class="searchable-select-label">Select</span>
                    </div>
                    <div class="searchable-select-dropdown">
                        <input type="text" class="searchable-select-search" placeholder="Search..." autocomplete="off">
                        <div class="searchable-select-options">
                            <?php foreach ($civs as $civ): ?>
                                <div class="searchable-select-option" data-value="<?= Html::encode($civ->slug) ?>">
                                    <?php if ($civ->emblemUrl): ?>
                                        <img src="<?= Html::encode($civ->emblemUrl) ?>" alt="" class="civ-emblem-sm">
                                    <?php endif; ?>
                                    <?= Html::encode($civ->name) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button id="compareBtn" class="btn btn-primary" disabled>Compare</button>
        </div>
    </div>
</div>

<?php
$baseUrl = Url::to(['matchup/view', 'yourCiv' => '__YOUR__', 'enemyCiv' => '__ENEMY__']);
$this->registerJs(<<<JS
(function() {
    var yourInput = document.getElementById('yourCiv');
    var enemyInput = document.getElementById('enemyCiv');
    var btn = document.getElementById('compareBtn');

    function updateBtn() {
        btn.disabled = !(yourInput.value && enemyInput.value);
    }

    // Searchable select logic
    document.querySelectorAll('.searchable-select').forEach(function(wrapper) {
        var trigger = wrapper.querySelector('.searchable-select-trigger');
        var label = wrapper.querySelector('.searchable-select-label');
        var dropdown = wrapper.querySelector('.searchable-select-dropdown');
        var search = wrapper.querySelector('.searchable-select-search');
        var options = wrapper.querySelectorAll('.searchable-select-option');
        var hidden = wrapper.querySelector('input[type=hidden]');
        var isOpen = false;

        function open() {
            dropdown.classList.add('open');
            search.value = '';
            filterOptions('');
            isOpen = true;
            setTimeout(function() { search.focus(); }, 10);
        }

        function close() {
            dropdown.classList.remove('open');
            isOpen = false;
        }

        function filterOptions(q) {
            var lower = q.toLowerCase();
            options.forEach(function(opt) {
                var text = opt.textContent.trim().toLowerCase();
                opt.style.display = text.includes(lower) ? '' : 'none';
            });
        }

        function selectOption(opt) {
            hidden.value = opt.getAttribute('data-value');
            label.innerHTML = opt.innerHTML;
            label.classList.add('has-value');
            close();
            updateBtn();
        }

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            // Close other dropdowns
            document.querySelectorAll('.searchable-select-dropdown.open').forEach(function(d) {
                if (d !== dropdown) d.classList.remove('open');
            });
            isOpen ? close() : open();
        });

        search.addEventListener('input', function() {
            filterOptions(this.value);
        });

        search.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') close();
            if (e.key === 'Enter') {
                var visible = [];
                options.forEach(function(opt) {
                    if (opt.style.display !== 'none') visible.push(opt);
                });
                if (visible.length === 1) selectOption(visible[0]);
            }
        });

        options.forEach(function(opt) {
            opt.addEventListener('click', function(e) {
                e.stopPropagation();
                selectOption(opt);
            });
        });

        dropdown.addEventListener('click', function(e) { e.stopPropagation(); });
    });

    // Close all on outside click
    document.addEventListener('click', function() {
        document.querySelectorAll('.searchable-select-dropdown.open').forEach(function(d) {
            d.classList.remove('open');
        });
    });

    btn.addEventListener('click', function() {
        if (!yourInput.value || !enemyInput.value) return;
        var url = '$baseUrl'.replace('__YOUR__', yourInput.value).replace('__ENEMY__', enemyInput.value);
        window.location.href = url;
    });
})();
JS
);
?>
