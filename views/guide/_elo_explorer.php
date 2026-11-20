<?php

/**
 * Interactive Elo explorer: the 1v1 Random Map ladder as a smooth area chart, tinted by skill band,
 * with a rating slider and a live readout (band, percentile, players above, distance to the next band
 * and milestone, a tip with a link to the band's guides).
 * Data: components/data/elo_distribution.json. Styles: web/css/elo-explorer.css.
 *
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Elo;

$file = Yii::getAlias('@app/components/data/elo_distribution.json');
$data = is_file($file) ? json_decode(file_get_contents($file), true) : null;
$buckets = $data['buckets'] ?? [];
if (!$buckets) return;

$bands = [
    'beginner' => [0, 900],
    'intermediate' => [900, 1300],
    'advanced' => [1300, 1700],
    'expert' => [1700, null],
];
$tips = [
    'beginner' => 'Learn one build order, play it every game and keep your town center making villagers.',
    'intermediate' => 'Scout early, keep your production buildings busy and spend your resources before they pile up.',
    'advanced' => 'Watch your own losses back and fix one mistake at a time, because idle time decides games here.',
    'expert' => 'Prepare plans for each civ and map, and borrow timings from pro games.',
];
$milestones = [1000, 1200, 1500, 2000];

$total = 0;
foreach ($buckets as $b) $total += $b['players'];
$maxRating = (int)end($buckets)['to'];
foreach ($bands as $key => [$lo, $hi]) {
    $bands[$key][1] = $hi ?? $maxRating;
}

/** Rating at which the given share (0..1) of players inside [lo, hi) sit below, spread evenly inside each bucket. */
$quantile = function ($lo, $hi, $q) use ($buckets) {
    $in = 0;
    foreach ($buckets as $b) {
        $a = max($b['from'], $lo);
        $z = min($b['to'], $hi);
        if ($z > $a) $in += $b['players'] * ($z - $a) / ($b['to'] - $b['from']);
    }
    $target = $in * $q;
    $run = 0;
    foreach ($buckets as $b) {
        $a = max($b['from'], $lo);
        $z = min($b['to'], $hi);
        if ($z <= $a) continue;
        $n = $b['players'] * ($z - $a) / ($b['to'] - $b['from']);
        if ($run + $n >= $target && $n > 0) return $a + ($target - $run) / $n * ($z - $a);
        $run += $n;
    }
    return $hi;
};

$bandInfo = [];
foreach ($bands as $key => [$lo, $hi]) {
    $in = 0;
    foreach ($buckets as $b) if ($b['from'] >= $lo && $b['from'] < $hi) $in += $b['players'];
    $bandInfo[$key] = [
        'key' => $key,
        'label' => Elo::label($key),
        'range' => Elo::range($key),
        'lo' => $lo,
        'hi' => $hi,
        'share' => $total ? $in / $total : 0,
        'mid' => (int)(round($quantile($lo, $hi, 0.5) / 10) * 10),
        'tip' => $tips[$key],
        'url' => Url::to(['guide/index', 'elo' => $key]),
        'icon' => '/images/icons/' . $key . '.png',
    ];
}
$median = (int)(round($quantile(0, $maxRating, 0.5) / 10) * 10);

// Chart geometry (SVG user units; the SVG scales to the box width).
$W = 400;
$H = 172;
$L = 8;
$R = $W - 8;
$top = 28;
$base = 146;
$x = function ($r) use ($L, $R, $maxRating) {
    return $L + $r / $maxRating * ($R - $L);
};
$peak = max(array_column($buckets, 'players'));
$y = function ($n) use ($top, $base, $peak) {
    return $base - $n / $peak * ($base - $top);
};
$f = function ($v) {
    return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
};

// Curve points: one per bucket centre, plus the two ends of the axis.
$pts = [[$x(0), $y($buckets[0]['players'])]];
foreach ($buckets as $b) $pts[] = [$x(($b['from'] + $b['to']) / 2), $y($b['players'])];
$pts[] = [$x($maxRating), $y(end($buckets)['players'])];

// Catmull-Rom through the points as cubic Beziers, control points kept above the baseline.
$d = 'M' . $f($pts[0][0]) . ' ' . $f($base) . ' L' . $f($pts[0][0]) . ' ' . $f($pts[0][1]);
$cnt = count($pts);
for ($i = 0; $i < $cnt - 1; $i++) {
    $p0 = $pts[max(0, $i - 1)];
    $p1 = $pts[$i];
    $p2 = $pts[$i + 1];
    $p3 = $pts[min($cnt - 1, $i + 2)];
    $c1x = $p1[0] + ($p2[0] - $p0[0]) / 6;
    $c1y = min($base, $p1[1] + ($p2[1] - $p0[1]) / 6);
    $c2x = $p2[0] - ($p3[0] - $p1[0]) / 6;
    $c2y = min($base, $p2[1] - ($p3[1] - $p1[1]) / 6);
    $d .= ' C' . $f($c1x) . ' ' . $f($c1y) . ' ' . $f($c2x) . ' ' . $f($c2y) . ' ' . $f($p2[0]) . ' ' . $f($p2[1]);
}
$d .= ' L' . $f($x($maxRating)) . ' ' . $f($base) . ' Z';

$uid = 'elox' . substr(md5(uniqid('', true)), 0, 6);
$fetched = !empty($data['fetched']) ? date('j M Y', strtotime($data['fetched'])) : '';

$config = [
    'total' => $total,
    'max' => $maxRating,
    'median' => $median,
    'buckets' => array_map(function ($b) {
        return [(int)$b['from'], (int)$b['to'], (int)$b['players']];
    }, $buckets),
    'bands' => array_values($bandInfo),
    'milestones' => $milestones,
    'geom' => ['L' => $L, 'R' => $R, 'top' => $top, 'base' => $base, 'peak' => $peak],
];
?>
<link rel="stylesheet" href="/css/elo-explorer.css">
<section class="elo-x" id="<?= $uid ?>" aria-labelledby="<?= $uid ?>-title">
    <div class="elo-x-head">
        <h2 class="elo-x-title" id="<?= $uid ?>-title">Where does your rating put you?</h2>
    </div>

    <div class="elo-x-chart">
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-labelledby="<?= $uid ?>-svgdesc" focusable="false">
            <desc id="<?= $uid ?>-svgdesc">Players per 50 Elo on the 1v1 Random Map ladder. Half the players are below <?= $median ?>.
                <?php foreach ($bandInfo as $b): ?><?= Html::encode($b['label'] . ' (' . $b['range'] . '): ' . round($b['share'] * 100) . '% of players. ') ?><?php endforeach; ?></desc>
            <defs>
                <path id="<?= $uid ?>-area" d="<?= $d ?>"/>
                <clipPath id="<?= $uid ?>-you">
                    <rect class="elo-x-you-clip" x="0" y="0" width="<?= $f($x($median)) ?>" height="<?= $H ?>"/>
                </clipPath>
                <?php foreach ($bandInfo as $b): ?>
                    <clipPath id="<?= $uid ?>-clip-<?= $b['key'] ?>">
                        <rect x="<?= $f($b['lo'] ? $x($b['lo']) : 0) ?>" y="0" width="<?= $f(($b['hi'] >= $maxRating ? $W : $x($b['hi'])) - ($b['lo'] ? $x($b['lo']) : 0)) ?>" height="<?= $base + 1 ?>"/>
                    </clipPath>
                <?php endforeach; ?>
            </defs>

            <?php foreach ($bandInfo as $b): ?>
                <g class="elo-x-band elo-x-band-<?= $b['key'] ?>" data-band="<?= $b['key'] ?>" clip-path="url(#<?= $uid ?>-clip-<?= $b['key'] ?>)">
                    <use href="#<?= $uid ?>-area" class="elo-x-fill-weak"/>
                    <use href="#<?= $uid ?>-area" class="elo-x-fill-strong" clip-path="url(#<?= $uid ?>-you)"/>
                    <use href="#<?= $uid ?>-area" class="elo-x-line"/>
                </g>
            <?php endforeach; ?>

            <line class="elo-x-baseline" x1="<?= $L ?>" x2="<?= $R ?>" y1="<?= $base ?>" y2="<?= $base ?>"/>
            <?php foreach ([900, 1300, 1700] as $edge): ?>
                <line class="elo-x-edge" x1="<?= $f($x($edge)) ?>" x2="<?= $f($x($edge)) ?>" y1="<?= $top - 6 ?>" y2="<?= $base ?>"/>
            <?php endforeach; ?>
            <?php for ($t = 0; $t <= $maxRating; $t += 500): ?>
                <line class="elo-x-tick" x1="<?= $f($x($t)) ?>" x2="<?= $f($x($t)) ?>" y1="<?= $base ?>" y2="<?= $base + 4 ?>"/>
                <text class="elo-x-axis" x="<?= $f($x($t)) ?>" y="<?= $base + 17 ?>" text-anchor="<?= $t === 0 ? 'start' : 'middle' ?>"><?= $t ?></text>
            <?php endfor; ?>

            <?php foreach ($bandInfo as $b): ?>
                <rect class="elo-x-hit" data-band="<?= $b['key'] ?>" x="<?= $f($b['lo'] ? $x($b['lo']) : 0) ?>" y="<?= $top - 6 ?>" width="<?= $f(($b['hi'] >= $maxRating ? $W : $x($b['hi'])) - ($b['lo'] ? $x($b['lo']) : 0)) ?>" height="<?= $base - $top + 6 ?>">
                    <title><?= Html::encode($b['label'] . ', ' . $b['range'] . ': ' . round($b['share'] * 100) . '% of players. Click to jump to ' . $b['mid'] . '.') ?></title>
                </rect>
            <?php endforeach; ?>

            <g class="elo-x-marker" transform="translate(<?= $f($x($median)) ?> 0)" aria-hidden="true">
                <line class="elo-x-marker-line" x1="0" x2="0" y1="<?= $top - 6 ?>" y2="<?= $base ?>"/>
                <circle class="elo-x-marker-dot" cx="0" cy="<?= $base ?>" r="4"/>
                <g class="elo-x-marker-tag">
                    <rect x="-22" y="2" width="44" height="20" rx="10"/>
                    <text x="0" y="16" text-anchor="middle"><?= $median ?></text>
                </g>
            </g>
        </svg>
    </div>

    <div class="elo-x-control">
        <label class="elo-x-label" for="<?= $uid ?>-range">Your rating</label>
        <input type="number" class="elo-x-number form-control form-control-sm" id="<?= $uid ?>-num" min="0" max="<?= $maxRating ?>" step="1" value="<?= $median ?>" inputmode="numeric" aria-label="Your rating, exact value">
    </div>
    <input type="range" class="elo-x-range" id="<?= $uid ?>-range" min="0" max="<?= $maxRating ?>" step="10" value="<?= $median ?>"
        style="--s1: <?= round(900 / $maxRating, 4) ?>; --s2: <?= round(1300 / $maxRating, 4) ?>; --s3: <?= round(1700 / $maxRating, 4) ?>">

    <div class="elo-x-chips" role="group" aria-label="Jump to a skill band">
        <?php foreach ($bandInfo as $b): ?>
            <button type="button" class="elo-x-chip elo-x-chip-<?= $b['key'] ?>" data-band="<?= $b['key'] ?>" aria-pressed="false" title="<?= Html::encode($b['range']) ?>">
                <img src="<?= Html::encode($b['icon']) ?>" alt="" width="24" height="24">
                <span class="elo-x-chip-text">
                    <span class="elo-x-chip-name"><?= Html::encode($b['label']) ?></span>
                    <span class="elo-x-chip-share"><?= round($b['share'] * 100) ?>%</span>
                </span>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="elo-x-readout" aria-live="polite">
        <div class="elo-x-you">
            <img class="elo-x-you-icon" src="" alt="" width="40" height="40" data-out="icon">
            <div class="elo-x-you-text">
                <div class="elo-x-you-band"><strong data-out="band"></strong> <span data-out="range"></span></div>
                <p class="elo-x-pct" data-out="pct"></p>
            </div>
        </div>
        <div class="elo-x-stats">
            <p class="elo-x-stat"><strong data-out="aboveN"></strong> <span data-out="aboveT"></span></p>
            <p class="elo-x-stat"><strong data-out="bandN"></strong> <span data-out="bandT"></span></p>
            <p class="elo-x-stat"><strong data-out="mileN"></strong> <span data-out="mileT"></span></p>
        </div>
        <p class="elo-x-tip"><span data-out="tip"></span> <a data-out="link" href="#"></a></p>
    </div>

    <p class="elo-x-note">
        <?= number_format($total) ?> active players on the <a href="https://www.aoe2companion.com" target="_blank" rel="noopener">aoe2companion</a> RM 1v1 leaderboard<?= $fetched ? ', ' . Html::encode($fetched) : '' ?>.
        Long inactive accounts are not counted.
    </p>

    <script type="application/json" class="elo-x-data"><?= json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
</section>
<script>
(function () {
    var root = document.getElementById(<?= json_encode($uid) ?>);
    if (!root) return;
    var cfg = JSON.parse(root.querySelector('.elo-x-data').textContent);
    var G = cfg.geom, MAX = cfg.max, TOTAL = cfg.total;
    var range = root.querySelector('.elo-x-range');
    var num = root.querySelector('.elo-x-number');
    var marker = root.querySelector('.elo-x-marker');
    var dot = root.querySelector('.elo-x-marker-dot');
    var tag = root.querySelector('.elo-x-marker-tag');
    var tagText = tag.querySelector('text');
    var youClip = root.querySelector('.elo-x-you-clip');
    var out = {};
    root.querySelectorAll('[data-out]').forEach(function (el) { out[el.getAttribute('data-out')] = el; });
    var chips = root.querySelectorAll('.elo-x-chip');
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var STORE = 'eloExplorerRating';

    function xOf(r) { return G.L + r / MAX * (G.R - G.L); }
    function yOf(n) { return G.base - n / G.peak * (G.base - G.top); }

    // Same points as the PHP curve, to put the dot on the line.
    var pts = [[0, cfg.buckets[0][2]]];
    cfg.buckets.forEach(function (b) { pts.push([(b[0] + b[1]) / 2, b[2]]); });
    pts.push([MAX, cfg.buckets[cfg.buckets.length - 1][2]]);
    function curveY(r) {
        var i = 0;
        while (i < pts.length - 2 && pts[i + 1][0] <= r) i++;
        var p0 = pts[Math.max(0, i - 1)], p1 = pts[i], p2 = pts[i + 1], p3 = pts[Math.min(pts.length - 1, i + 2)];
        var t = (r - p1[0]) / (p2[0] - p1[0] || 1);
        t = Math.max(0, Math.min(1, t));
        var y0 = yOf(p1[1]), y3 = yOf(p2[1]);
        var c1 = Math.min(G.base, y0 + (yOf(p2[1]) - yOf(p0[1])) / 6);
        var c2 = Math.min(G.base, y3 - (yOf(p3[1]) - yOf(p1[1])) / 6);
        var u = 1 - t;
        return u * u * u * y0 + 3 * u * u * t * c1 + 3 * u * t * t * c2 + t * t * t * y3;
    }

    function below(r) {
        var n = 0;
        cfg.buckets.forEach(function (b) {
            if (b[1] <= r) n += b[2];
            else if (b[0] < r) n += b[2] * (r - b[0]) / (b[1] - b[0]);
        });
        return n;
    }

    function bandOf(r) {
        for (var i = cfg.bands.length - 1; i >= 0; i--) if (r >= cfg.bands[i].lo) return cfg.bands[i];
        return cfg.bands[0];
    }

    function fmt(n) { return Math.round(n).toLocaleString('en-US'); }
    function pctText(p) {
        if (p >= 99 && p < 100) return (Math.floor(p * 10) / 10).toFixed(1);
        if (p > 0 && p < 1) return (Math.max(0.1, Math.round(p * 10) / 10)).toFixed(1);
        return String(Math.round(p));
    }
    function roughCount(n) {
        if (n < 50) return fmt(n);
        if (n < 1000) return fmt(Math.round(n / 10) * 10);
        return fmt(Math.round(n / 100) * 100);
    }

    function render(r) {
        r = Math.max(0, Math.min(MAX, Math.round(r)));
        var x = xOf(r);
        marker.setAttribute('transform', 'translate(' + x.toFixed(2) + ' 0)');
        dot.setAttribute('cy', curveY(r).toFixed(2));
        var tx = Math.max(G.L + 22, Math.min(G.R - 22, x)) - x;
        tag.setAttribute('transform', 'translate(' + tx.toFixed(2) + ' 0)');
        tagText.textContent = r;
        youClip.setAttribute('width', x.toFixed(2));

        var band = bandOf(r);
        root.setAttribute('data-band', band.key);
        chips.forEach(function (c) { c.setAttribute('aria-pressed', c.getAttribute('data-band') === band.key ? 'true' : 'false'); });

        var b = below(r), above = TOTAL - b, p = b / TOTAL * 100, top = above / TOTAL * 100;
        out.icon.src = band.icon;
        out.band.textContent = band.label;
        out.range.textContent = band.range;

        if (above < 0.5) {
            out.pct.innerHTML = 'Higher than <strong>every</strong> ranked 1v1 player';
            out.aboveN.textContent = '0';
            out.aboveT.textContent = 'players above you';
        } else {
            out.pct.innerHTML = b < 0.5
                ? 'At the very bottom of the ladder'
                : 'Better than <strong>' + pctText(p) + '%</strong> of ranked 1v1 players';
            var n = Math.max(1, Math.round(above));
            var rough = n < 50 || TOTAL - n < 50 ? fmt(n) : '≈' + roughCount(n);
            out.aboveN.textContent = rough;
            out.aboveT.textContent = (n === 1 ? 'player' : 'players') + ' above you'
                + (top < 99.5 ? ', the top ' + pctText(top) + '%' : '');
        }

        var idx = cfg.bands.indexOf(band);
        var next = cfg.bands[idx + 1];
        if (next) {
            out.bandN.textContent = fmt(next.lo - r);
            out.bandT.textContent = (next.lo - r === 1 ? 'point' : 'points') + ' to ' + next.label + ' (' + next.lo + ')';
        } else {
            out.bandN.textContent = band.label;
            out.bandT.textContent = 'is the top band';
        }

        var m = cfg.milestones.filter(function (v) { return v > r; })[0];
        var lastM = cfg.milestones[cfg.milestones.length - 1];
        if (m) {
            out.mileN.textContent = fmt(m - r);
            out.mileT.textContent = (m - r === 1 ? 'point' : 'points') + ' to the ' + m + ' mark';
        } else {
            out.mileN.textContent = lastM + '+';
            out.mileT.textContent = 'past the last milestone';
        }

        out.tip.textContent = band.tip;
        out.link.href = band.url;
        out.link.textContent = band.label + ' guides';

        range.setAttribute('aria-valuetext', r + ' Elo, ' + band.label);
        return r;
    }

    var current = cfg.median;
    function set(r, from) {
        current = render(r);
        if (from !== 'range') range.value = current;
        if (from !== 'num') num.value = current;
        try { localStorage.setItem(STORE, String(current)); } catch (e) {}
    }

    var anim = null;
    function glide(to) {
        if (anim) cancelAnimationFrame(anim);
        if (reduceMotion) { set(to); return; }
        var from = current, t0 = null, dur = 260;
        function step(ts) {
            if (t0 === null) t0 = ts;
            var k = Math.min(1, (ts - t0) / dur);
            var e = 1 - Math.pow(1 - k, 3);
            set(from + (to - from) * e);
            anim = k < 1 ? requestAnimationFrame(step) : null;
        }
        anim = requestAnimationFrame(step);
    }

    range.addEventListener('input', function () {
        if (anim) { cancelAnimationFrame(anim); anim = null; }
        set(+range.value, 'range');
    });
    range.addEventListener('keydown', function (e) {
        if (e.key === 'PageUp' || e.key === 'PageDown') {
            e.preventDefault();
            set(current + (e.key === 'PageUp' ? 100 : -100));
        }
    });
    num.addEventListener('input', function () {
        if (num.value === '' || isNaN(+num.value)) return;
        set(+num.value, 'num');
    });
    num.addEventListener('change', function () { set(+num.value || 0); });

    function bandByKey(k) {
        for (var i = 0; i < cfg.bands.length; i++) if (cfg.bands[i].key === k) return cfg.bands[i];
        return null;
    }
    root.querySelectorAll('.elo-x-hit, .elo-x-chip').forEach(function (el) {
        el.addEventListener('click', function () {
            var b = bandByKey(el.getAttribute('data-band'));
            if (b) glide(b.mid);
        });
    });

    var start = cfg.median;
    try {
        var saved = parseInt(localStorage.getItem(STORE), 10);
        if (!isNaN(saved)) start = saved;
    } catch (e) {}
    set(start);
})();
</script>
