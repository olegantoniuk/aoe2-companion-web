<?php

namespace app\components;

use Yii;
use yii\helpers\Html;

/**
 * A small histogram of the 1v1 Random Map ladder (players per 50 Elo), with one skill band highlighted.
 * Data: components/data/elo_distribution.json, from the aoe2companion leaderboard (November 2026).
 */
class EloHistogram
{
    /** Elo ranges of the bands, as [from, to). */
    const RANGES = [
        'beginner' => [0, 900],
        'intermediate' => [900, 1300],
        'advanced' => [1300, 1700],
        'expert' => [1700, 99999],
    ];

    /** Ratings above this go into the last bar; the tail is too thin to show. */
    const MAX = 2600;

    private static $data = null;

    private static function data()
    {
        if (self::$data === null) {
            $f = __DIR__ . '/data/elo_distribution.json';
            self::$data = is_file($f) ? json_decode(file_get_contents($f), true) : ['buckets' => []];
        }
        return self::$data;
    }

    /** Share of ladder players inside the band, 0..1. */
    public static function share($band)
    {
        [$lo, $hi] = self::RANGES[$band] ?? [0, 0];
        $all = 0;
        $in = 0;
        foreach (self::data()['buckets'] as $b) {
            $all += $b['players'];
            if ($b['from'] >= $lo && $b['from'] < $hi) $in += $b['players'];
        }
        return $all ? $in / $all : 0;
    }

    /**
     * Inline SVG. $o: width, height, label (bool, show the share under the bars).
     */
    public static function render($band, array $o = [])
    {
        $w = $o['width'] ?? 220;
        $h = $o['height'] ?? 40;
        [$lo, $hi] = self::RANGES[$band] ?? [0, 0];
        $bars = [];
        foreach (self::data()['buckets'] as $b) {
            $key = min($b['from'], self::MAX);
            $bars[$key] = ($bars[$key] ?? 0) + $b['players'];
        }
        if (!$bars) return '';
        ksort($bars);
        $max = max($bars);
        $n = count($bars);
        $gap = 1;
        $bw = ($w - $gap * ($n - 1)) / $n;
        $svg = '';
        $i = 0;
        foreach ($bars as $from => $count) {
            $bh = max(1, round($count / $max * ($h - 2), 1));
            $x = round($i * ($bw + $gap), 2);
            $inBand = $from >= $lo && $from < $hi;
            $svg .= '<rect x="' . $x . '" y="' . ($h - $bh) . '" width="' . round($bw, 2) . '" height="' . $bh . '" rx="1" class="'
                . ($inBand ? 'elo-bar is-in' : 'elo-bar') . '"><title>' . Html::encode(Yii::t('app', '{from} Elo: {count} {players}', [
                    'from' => $from . ($from >= self::MAX ? '+' : ''),
                    'count' => number_format($count, 0, '.', Tr::isEnglish() ? ',' : "\u{00A0}"),
                    'players' => Tr::plural($count, 'player|players'),
                ])) . '</title></rect>';
            $i++;
        }
        $share = round(self::share($band) * 100);
        $title = Html::encode(Yii::t('app', '{range}: {share}% of ranked 1v1 players', ['range' => \app\models\Elo::range($band), 'share' => $share]));
        $html = '<span class="elo-hist elo-hist-' . Html::encode($band) . '" title="' . $title . '">'
            . '<svg viewBox="0 0 ' . $w . ' ' . $h . '" width="' . $w . '" height="' . $h . '" role="img" aria-label="' . $title . '">' . $svg . '</svg>';
        if ($o['label'] ?? true) {
            $html .= '<span class="elo-hist-label">' . Yii::t('app', '{range}, {share}% of ranked 1v1 players', [
                'range' => '<strong>' . Html::encode(\app\models\Elo::range($band)) . '</strong>',
                'share' => $share,
            ]) . '</span>';
        }
        return $html . '</span>';
    }
}
