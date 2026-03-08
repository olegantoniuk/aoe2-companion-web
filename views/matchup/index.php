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
                <label class="form-label fw-semibold" for="yourCiv">Your Civilization</label>
                <select id="yourCiv" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach ($civs as $civ): ?>
                        <option value="<?= Html::encode($civ->slug) ?>"><?= Html::encode($civ->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-sm-2 text-center" style="font-weight: 700; font-size: 1.25rem; color: #6c757d; padding-bottom: 6px;">
                vs
            </div>

            <div class="col-sm-5">
                <label class="form-label fw-semibold" for="enemyCiv">Enemy Civilization</label>
                <select id="enemyCiv" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach ($civs as $civ): ?>
                        <option value="<?= Html::encode($civ->slug) ?>"><?= Html::encode($civ->name) ?></option>
                    <?php endforeach; ?>
                </select>
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
    var yourSel = document.getElementById('yourCiv');
    var enemySel = document.getElementById('enemyCiv');
    var btn = document.getElementById('compareBtn');

    function update() {
        btn.disabled = !(yourSel.value && enemySel.value);
    }
    yourSel.addEventListener('change', update);
    enemySel.addEventListener('change', update);

    btn.addEventListener('click', function() {
        if (!yourSel.value || !enemySel.value) return;
        var url = '$baseUrl'.replace('__YOUR__', yourSel.value).replace('__ENEMY__', enemySel.value);
        window.location.href = url;
    });
})();
JS
);
?>
