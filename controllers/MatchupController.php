<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Unit;
use app\models\Civilization;

class MatchupController extends Controller
{
    private static $buildingOrder = [
        'Unique' => 0,
        'Barracks' => 1,
        'Stable' => 2,
        'Archery Range' => 3,
        'Siege Workshop' => 4,
        'Castle' => 5,
        'Monastery' => 6,
        'Town Center' => 7,
        'Market' => 8,
        'Dock' => 9,
        'Other' => 10,
    ];

    public function actionIndex()
    {
        $civs = Civilization::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('index', [
            'civs' => $civs,
        ]);
    }

    public function actionView($yourCiv, $enemyCiv)
    {
        $your = Civilization::find()->where(['slug' => $yourCiv])->one();
        $enemy = Civilization::find()->where(['slug' => $enemyCiv])->one();

        if (!$your || !$enemy) {
            throw new NotFoundHttpException('Civilization not found.');
        }

        $yourUnits = $this->getCivUnits($your->id);
        $enemyUnits = $this->getCivUnits($enemy->id);

        // Filter out King (no age) and Trade Cart
        $hiddenUnits = ['King', 'Trade Cart'];
        $enemyUnits = array_values(array_filter($enemyUnits, function ($u) use ($hiddenUnits) {
            return !empty($u->age) && !in_array($u->name, $hiddenUnits);
        }));

        // Build counter map
        $counterBonuses = [];
        $counters = $this->buildCounterMap($yourUnits, $enemyUnits, $counterBonuses);

        // Split into land and naval
        $landUnits = [];
        $navalUnits = [];
        foreach ($enemyUnits as $u) {
            if ($u->isNaval) {
                $navalUnits[] = $u;
            } else {
                $landUnits[] = $u;
            }
        }

        // Sort all units
        $landUnits = $this->sortUnits($landUnits);
        $navalUnits = $this->sortUnits($navalUnits);

        // Build upgrade lines for "All" tab
        $upgradeLines = $this->buildUpgradeLines($landUnits, $counters);
        $navalLines = $this->buildUpgradeLines($navalUnits, $counters);

        // Build byAge — only Feudal/Castle/Imperial (land only)
        $ageOrder = ['Feudal Age', 'Castle Age', 'Imperial Age'];
        $byAge = [];
        foreach ($landUnits as $u) {
            $cleanAge = preg_replace('/\s+(before|since).*/', '', $u->age ?? '');
            if (in_array($cleanAge, $ageOrder)) {
                $byAge[$cleanAge][] = $u;
            }
        }
        $byAge = array_merge(array_flip($ageOrder), $byAge);
        $byAge = array_filter($byAge, 'is_array');

        // Naval by age
        $navalByAge = [];
        foreach ($navalUnits as $u) {
            $cleanAge = preg_replace('/\s+(before|since).*/', '', $u->age ?? '');
            if (in_array($cleanAge, $ageOrder)) {
                $navalByAge[$cleanAge][] = $u;
            }
        }

        // Building counters — your units strong vs buildings
        $buildingCounters = $this->buildBuildingCounters($yourUnits);

        $civs = Civilization::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('view', [
            'yourCiv' => $your,
            'enemyCiv' => $enemy,
            'counters' => $counters,
            'upgradeLines' => $upgradeLines,
            'navalLines' => $navalLines,
            'byAge' => $byAge,
            'navalByAge' => $navalByAge,
            'counterBonuses' => $counterBonuses,
            'buildingCounters' => $buildingCounters,
            'civs' => $civs,
        ]);
    }

    /**
     * Get all units available to a civilization (unique + shared).
     */
    private function getCivUnits($civId)
    {
        $unique = Unit::find()
            ->where(['civilization_id' => $civId, 'is_unique' => 1])
            ->with('armorClasses')
            ->all();

        $shared = Unit::find()
            ->innerJoin('unit_availability ua', 'ua.unit_id = unit.id')
            ->where(['ua.civilization_id' => $civId, 'ua.available' => 1, 'unit.is_unique' => 0])
            ->with('armorClasses')
            ->all();

        return array_merge($unique, $shared);
    }

    /**
     * Sort units by building order: Unique → Barracks → Stable → Archery → Siege → Castle → Monastery → TC → Market → Dock.
     */
    private function sortUnits($units)
    {
        $order = self::$buildingOrder;
        usort($units, function ($a, $b) use ($order) {
            $aGroup = $a->is_unique ? 'Unique' : $a->buildingGroup;
            $bGroup = $b->is_unique ? 'Unique' : $b->buildingGroup;
            $aOrder = $order[$aGroup] ?? 10;
            $bOrder = $order[$bGroup] ?? 10;
            if ($aOrder !== $bOrder) return $aOrder - $bOrder;
            return strcasecmp($a->name, $b->name);
        });
        return $units;
    }

    /**
     * Build upgrade lines for grouped display.
     * Returns array of lines, each line = ['units' => [Unit, ...], 'counters' => [Unit, ...], 'isNaval' => bool]
     */
    private function buildUpgradeLines($units, $counters)
    {
        $byName = [];
        foreach ($units as $u) {
            $byName[$u->name] = $u;
        }

        // Find upgrade chains
        $visited = [];
        $lines = [];

        foreach ($units as $u) {
            if (isset($visited[$u->id])) continue;

            // Find root of chain (walk back through upgrades_from)
            $root = $u;
            while (!empty($root->upgrades_from) && isset($byName[$root->upgrades_from]) && !isset($visited[$byName[$root->upgrades_from]->id])) {
                $root = $byName[$root->upgrades_from];
            }

            // Build chain forward from root
            $chain = [];
            $current = $root;
            while ($current && !isset($visited[$current->id])) {
                $visited[$current->id] = true;
                $chain[] = $current;
                $nextName = $current->upgrades_to;
                $current = null;
                if ($nextName) {
                    if (isset($byName[$nextName])) {
                        $current = $byName[$nextName];
                    } else {
                        // Partial match
                        foreach ($byName as $n => $unit) {
                            if (strpos($nextName, $n) === 0 && !isset($visited[$unit->id])) {
                                $current = $unit;
                                break;
                            }
                        }
                    }
                }
            }

            // Merge counters for all units in chain
            $lineCounters = [];
            foreach ($chain as $cu) {
                if (!empty($counters[$cu->id])) {
                    foreach ($counters[$cu->id] as $counter) {
                        $lineCounters[$counter->id] = $counter;
                    }
                }
            }

            $lines[] = [
                'units' => $chain,
                'counters' => array_values($lineCounters),
            ];
        }

        return $lines;
    }

    /**
     * Build counter map: for each enemy unit, find your units that counter it.
     *
     * @return array [enemyUnit->id => [Unit, ...]]
     */
    private function buildCounterMap($yourUnits, $enemyUnits, &$counterBonuses = [])
    {
        $categoryMap = UnitController::getCounterCategoryMap();
        $categoryDefs = UnitController::getCategoryDefinitions();

        $result = [];
        $scores = []; // [enemyId][yourUnitId] => max bonus score

        // 1. Match via strong_against field
        foreach ($yourUnits as $yu) {
            $strongAgainst = $yu->strong_against ? json_decode($yu->strong_against, true) : [];
            if (!$strongAgainst) continue;

            foreach ($strongAgainst as $entry) {
                $key = strtolower(trim($entry));
                $this->matchCounter($key, $yu, $enemyUnits, $categoryMap, $categoryDefs, $result, $scores, 0, '', $counterBonuses);
            }
        }

        // 2. Match via unit_attack_bonus table
        foreach ($yourUnits as $yu) {
            if (!$yu->attackBonuses) continue;
            foreach ($yu->attackBonuses as $ab) {
                $bonus = (int)$ab->bonus;
                if ($bonus <= 0) continue;
                // Skip building-only bonuses
                $skip = ['all buildings', 'building', 'standard buildings', 'stone defense', 'walls and gates', 'castles'];
                $key = strtolower(trim($ab->vs));
                if (in_array($key, $skip)) continue;
                $this->matchCounter($key, $yu, $enemyUnits, $categoryMap, $categoryDefs, $result, $scores, $bonus, $ab->vs, $counterBonuses);
            }
        }

        // Sort: units with higher attack bonus first
        foreach ($result as $eid => $units) {
            $unitArr = array_values($units);
            usort($unitArr, function ($a, $b) use ($eid, $scores) {
                $sa = $scores[$eid][$a->id] ?? 0;
                $sb = $scores[$eid][$b->id] ?? 0;
                return $sb - $sa;
            });
            $result[$eid] = $unitArr;
        }

        return $result;
    }

    /**
     * Try to match a counter key against enemy units and record results.
     */
    private function matchCounter($key, $yu, $enemyUnits, $categoryMap, $categoryDefs, &$result, &$scores, $bonus, $vsText = '', &$counterBonuses = [])
    {
        if (isset($categoryMap[$key])) {
            $catSlug = $categoryMap[$key];
            $catDef = $categoryDefs[$catSlug] ?? null;
            if (!$catDef) return;

            foreach ($enemyUnits as $eu) {
                if ($this->unitMatchesCategory($eu, $catDef)) {
                    $result[$eu->id][$yu->id] = $yu;
                    $scores[$eu->id][$yu->id] = max($scores[$eu->id][$yu->id] ?? 0, $bonus);
                    if ($bonus > 0 && $vsText) {
                        if (!isset($counterBonuses[$eu->id][$yu->id]) || $counterBonuses[$eu->id][$yu->id]['bonus'] < $bonus) {
                            $counterBonuses[$eu->id][$yu->id] = ['vs' => $vsText, 'bonus' => $bonus];
                        }
                    }
                }
            }
        } else {
            foreach ($enemyUnits as $eu) {
                if ($this->nameMatches($key, strtolower($eu->name))) {
                    $result[$eu->id][$yu->id] = $yu;
                    $scores[$eu->id][$yu->id] = max($scores[$eu->id][$yu->id] ?? 0, $bonus);
                    if ($bonus > 0 && $vsText) {
                        if (!isset($counterBonuses[$eu->id][$yu->id]) || $counterBonuses[$eu->id][$yu->id]['bonus'] < $bonus) {
                            $counterBonuses[$eu->id][$yu->id] = ['vs' => $vsText, 'bonus' => $bonus];
                        }
                    }
                }
            }
        }
    }

    /**
     * Check if a unit matches a category definition.
     */
    private function unitMatchesCategory($unit, $catDef)
    {
        if (isset($catDef['armorClassNames'])) {
            $unitAC = array_map(function ($ac) { return $ac->name; }, $unit->armorClasses);
            return !empty(array_intersect($unitAC, $catDef['armorClassNames']));
        }
        if (isset($catDef['names'])) {
            return in_array($unit->name, $catDef['names']);
        }
        if (isset($catDef['like'])) {
            return stripos($unit->name, $catDef['like']) !== false;
        }
        return false;
    }

    /**
     * Fuzzy name match: exact, plural -s, plural -men→-man.
     */
    private function nameMatches($counterKey, $unitNameLower)
    {
        if ($counterKey === $unitNameLower) return true;
        if (substr($counterKey, -1) === 's' && substr($counterKey, 0, -1) === $unitNameLower) return true;
        if (substr($counterKey, -3) === 'men' && substr($counterKey, 0, -3) . 'man' === $unitNameLower) return true;
        return false;
    }

    /**
     * Find your units that are effective against buildings.
     * Sorted by building attack bonus descending.
     *
     * @return array ['units' => [Unit, ...], 'bonuses' => [unitId => ['vs' => ..., 'bonus' => ...]]]
     */
    private function buildBuildingCounters($yourUnits)
    {
        $buildingVs = ['all buildings', 'standard buildings', 'building'];
        $result = [];
        $bonuses = [];

        foreach ($yourUnits as $yu) {
            // Check strong_against for "buildings"
            $strongAgainst = $yu->strong_against ? json_decode($yu->strong_against, true) : [];
            $fromStrong = false;
            foreach ($strongAgainst as $entry) {
                if (strtolower(trim($entry)) === 'buildings') {
                    $fromStrong = true;
                    break;
                }
            }

            // Check attack bonuses vs buildings
            $maxBonus = 0;
            $bestVs = '';
            foreach ($yu->attackBonuses as $ab) {
                if ((int)$ab->bonus > 0 && in_array(strtolower(trim($ab->vs)), $buildingVs)) {
                    if ((int)$ab->bonus > $maxBonus) {
                        $maxBonus = (int)$ab->bonus;
                        $bestVs = $ab->vs;
                    }
                }
            }

            if ($fromStrong || $maxBonus >= 20) {
                $result[$yu->id] = $yu;
                if ($maxBonus > 0) {
                    $bonuses[$yu->id] = ['vs' => $bestVs, 'bonus' => $maxBonus];
                }
            }
        }

        // Sort by bonus descending, then alphabetically
        $unitArr = array_values($result);
        usort($unitArr, function ($a, $b) use ($bonuses) {
            $ba = isset($bonuses[$a->id]) ? $bonuses[$a->id]['bonus'] : 0;
            $bb = isset($bonuses[$b->id]) ? $bonuses[$b->id]['bonus'] : 0;
            if ($ba !== $bb) return $bb - $ba;
            return strcasecmp($a->name, $b->name);
        });

        return ['units' => $unitArr, 'bonuses' => $bonuses];
    }
}
