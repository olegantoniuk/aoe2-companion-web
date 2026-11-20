<?php

namespace app\components;

use yii\helpers\Html;

/**
 * Draws one build order step as a small scene from game sprites: villagers at work next to what they work on,
 * a builder next to the building, a research icon, the age up, trained units.
 * The step carries "scene": {"actions": [{"kind": "gather", "villagers": 6, "task": "sheep"}, ...]}.
 * Sprites and their foot points: components/data/scene_manifest.json, files in web/images/scene/.
 */
class BuildScene
{
    /** Sprites are drawn at this share of their file size. */
    const SCALE = 0.62;

    /** Resource or task -> [villager animation, object or building]. */
    const TASKS = [
        'sheep' => ['sheep', 'obj:sheep'],
        'wood' => ['wood', 'obj:trees'],
        'berries' => ['berries', 'obj:berries'],
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

    private static $m = null;

    private static function manifest()
    {
        if (self::$m === null) {
            $f = __DIR__ . '/data/scene_manifest.json';
            self::$m = is_file($f) ? json_decode(file_get_contents($f), true) : [];
        }
        return self::$m;
    }

    /** One sprite standing on the ground line: its foot point lands on the line. */
    private static function sprite($group, $key, $extra = '')
    {
        $e = self::manifest()[$group][$key] ?? null;
        if (!$e) return '';
        $s = self::SCALE;
        $w = round($e['w'] * $s);
        $h = round($e['h'] * $s);
        // how far the picture reaches below its foot point
        $below = round(($e['h'] - $e['foot'][1]) * $s);
        return '<img src="/images/scene/' . Html::encode($e['file']) . '" alt="" width="' . $w . '" height="' . $h . '"'
            . ' class="scene-sprite ' . $extra . '" style="margin-bottom:-' . $below . 'px" loading="lazy">';
    }

    /** Up to three villagers doing a task, male and female in turn, with "×N" when there are more. */
    private static function villagers($task, $n)
    {
        $n = max(1, (int)$n);
        $html = '<span class="scene-crowd">';
        for ($i = 0; $i < min($n, 3); $i++) {
            $html .= self::sprite($i % 2 ? 'villagers_female' : 'villagers', $task, 'scene-vil');
        }
        if ($n > 1) $html .= '<span class="scene-count">×' . $n . '</span>';
        return $html . '</span>';
    }

    private static function target($ref)
    {
        if (!$ref) return '';
        [$kind, $key] = explode(':', $ref);
        return self::sprite($kind === 'obj' ? 'objects' : 'buildings', $key, 'scene-target');
    }

    private static function badge($img, $label, $class = '')
    {
        return '<span class="scene-badge ' . $class . '">' . ($img ? '<img src="' . Html::encode($img) . '" alt="">' : '')
            . '<span>' . Html::encode($label) . '</span></span>';
    }

    private static function action(array $a)
    {
        switch ($a['kind'] ?? 'note') {
            case 'gather':
                [$vil, $obj] = self::TASKS[$a['task'] ?? ''] ?? ['walk', null];
                return '<span class="scene-group">' . self::villagers($vil, $a['villagers'] ?? 1) . self::target($obj) . '</span>';
            case 'move':
                [, $obj] = self::TASKS[$a['to'] ?? ''] ?? [null, null];
                return '<span class="scene-group">' . self::villagers('walk', $a['villagers'] ?? 1)
                    . '<span class="scene-arrow">' . UnitPills::chevron() . UnitPills::chevron() . '</span>' . self::target($obj) . '</span>';
            case 'lure':
                $animal = ($a['task'] ?? 'boar') === 'deer' ? 'deer' : 'boar';
                return '<span class="scene-group">' . self::villagers('hunt_shoot', $a['villagers'] ?? 1) . self::target('obj:' . $animal) . '</span>';
            case 'build':
                $key = self::BUILDINGS[$a['building'] ?? ''] ?? null;
                $count = (int)($a['count'] ?? 1);
                $bld = $key ? self::target('bld:' . $key) : self::badge(UnitPills::buildingIcon($a['building'] ?? ''), $a['building'] ?? 'Building');
                return '<span class="scene-group">' . self::villagers('build', $a['villagers'] ?? 1) . $bld
                    . ($count > 1 ? '<span class="scene-count">×' . $count . '</span>' : '') . '</span>';
            case 'research':
                $name = $a['tech'] ?? '';
                $file = '/images/techs/' . str_replace([' ', "'", '*', '(', ')', '-'], '', $name) . '.png';
                $icon = is_file(\Yii::getAlias('@webroot') . $file) ? $file : null;
                // the building it is researched in, then the technology
                $where = self::techBuilding($name);
                return '<span class="scene-group">' . ($where ? self::target('bld:' . $where) : '')
                    . self::badge($icon, $name, 'scene-badge-tech') . '</span>';
            case 'age':
                $age = $a['age'] ?? 'Feudal Age';
                $label = (($a['event'] ?? 'click') === 'arrive' ? 'Reach the ' : 'Click up to the ') . $age;
                return self::badge('/images/ages/' . str_replace(' ', '', $age) . '.png', $label, 'scene-badge-age');
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

    /** Rough drawn width of a piece of scene HTML, from the width attributes of its images. */
    private static function width($html)
    {
        $w = 0;
        preg_match_all('/width="(\d+)"/', $html, $m);
        foreach ($m[1] as $x) $w += (int)$x;
        // villagers in a crowd overlap, badges and units have no width attribute
        $w -= 46 * max(0, substr_count($html, 'scene-vil') - substr_count($html, 'scene-crowd'));
        $w += 170 * substr_count($html, 'scene-badge') + 80 * substr_count($html, 'scene-unit');
        return $w + 28;
    }

    /** The whole scene for one step, or '' when the step has nothing to draw. */
    public static function render(array $step, $maxWidth = 600)
    {
        $actions = $step['scene']['actions'] ?? [];
        $parts = array_values(array_filter(array_map([self::class, 'action'], array_slice($actions, 0, 3))));
        if (!$parts) return '';
        $total = array_sum(array_map([self::class, 'width'], $parts));
        // everything stays on one row: a wide step is drawn smaller
        $zoom = $total > $maxWidth ? round($maxWidth / $total, 3) : 1;
        return '<div class="scene"><div class="scene-inner"' . ($zoom < 1 ? ' style="zoom:' . $zoom . '"' : '') . '>'
            . implode('', $parts) . '</div></div>';
    }
}
