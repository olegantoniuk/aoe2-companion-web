<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use app\assets\AppAsset;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode(\app\components\Seo::title($this)) ?></title>
    <?= \app\components\Seo::head($this) ?>
    <?php $this->head() ?>
    <script>
    (function(){var t=localStorage.getItem('theme')||'light';document.documentElement.setAttribute('data-theme',t);})();
    </script>
</head>
<body>
<?php $this->beginBody() ?>

<?php
NavBar::begin([
    // calligraphic wordmark: black on the light theme, white on the dark one
    'brandLabel' => (is_file(Yii::getAlias('@webroot') . '/images/brand/emblem.png')
            ? '<img src="/images/brand/emblem.png?v=' . filemtime(Yii::getAlias('@webroot') . '/images/brand/emblem.png') . '" alt="" class="brand-emblem">'
            : '')
        . '<img src="/images/logo/001-wordmark-black.png" alt="AoE2 Companion" class="brand-logo brand-logo-light">'
        . '<img src="/images/logo/001-wordmark-white.png" alt="AoE2 Companion" class="brand-logo brand-logo-dark">',
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar navbar-expand-lg navbar-light',
    ],
]);

// the knowledge base first; the reference pages follow in a row on wide screens
// and fold into one "Database" menu when the header runs out of room
$referenceItems = [
    ['label' => 'Civilizations', 'url' => ['/civilization/index']],
    ['label' => 'Units', 'url' => ['/unit/index']],
    ['label' => 'Buildings', 'url' => ['/building/index']],
    ['label' => 'Technologies', 'url' => ['/technology/index']],
    ['label' => 'Classes', 'url' => ['/unit/classes']],
];
$route = Yii::$app->controller->id;
$inReference = in_array($route, ['civilization', 'unit', 'building', 'technology'], true);
$menuItems = [
    ['label' => 'Builds', 'url' => ['/build/index']],
    ['label' => 'Guides', 'url' => ['/guide/index']],
    ['label' => 'Matchup', 'url' => ['/matchup/index']],
];
foreach ($referenceItems as $i => $item) {
    // shown in a row from 1400 px, and in the collapsed mobile menu
    $menuItems[] = $item + ['options' => ['class' => 'nav-item d-lg-none d-xxl-block' . ($i === 0 ? ' nav-item-ref-first' : '')]];
}
$menuItems[] = [
    'label' => 'Database',
    'items' => $referenceItems,
    'active' => $inReference,
    'options' => ['class' => 'nav-item dropdown d-none d-lg-block d-xxl-none'],
];

if (Yii::$app->user->isGuest) {
    $menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
} else {
    $menuItems[] = '<li class="nav-item">'
        . Html::beginForm(['/site/logout'], 'post', ['class' => 'd-flex'])
        . Html::submitButton(
            'Logout (' . Yii::$app->user->identity->username . ')',
            ['class' => 'btn btn-link nav-link']
        )
        . Html::endForm()
        . '</li>';
}

echo Nav::widget([
    'options' => ['class' => 'navbar-nav ms-auto'],
    'items' => $menuItems,
]);

// Theme toggle button
echo '<button class="theme-toggle ms-2" id="themeToggle" type="button" title="Toggle theme">'
    . '<svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>'
    . '<svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>'
    . '</button>';

NavBar::end();
?>

<div class="admin5-content-wrapper container">
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= Yii::$app->session->getFlash('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= Yii::$app->session->getFlash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?= $content ?>
</div>

<footer class="site-footer">
    <div class="container text-center">
        <p class="mb-0">AoE2 Companion &copy; <?= date('Y') ?></p>
    </div>
</footer>

<button type="button" class="back-to-top" id="backToTop" aria-label="Back to top" title="Back to top">
    <svg viewBox="0 0 16 16" width="18" height="18" aria-hidden="true"><path d="M3 10l5-5 5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>

<script>
// header that hides while scrolling down and comes back on the way up; a back to top button once the page has moved
(function () {
    var nav = document.querySelector('nav.navbar');
    var top = document.getElementById('backToTop');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var lastY = window.scrollY;
    var ticking = false;
    function update() {
        var y = window.scrollY;
        var menuOpen = nav && nav.querySelector('.navbar-collapse.show, .dropdown-menu.show');
        if (nav) {
            nav.classList.toggle('is-scrolled', y > 4);
            if (y > 80 && y > lastY + 4 && !menuOpen) nav.classList.add('is-hidden');
            else if (y < lastY - 4 || y <= 80) nav.classList.remove('is-hidden');
        }
        if (top) top.classList.toggle('is-visible', y > 400);
        lastY = y;
        ticking = false;
    }
    window.addEventListener('scroll', function () {
        if (!ticking) { window.requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    if (top) top.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
        top.blur();
    });
    update();
})();

document.getElementById('themeToggle').addEventListener('click', function() {
    var html = document.documentElement;
    var current = html.getAttribute('data-theme');
    var next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
});
</script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
