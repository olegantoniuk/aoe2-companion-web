<?php

namespace app\components;

use yii\helpers\Html;

/**
 * Draws one build order step as a small scene from game sprites: villagers at work next to what they work on,
 * a builder next to the building, a research icon, the age up, trained units.
 * The step carries "scene": {"actions": [{"kind": "gather", "villagers": 6, "task": "sheep"}, ...]}.
 * Sprites and their foot points: components/data/scene_manifest.json, files in web/images/scene/.
 * Every sprite stands with its foot point on one ground line. web/js/build-scenes.js then fits each scene
 * to the width of the column (wrapping long steps onto a second row) and centres it vertically.
 */
class BuildScene
{
    /** Sprites are drawn at this share of their file size. */
    const SCALE = 0.62;

    /** Extra size for sprites that come out too small next to the villagers. */
    const SIZE = [
        'buildings.farm' => 1.5,
        'objects.farm' => 1.5,
        'buildings.palisade' => 1.35,
    ];

    /** How much of a villager's width the next villager in a crowd overlaps; walkers keep their distance. */
    const OVERLAP = ['walk' => -0.12, 'build' => 0.42, 'berries' => 0.36];

    /** Resource or task -> [villager animation, object or building]. */
    const TASKS = [
        'sheep' => ['sheep', 'obj:sheep'],
        'wood' => ['wood', 'forest'],
        'berries' => ['berries', 'bushes'],
        'boar' => ['hunt', 'obj:boar_dead'],
        'hunt' => ['hunt', 'obj:boar_dead'],
        'deer' => ['hunt', 'obj:deer_dead'],
        'gold' => ['gold', 'obj:gold'],
        'stone' => ['stone', 'obj:stone'],
        'farm' => ['farm', 'bld:farm'],
        'fish' => ['fish', null],
    ];

    const BUILDINGS = [
        'House' => 'house', 'Lumber Camp' => 'lumber_camp', 'Mill' => 'mill', 'Mining Camp' => 'mining_camp',
        'Barracks' => 'barracks', 'Archery Range' => 'archery_range', 'Stable' => 'stable', 'Blacksmith' => 'blacksmith',
        'Market' => 'market', 'Dock' => 'dock', 'Town Center' => 'town_center', 'Siege Workshop' => 'siege_workshop',
        'Monastery' => 'monastery', 'Castle' => 'castle', 'Watch Tower' => 'watch_tower', 'Palisade Wall' => 'palisade',
        'Palisade' => 'palisade', 'Farm' => 'farm', 'Fish Trap' => 'fish_trap', 'Outpost' => 'outpost',
    ];

    /**
     * Groups of sprites drawn as one object: [group, key, foot x, foot y (up is negative), size].
     * Feet are in drawn pixels; the first entry is the one the villagers work on.
     */
    const CLUSTERS = [
        'forest' => [
            ['objects', 'tree', 30, 0, 0.95],
            ['objects', 'trees', 150, 6, 0.95],
            ['objects', 'tree', 250, 2, 0.95],
            ['objects', 'trees', 82, -36, 0.95],
            ['objects', 'tree', 205, -40, 0.95],
            ['objects', 'trees', 300, -34, 0.95],
            ['objects', 'tree', 125, -72, 0.95],
            ['objects', 'trees', 250, -76, 0.95],
        ],
        'bushes' => [
            ['objects', 'berries', 30, 0, 0.85],
            ['objects', 'berries', 104, 6, 0.85],
            ['objects', 'berries', 66, -28, 0.85],
            ['objects', 'berries', 140, -24, 0.85],
            ['objects', 'berries', 176, 4, 0.85],
        ],
    ];

    private static $m = null;

    private static function manifest()
    {
        if (self::$m === null) {
            $f = __DIR__ . '/data/scene_manifest.json';
            self::$m = is_file($f) ? json_decode(file_get_contents($f), true) : [];
        }
        return self::$m;
    }

    private static function entry($group, $key)
    {
        return self::manifest()[$group][$key] ?? null;
    }

    private static function scaleOf($group, $key, $mul = 1)
    {
        return self::SCALE * (self::SIZE[$group . '.' . $key] ?? 1) * $mul;
    }

    private static function img($e, $s, $class, $style)
    {
        return '<img src="/images/scene/' . Html::encode($e['file']) . '" alt="" width="' . round($e['w'] * $s) . '" height="' . round($e['h'] * $s) . '"'
            . ' class="scene-sprite ' . $class . '" style="' . $style . '" loading="lazy">';
    }

    /** One sprite standing on the ground line: its foot point lands on the line. */
    private static function sprite($group, $key, $extra = '', $style = '')
    {
        $e = self::entry($group, $key);
        if (!$e) return '';
        $s = self::scaleOf($group, $key);
        // how far the picture reaches below its foot point
        $below = round(($e['h'] - $e['foot'][1]) * $s);
        return self::img($e, $s, $extra, 'margin-bottom:-' . $below . 'px;' . $style);
    }

    /** Several sprites placed around one ground point, drawn back to front, as one block on the ground line. */
    private static function cluster($name, $style = '')
    {
        $items = [];
        $minX = $minY = INF;
        $maxX = $maxY = -INF;
        foreach (self::CLUSTERS[$name] as [$group, $key, $fx, $fy, $mul]) {
            $e = self::entry($group, $key);
            if (!$e) continue;
            $s = self::scaleOf($group, $key, $mul);
            $x = $fx - $e['foot'][0] * $s;
            $y = $fy - $e['foot'][1] * $s;
            $items[] = [$e, $s, $x, $y, $fy];
            $minX = min($minX, $x); $minY = min($minY, $y);
            $maxX = max($maxX, $x + $e['w'] * $s); $maxY = max($maxY, $y + $e['h'] * $s);
        }
        if (!$items) return ['', 0];
        usort($items, function ($a, $b) { return $a[4] <=> $b[4]; });
        $html = '';
        foreach ($items as [$e, $s, $x, $y]) {
            $html .= self::img($e, $s, 'scene-piece', 'left:' . round($x - $minX) . 'px;top:' . round($y - $minY) . 'px');
        }
        $w = round($maxX - $minX);
        $h = round($maxY - $minY);
        $wrap = '<span class="scene-cluster scene-' . $name . '" style="width:' . $w . 'px;height:' . $h . 'px;margin-bottom:-' . round($maxY) . 'px;' . $style . '">'
            . $html . '</span>';
        // where the first entry's foot sits, counted from the left edge of the block
        return [$wrap, self::CLUSTERS[$name][0][2] - $minX];
    }

    /** A palisade line: segments stepping back to the right, drawn back to front. */
    private static function palisade()
    {
        $e = self::entry('buildings', 'palisade');
        if (!$e) return '';
        $s = self::scaleOf('buildings', 'palisade');
        $w = $e['w'] * $s;
        $h = $e['h'] * $s;
        $n = 6;
        $dx = $w * 0.62;
        $dy = $w * 0.2;
        $html = '';
        for ($i = $n - 1; $i >= 0; $i--) {
            $html .= self::img($e, $s, 'scene-piece', 'left:' . round($i * $dx) . 'px;top:' . round(($n - 1 - $i) * $dy) . 'px');
        }
        $bw = round(($n - 1) * $dx + $w);
        $bh = round(($n - 1) * $dy + $h);
        $below = round(($e['h'] - $e['foot'][1]) * $s);
        return '<span class="scene-cluster scene-palisade" style="width:' . $bw . 'px;height:' . $bh . 'px;margin-bottom:-' . $below . 'px">' . $html . '</span>';
    }

    /**
     * Up to three villagers doing a task, male and female in turn, with "×N" when there are more.
     * Returns the html and how far the last villager's foot is from the right edge of the crowd.
     */
    private static function crowd($task, $n)
    {
        $n = max(1, (int)$n);
        $html = '<span class="scene-crowd">';
        $tail = 0;
        $k = self::OVERLAP[$task] ?? 0.4;
        for ($i = 0; $i < min($n, 3); $i++) {
            $group = $i % 2 ? 'villagers_female' : 'villagers';
            $e = self::entry($group, $task);
            if (!$e) continue;
            $s = self::scaleOf($group, $task);
            $html .= self::sprite($group, $task, 'scene-vil', $i ? 'margin-left:' . round(-$k * self::entry('villagers', $task)['w'] * self::SCALE) . 'px' : '');
            $tail = ($e['w'] - $e['foot'][0]) * $s;
        }
        if ($n > 1) $html .= '<span class="scene-count">×' . $n . '</span>';
        return [$html . '</span>', $tail];
    }

    private static function villagers($task, $n)
    {
        return self::crowd($task, $n)[0];
    }

    private static function target($ref)
    {
        if (!$ref) return '';
        if ($ref === 'forest' || $ref === 'bushes') return self::cluster($ref)[0];
        [$kind, $key] = explode(':', $ref);
        if ($kind === 'bld' && $key === 'palisade') return self::palisade();
        if ($kind === 'bld') $key = self::look($key);
        return self::sprite($kind === 'obj' ? 'objects' : 'buildings', $key, 'scene-target');
    }

    private static function badge($img, $label, $class = '')
    {
        return '<span class="scene-badge ' . $class . '">' . ($img ? '<img src="' . Html::encode($img) . '" alt="">' : '')
            . '<span>' . Html::encode($label) . '</span></span>';
    }

    /** Buildings already drawn in the current step, so a research there does not draw it again. */
    private static $drawn = [];

    /** Still in the Dark Age: buildings that have a Dark Age look use it. Steps are rendered in order. */
    private static $dark = true;
    const DARK_LOOK = ['house', 'mill', 'barracks', 'dock', 'town_center'];

    private static function look($key)
    {
        return self::$dark && in_array($key, self::DARK_LOOK, true) ? $key . '_dark' : $key;
    }

    private static function action(array $a)
    {
        switch ($a['kind'] ?? 'note') {
            case 'gather':
                [$vil, $obj] = self::TASKS[$a['task'] ?? ''] ?? ['walk', null];
                if ($obj === 'forest' || $obj === 'bushes') {
                    // the villagers stand right at the edge of the wood or the bushes, in front of them
                    [$crowd, $tail] = self::crowd($vil, $a['villagers'] ?? 1);
                    $reach = $obj === 'forest' ? 34 : 64;
                    $anchor = self::cluster($obj)[1];
                    $block = self::cluster($obj, 'margin-left:' . round($reach - $tail - $anchor) . 'px')[0];
                    return '<span class="scene-group scene-work">' . $crowd . $block . '</span>';
                }
                if ($obj === 'obj:gold' || $obj === 'obj:stone') {
                    // miners stand at the edge of the pile, not a step away from its picture
                    [$crowd, $tail] = self::crowd($vil, $a['villagers'] ?? 1);
                    $key = substr($obj, 4);
                    $e = self::entry('objects', $key);
                    $sc = self::scaleOf('objects', $key);
                    $ml = round(0.42 * $e['w'] * $sc + 6 - $tail - $e['foot'][0] * $sc);
                    return '<span class="scene-group scene-work">' . $crowd . self::sprite('objects', $key, 'scene-target', 'margin-left:' . $ml . 'px') . '</span>';
                }
                return '<span class="scene-group">' . self::villagers($vil, $a['villagers'] ?? 1) . self::target($obj) . '</span>';
            case 'move':
                [, $obj] = self::TASKS[$a['to'] ?? ''] ?? [null, null];
                if ($obj === 'forest') $obj = 'obj:trees';
                if ($obj === 'bushes') $obj = 'obj:berries';
                return '<span class="scene-group">' . self::villagers('walk', $a['villagers'] ?? 1)
                    . '<span class="scene-arrow">' . str_repeat(UnitPills::chevron(), 3) . '</span>' . self::target($obj) . '</span>';
            case 'lure':
                $animal = ($a['task'] ?? 'boar') === 'deer' ? 'deer' : 'boar';
                return '<span class="scene-group">' . self::villagers('hunt_shoot', $a['villagers'] ?? 1) . self::target('obj:' . $animal) . '</span>';
            case 'build':
                $key = self::BUILDINGS[$a['building'] ?? ''] ?? null;
                $count = (int)($a['count'] ?? 1);
                if ($key) self::$drawn[] = $key;
                $bld = $key ? self::target('bld:' . $key) : self::badge(UnitPills::buildingIcon($a['building'] ?? ''), $a['building'] ?? 'Building');
                return '<span class="scene-group">' . self::villagers('build', $a['villagers'] ?? 1) . $bld
                    . ($count > 1 ? '<span class="scene-count">×' . $count . '</span>' : '') . '</span>';
            case 'research':
                $name = $a['tech'] ?? '';
                $file = '/images/techs/' . str_replace([' ', "'", '*', '(', ')', '-'], '', $name) . '.png';
                $icon = is_file(\Yii::getAlias('@webroot') . $file) ? $file : null;
                // the building it is researched in, then the technology
                $where = self::techBuilding($name);
                if ($where && in_array($where, self::$drawn, true)) $where = null;
                elseif ($where) self::$drawn[] = $where;
                return '<span class="scene-group">' . ($where ? self::target('bld:' . $where) : '')
                    . self::badge($icon, $name, 'scene-badge-tech') . '</span>';
            case 'age':
                $age = $a['age'] ?? 'Feudal Age';
                $click = ($a['event'] ?? 'click') !== 'arrive';
                $label = ($click ? 'Click up to the ' : 'Reach the ') . $age;
                // the Town Center that does the age up; it keeps its Dark Age look while Feudal is researched
                if (!$click || $age !== 'Feudal Age') self::$dark = false;
                return '<span class="scene-group">' . self::sprite('buildings', self::look('town_center'), 'scene-target')
                    . self::badge('/images/ages/' . str_replace(' ', '', $age) . '.png', $label, 'scene-badge-age') . '</span>';
            case 'train':
            case 'scout':
                $unit = \app\models\Unit::findOne(['name' => $a['unit'] ?? '']);
                $sprite = $unit ? $unit->transparentSpriteUrl : null;
                $html = '<span class="scene-group">';
                if ($sprite) {
                    $html .= '<img src="' . Html::encode($sprite) . '" alt="" class="scene-unit" loading="lazy">';
                } elseif ($unit && $unit->iconUrl) {
                    $html .= self::badge($unit->iconUrl, $unit->name);
                }
                if (!empty($a['count']) && (int)$a['count'] > 1) $html .= '<span class="scene-count">×' . (int)$a['count'] . '</span>';
                return $html . '</span>';
        }
        return '';
    }

    /**
     * Pictures for the short step list: [url, kind] for the main thing a step does
     * (villager, building, technology, age shield or unit). Up to two.
     */
    public static function stepIcons(array $step)
    {
        $out = [];
        foreach ($step['scene']['actions'] ?? [] as $a) {
            $icon = null;
            $kind = 'unit';
            switch ($a['kind'] ?? '') {
                case 'gather':
                case 'move':
                case 'lure':
                    $icon = UnitPills::iconForSlug('villager');
                    break;
                case 'build':
                    $icon = UnitPills::buildingIcon($a['building'] ?? '');
                    $kind = 'building';
                    break;
                case 'research':
                    $file = '/images/techs/' . str_replace([' ', "'", '*', '(', ')', '-'], '', $a['tech'] ?? '') . '.png';
                    $icon = is_file(\Yii::getAlias('@webroot') . $file) ? $file : null;
                    $kind = 'tech';
                    break;
                case 'age':
                    $icon = UnitPills::ageIcon($a['age'] ?? 'Feudal Age');
                    $kind = 'age';
                    break;
                case 'train':
                case 'scout':
                    $u = \app\models\Unit::findOne(['name' => $a['unit'] ?? '']);
                    $icon = $u ? $u->iconUrl : null;
                    break;
            }
            if ($icon && !in_array([$icon, $kind], $out, true)) $out[] = [$icon, $kind];
            if (count($out) === 2) break;
        }
        return $out;
    }

    /** Building where a technology is researched, as a scene sprite key, or null. */
    private static function techBuilding($tech)
    {
        static $map = null;
        if ($map === null) {
            $map = \app\models\Technology::find()->select(['building', 'name'])->indexBy('name')->column();
        }
        return self::BUILDINGS[$map[$tech] ?? ''] ?? null;
    }

    /** The whole scene for one step, or '' when the step has nothing to draw ($first: the build's first step). Sizing happens in build-scenes.js. */
    public static function render(array $step, $first = false)
    {
        if ($first) self::$dark = true;
        $actions = $step['scene']['actions'] ?? [];
        self::$drawn = [];
        $parts = array_values(array_filter(array_map([self::class, 'action'], array_slice($actions, 0, 3))));
        if (!$parts) return '';
        return '<div class="scene"><div class="scene-inner">' . implode('', $parts) . '</div></div>';
    }
}
