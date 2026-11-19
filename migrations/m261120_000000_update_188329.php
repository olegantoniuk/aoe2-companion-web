<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Brings the data up to the current game (Update 188329, October 2026):
 * - the Viking Sagas civilizations (Danes, Saxons, Varangians) with bonuses, unique units and technologies;
 * - the naval rework units (Hulk, War Hulk, Carrack, Catapult Galleon) and the Mounted Crossbowman line;
 * - balance changes to existing units, renamed technologies and the new availability of units per civilization.
 * Data: data/update_188329.json, built from the game data and the wiki.
 */
class m261120_000000_update_188329 extends Migration
{
    private $civIds = [];
    private $unitIds = [];
    private $techIds = [];

    private static function enc($v)
    {
        return $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function reloadIds()
    {
        $this->civIds = (new Query())->select(['id', 'name'])->from('{{%civilization}}')->indexBy('name')->column();
        $this->unitIds = (new Query())->select(['id', 'name'])->from('{{%unit}}')->indexBy('name')->column();
        $this->techIds = (new Query())->select(['id', 'name'])->from('{{%technology}}')->indexBy('name')->column();
    }

    private function insertUnit(array $u)
    {
        $cost = $u['cost'] ?? [];
        $up = $u['upgrade_cost'] ?? [];
        $ev = $u['elite_variant'] ?? null;
        $this->insert('{{%unit}}', [
            'name' => $u['name'],
            'slug' => $u['slug'],
            'page_title' => $u['name'],
            'is_unique' => (int)($u['is_unique'] ?? 0),
            'age' => $u['age'] ?? null,
            'training_building' => $u['training_building'] ?? null,
            'civilization_id' => !empty($u['civilization']) ? ($this->civIds[$u['civilization']] ?? null) : null,
            'cost_food' => $cost['food'] ?? null ?: null,
            'cost_wood' => $cost['wood'] ?? null ?: null,
            'cost_gold' => $cost['gold'] ?? null ?: null,
            'cost_stone' => $cost['stone'] ?? null ?: null,
            'training_time' => $u['training_time'] ?? null,
            'hit_points' => $u['hit_points'] ?? null,
            'melee_attack' => $u['melee_attack'] ?? null,
            'pierce_attack' => $u['pierce_attack'] ?? null,
            'blast_radius' => $u['blast_radius'] ?? null,
            'rate_of_fire' => $u['rate_of_fire'] ?? null,
            'attack_delay' => $u['attack_delay'] ?? null,
            'frame_delay' => $u['frame_delay'] ?? null,
            'range_val' => $u['range_val'] ?? null,
            'min_range' => $u['min_range'] ?? null,
            'accuracy' => $u['accuracy'] ?? null,
            'melee_armor' => $u['melee_armor'] ?? null,
            'pierce_armor' => $u['pierce_armor'] ?? null,
            'speed' => $u['speed'] ?? null,
            'line_of_sight' => $u['line_of_sight'] ?? null,
            'garrison_capacity' => $u['garrison_capacity'] ?? null,
            'abilities' => !empty($u['abilities']) ? self::enc($u['abilities']) : null,
            'description' => $u['description'] ?? null,
            'tactics' => $u['tactics'] ?? null,
            'upgrades_from' => $u['upgrades_from'] ?? null,
            'upgrades_to' => $u['upgrades_to'] ?? ($ev['name'] ?? null),
            'upgrade_cost_food' => $up['food'] ?? null ?: null,
            'upgrade_cost_wood' => $up['wood'] ?? null ?: null,
            'upgrade_cost_gold' => $up['gold'] ?? null ?: null,
            'upgrade_cost_stone' => $up['stone'] ?? null ?: null,
            'upgrade_time' => $u['upgrade_time'] ?? null,
            // JSON columns: Yii encodes the value once more, which gives the JSON string holding JSON
            // that the older rows have
            'elite_variant' => $ev ? self::enc($ev) : null,
            'image_icon' => !empty($u['icon_file']) ? 'units/images/' . $u['icon_file'] : null,
            'strong_against' => !empty($u['strong_against']) ? self::enc($u['strong_against']) : null,
            'weak_against' => !empty($u['weak_against']) ? self::enc($u['weak_against']) : null,
            'other_forms' => !empty($u['other_forms']) ? self::enc($u['other_forms']) : null,
        ]);
        $id = (int)$this->db->getLastInsertID();
        foreach ($u['armor_classes'] ?? [] as $ac) {
            $this->insert('{{%unit_armor_class}}', ['unit_id' => $id, 'name' => $ac['name'], 'modifier' => $ac['modifier'] ?? null]);
        }
        foreach ($u['attack_bonuses'] ?? [] as $b) {
            $this->insert('{{%unit_attack_bonus}}', ['unit_id' => $id, 'bonus' => $b['bonus'], 'vs' => $b['vs'], 'variant' => $b['variant'] ?? null]);
        }
        return $id;
    }

    private function setAvailability($unitId, $civId, $available)
    {
        $exists = (new Query())->from('{{%unit_availability}}')->where(['unit_id' => $unitId, 'civilization_id' => $civId])->exists();
        if ($exists) {
            $this->update('{{%unit_availability}}', ['available' => (int)$available], ['unit_id' => $unitId, 'civilization_id' => $civId]);
        } else {
            $this->insert('{{%unit_availability}}', ['unit_id' => $unitId, 'civilization_id' => $civId, 'available' => (int)$available]);
        }
    }

    private function editEliteVariant($unitId, callable $fn)
    {
        $raw = (new Query())->select('elite_variant')->from('{{%unit}}')->where(['id' => $unitId])->scalar();
        $ev = is_string($raw) ? json_decode($raw, true) : null;
        if (is_string($ev)) $ev = json_decode($ev, true);
        if (!is_array($ev)) return;
        $ev = $fn($ev);
        $this->update('{{%unit}}', ['elite_variant' => self::enc($ev)], ['id' => $unitId]);
    }

    public function safeUp()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/data/update_188329.json'), true);

        // 1. civilizations, their bonuses and unique technologies
        foreach ($data['civilizations'] as $c) {
            $this->insert('{{%civilization}}', [
                'name' => $c['name'],
                'slug' => $c['slug'],
                'page_title' => $c['page_title'] ?? $c['name'],
                'architecture_set' => $c['architecture_set'] ?? null,
                'continent' => $c['continent'] ?? null,
                'focus' => $c['focus'] ?? null,
                'summary' => $c['summary'] ?? null,
                'intro' => $c['intro'] ?? null,
                'team_bonus' => $c['team_bonus'] ?? null,
                'image_emblem' => !empty($c['emblem_file']) ? 'civilizations/images/' . $c['emblem_file'] : null,
            ]);
            $civId = (int)$this->db->getLastInsertID();
            foreach ($c['bonuses'] ?? [] as $i => $b) {
                $this->insert('{{%civilization_bonus}}', ['civilization_id' => $civId, 'bonus_text' => $b, 'sort_order' => $i + 1]);
            }
            foreach ($c['unique_techs'] ?? [] as $t) {
                $this->insert('{{%civilization_technology}}', ['civilization_id' => $civId, 'name' => $t['name'], 'description' => $t['description'] ?? null]);
            }
        }
        foreach ($data['civ_fixes'] as $f) {
            $name = $f['name'];
            unset($f['name']);
            $this->update('{{%civilization}}', $f, ['name' => $name]);
        }
        $this->reloadIds();

        // 2. technologies of the new civilizations
        foreach ($data['civ_techs'] as $t) {
            $cost = $t['cost'] ?? [];
            $this->insert('{{%technology}}', [
                'game_id' => $t['game_id'],
                'name' => $t['name'],
                'description' => $t['description'] ?? null,
                'cost_food' => $cost['food'] ?? null ?: null,
                'cost_wood' => $cost['wood'] ?? null ?: null,
                'cost_gold' => $cost['gold'] ?? null ?: null,
                'cost_stone' => $cost['stone'] ?? null ?: null,
                'research_time' => $t['research_time'] ?? null,
                'civilization_id' => !empty($t['civilization']) ? ($this->civIds[$t['civilization']] ?? null) : null,
                'building' => $t['building'] ?? null,
                'age' => $t['age'] ?? null,
            ]);
        }

        // 3. technologies renamed (stray hyphens in the old names)
        foreach ($data['changes'] as $ch) {
            if ($ch['type'] === 'technology' && $ch['field'] === 'renamed') {
                $this->update('{{%technology}}', ['name' => $ch['new']], ['name' => $ch['name']]);
                $this->update('{{%civilization_technology}}', ['name' => $ch['new']], ['name' => $ch['name']]);
            }
        }

        // 4. new units
        foreach (array_merge($data['civ_units'], $data['shared_units']) as $u) {
            $this->insertUnit($u);
        }
        $this->reloadIds();

        // 5. balance changes to existing units
        foreach ($data['changes'] as $ch) {
            if ($ch['type'] !== 'unit' || !isset($this->unitIds[$ch['name']])) continue;
            $uid = $this->unitIds[$ch['name']];
            $field = $ch['field'];
            if (preg_match('/^(elite_variant\.)?attack_bonus vs (.+)$/', $field, $m)) {
                $vs = $m[2];
                if ($m[1]) {
                    $new = $ch['new'];
                    $this->editEliteVariant($uid, function ($ev) use ($vs, $new) {
                        $list = array_values(array_filter($ev['attack_bonuses'] ?? [], function ($b) use ($vs) { return $b['vs'] !== $vs; }));
                        if ($new !== null) $list[] = ['bonus' => $new, 'vs' => $vs, 'variant' => 'Elite'];
                        $ev['attack_bonuses'] = $list;
                        return $ev;
                    });
                } elseif ($ch['new'] === null) {
                    $this->delete('{{%unit_attack_bonus}}', ['unit_id' => $uid, 'vs' => $vs]);
                } elseif ((new Query())->from('{{%unit_attack_bonus}}')->where(['unit_id' => $uid, 'vs' => $vs])->exists()) {
                    $this->update('{{%unit_attack_bonus}}', ['bonus' => $ch['new']], ['unit_id' => $uid, 'vs' => $vs]);
                } else {
                    $this->insert('{{%unit_attack_bonus}}', ['unit_id' => $uid, 'bonus' => $ch['new'], 'vs' => $vs, 'variant' => null]);
                }
            } elseif (strpos($field, 'elite_variant.') === 0) {
                $key = substr($field, strlen('elite_variant.'));
                $new = $ch['new'];
                $this->editEliteVariant($uid, function ($ev) use ($key, $new) {
                    $ev[$key] = $new;
                    return $ev;
                });
            } elseif (in_array($field, ['training_time', 'speed', 'hit_points', 'melee_armor', 'pierce_armor', 'range_val',
                'line_of_sight', 'upgrade_cost_food', 'upgrade_cost_wood', 'upgrade_cost_gold', 'cost_food', 'cost_wood', 'cost_gold'], true)) {
                $this->update('{{%unit}}', [$field => $ch['new']], ['id' => $uid]);
            }
        }

        // 6. availability: the full lists of the new civilizations, then the changes for the others
        foreach ($data['civ_availability'] as $civ => $lists) {
            $civId = $this->civIds[$civ] ?? null;
            if (!$civId) continue;
            $has = array_flip($lists['units']);
            foreach ($this->unitIds as $name => $uid) {
                $u = (new Query())->select(['is_unique', 'civilization_id'])->from('{{%unit}}')->where(['id' => $uid])->one();
                // unique units of other civilizations get no row, like the existing data
                if ($u['is_unique'] && !isset($has[$name])) continue;
                $this->setAvailability($uid, $civId, isset($has[$name]));
            }
            foreach ($lists['techs'] as $t) {
                if (isset($this->techIds[$t])) {
                    $this->insert('{{%technology_availability}}', ['technology_id' => $this->techIds[$t], 'civilization_id' => $civId]);
                }
            }
        }
        foreach ($data['availability_changes'] as $a) {
            $civId = $this->civIds[$a['civilization']] ?? null;
            if (!$civId) continue;
            if ($a['kind'] === 'unit' && isset($this->unitIds[$a['name']])) {
                $this->setAvailability($this->unitIds[$a['name']], $civId, $a['available']);
            } elseif ($a['kind'] === 'technology' && isset($this->techIds[$a['name']])) {
                $cond = ['technology_id' => $this->techIds[$a['name']], 'civilization_id' => $civId];
                $this->delete('{{%technology_availability}}', $cond);
                if ($a['available']) $this->insert('{{%technology_availability}}', $cond);
            }
        }
    }

    public function safeDown()
    {
        echo "m261120_000000_update_188329 changes existing rows and cannot be reverted; restore the database backup instead.\n";
        return false;
    }
}
