<?php

namespace app\components;

use app\models\Unit;

/**
 * The upgrade line a unit belongs to (Archer, Crossbowman, Arbalester), built from upgrades_from / upgrades_to,
 * including side branches (Winged Hussar from Light Cavalry) and the elite step of unique units that only exists
 * as elite_variant data. Used by the Combat Stats table of the unit page.
 */
class UnitLine
{
    /** Combat stat columns and the matching elite_variant keys. */
    const STATS = [
        'hit_points' => 'hit_points', 'melee_attack' => 'melee_attack', 'pierce_attack' => 'pierce_attack',
        'melee_armor' => 'melee_armor', 'pierce_armor' => 'pierce_armor', 'range_val' => 'range',
        'min_range' => 'min_range', 'blast_radius' => 'blast_radius',
        'accuracy' => 'accuracy', 'rate_of_fire' => 'rate_of_fire', 'speed' => 'speed', 'line_of_sight' => 'line_of_sight',
    ];

    private static $all = null;

    private static function units()
    {
        if (self::$all === null) {
            self::$all = [];
            foreach (Unit::find()->all() as $u) {
                self::$all[strtolower($u->name)] = $u;
            }
        }
        return self::$all;
    }

    /** "Camel Scout (Gurjaras only)" -> "camel scout" */
    private static function clean($name)
    {
        $name = preg_replace('/\s*\([^)]*\)|requires.*$|since.*$|before .*$|\*/i', '', (string)$name);
        return strtolower(trim($name));
    }

    /** Unit names inside a glued string such as "Fast Fire ShipDragon Ship (Chinese only)". */
    private static function namesIn($text)
    {
        $text = strtolower(trim(preg_replace('/\([^)]*\)/', '', (string)$text)));
        if (isset(self::units()[$text])) return [$text];
        // "elite samurai" is only an upgrade, not the Samurai again
        $text = preg_replace('/\belite\s+[a-z\' -]+/', ' ', $text);
        $found = [];
        $names = array_keys(self::units());
        usort($names, function ($a, $b) { return strlen($b) - strlen($a); });
        foreach ($names as $n) {
            if ($n !== '' && strpos($text, $n) !== false) {
                $found[] = $n;
                $text = str_replace($n, ' ', $text);
            }
        }
        return $found;
    }

    private static function parentOf(Unit $u)
    {
        $p = self::clean($u->upgrades_from);
        return $p !== '' ? (self::units()[$p] ?? null) : null;
    }

    private static function childrenOf(Unit $u)
    {
        $kids = [];
        foreach (self::namesIn($u->upgrades_to) as $n) {
            $kids[$n] = self::units()[$n];
        }
        foreach (self::units() as $n => $c) {
            if (self::clean($c->upgrades_from) === strtolower($u->name)) {
                $kids[$n] = $c;
            }
        }
        // the regular unit first, unique side branches after it
        uasort($kids, function ($a, $b) { return (int)$a->is_unique - (int)$b->is_unique; });
        return array_values($kids);
    }

    /** Age name for sorting and grouping; odd values like "Castle Age before ..." are cut down. */
    public static function age($age)
    {
        if (preg_match('/(Dark|Feudal|Castle|Imperial) Age/', (string)$age, $m)) return $m[0];
        return 'Imperial Age';
    }

    /**
     * Columns of the line, in order: each is
     * [name, age, icon, unit (or null), stats[attr => value], parent index, upgrade [food, wood, gold, time], current]
     * Returns [] when the unit is on its own.
     */
    public static function columns(Unit $unit)
    {
        $units = self::units();
        $self = $units[strtolower($unit->name)] ?? $unit;
        $root = $self;
        $guard = 0;
        while (($p = self::parentOf($root)) && $guard++ < 10) {
            $root = $p;
        }

        $cols = [];
        $seen = [];
        $walk = function (Unit $u, $parentIdx) use (&$walk, &$cols, &$seen, $self) {
            if (isset($seen[$u->id])) return;
            $seen[$u->id] = true;
            $stats = [];
            foreach (self::STATS as $attr => $evKey) {
                $stats[$attr] = $u->$attr;
            }
            $upgrade = null;
            if ($parentIdx !== null) {
                $pu = $cols[$parentIdx]['unit'];
                $upgrade = [$pu->upgrade_cost_food, $pu->upgrade_cost_wood, $pu->upgrade_cost_gold, $pu->upgrade_time];
            }
            $idx = count($cols);
            $cols[] = [
                'name' => $u->name, 'age' => self::age($u->age), 'icon' => $u->iconUrl, 'unit' => $u,
                'stats' => $stats, 'parent' => $parentIdx, 'upgrade' => $upgrade,
                'current' => $u->id === $self->id, 'slug' => $u->slug,
            ];
            $kids = self::childrenOf($u);
            foreach ($kids as $k) {
                if (count($cols) < 12) $walk($k, $idx);
            }
            // the elite step of a unique unit lives only in elite_variant
            $ev = $u->elite_variant;
            if (is_string($ev)) $ev = json_decode($ev, true);
            if (is_string($ev)) $ev = json_decode($ev, true);
            if (!$kids && $ev && !empty($ev['name']) && !isset(self::units()[strtolower($ev['name'])])) {
                $estats = [];
                foreach (self::STATS as $attr => $evKey) {
                    $estats[$attr] = $ev[$evKey] ?? $u->$attr;
                }
                $cols[] = [
                    'name' => $ev['name'], 'age' => 'Imperial Age', 'icon' => UnitPills::iconForName($ev['name']) ?: $u->iconUrl,
                    'unit' => null, 'stats' => $estats, 'parent' => $idx,
                    'upgrade' => [$u->upgrade_cost_food, $u->upgrade_cost_wood, $u->upgrade_cost_gold, $u->upgrade_time],
                    'current' => false, 'slug' => null,
                ];
            }
        };
        $walk($root, null);

        if (count($cols) < 2) return [];
        // group by age, keeping the walk order inside an age
        $order = ['Dark Age' => 0, 'Feudal Age' => 1, 'Castle Age' => 2, 'Imperial Age' => 3];
        $indexed = array_keys($cols);
        usort($indexed, function ($a, $b) use ($cols, $order) {
            return [$order[$cols[$a]['age']] ?? 3, $a] <=> [$order[$cols[$b]['age']] ?? 3, $b];
        });
        $remap = array_flip($indexed);
        $sorted = [];
        foreach ($indexed as $old) {
            $c = $cols[$old];
            $c['parent'] = $c['parent'] === null ? null : $remap[$c['parent']];
            $sorted[] = $c;
        }
        return $sorted;
    }
}
