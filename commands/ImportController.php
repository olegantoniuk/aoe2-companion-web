<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class ImportController extends Controller
{
    public $dataDir;

    /**
     * Maps alternative civilization names (used in unit JSON files)
     * to canonical names (used in civilization JSON files).
     * E.g. wiki uses "Mayans" but the civ is stored as "Maya".
     */
    private $civAliases = [
        'Mayans' => 'Maya',
        'Incas' => 'Inca',
    ];

    public function init()
    {
        parent::init();
        $this->dataDir = dirname(Yii::getAlias('@app')) . '/data';
    }

    /**
     * Import all data: civilizations, units, and related tables.
     */
    public function actionAll()
    {
        $this->actionCivilizations();
        $this->actionUnits();
        $this->actionAvailability();
        $this->stdout("\nAll imports complete!\n", Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Import civilizations from JSON files.
     */
    public function actionCivilizations()
    {
        $this->stdout("=== Importing Civilizations ===\n", Console::FG_CYAN);
        $civDir = $this->dataDir . '/civilizations';
        $files = glob($civDir . '/*.json');
        $count = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || empty($data['name'])) {
                $this->stderr("Skipping invalid file: $file\n", Console::FG_RED);
                continue;
            }

            $slug = $this->slugify($data['name']);

            // Check if already exists
            $existing = Yii::$app->db->createCommand(
                'SELECT id FROM civilization WHERE slug = :slug', [':slug' => $slug]
            )->queryScalar();

            if ($existing) {
                continue;
            }

            Yii::$app->db->createCommand()->insert('civilization', [
                'name' => $data['name'],
                'slug' => $slug,
                'page_title' => $data['page_title'] ?? null,
                'architecture_set' => $data['architecture_set'] ?? null,
                'continent' => $data['continent'] ?? null,
                'focus' => $data['focus'] ?? null,
                'team_bonus' => $data['team_bonus'] ?? null,
            ])->execute();

            $civId = Yii::$app->db->getLastInsertID();

            // Civilization bonuses
            if (!empty($data['civilization_bonuses'])) {
                foreach ($data['civilization_bonuses'] as $i => $bonus) {
                    Yii::$app->db->createCommand()->insert('civilization_bonus', [
                        'civilization_id' => $civId,
                        'bonus_text' => $bonus,
                        'sort_order' => $i,
                    ])->execute();
                }
            }

            // Unique technologies
            if (!empty($data['unique_technologies'])) {
                foreach ($data['unique_technologies'] as $tech) {
                    Yii::$app->db->createCommand()->insert('civilization_technology', [
                        'civilization_id' => $civId,
                        'name' => $tech['name'],
                        'description' => $tech['description'] ?? null,
                    ])->execute();
                }
            }

            $count++;
        }

        $this->stdout("Imported $count civilizations\n", Console::FG_GREEN);
    }

    /**
     * Import units from JSON files.
     */
    public function actionUnits()
    {
        $this->stdout("=== Importing Units ===\n", Console::FG_CYAN);
        $unitDir = $this->dataDir . '/units';
        $files = glob($unitDir . '/*.json');
        $count = 0;

        // Build civ name → id map
        $civMap = [];
        $civRows = Yii::$app->db->createCommand('SELECT id, name FROM civilization')->queryAll();
        foreach ($civRows as $row) {
            $civMap[$row['name']] = $row['id'];
        }

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || empty($data['name'])) {
                $this->stderr("Skipping invalid file: $file\n", Console::FG_RED);
                continue;
            }

            $slug = $this->slugify($data['name']);

            // Check if already exists
            $existing = Yii::$app->db->createCommand(
                'SELECT id FROM unit WHERE slug = :slug', [':slug' => $slug]
            )->queryScalar();

            if ($existing) {
                continue;
            }

            // Resolve civilization_id for unique units
            $civId = null;
            if (!empty($data['civilization'])) {
                $civName = is_array($data['civilization']) ? $data['civilization'][0] : $data['civilization'];
                $civName = $this->resolveCivName($civName);
                $civId = $civMap[$civName] ?? null;
            }

            $cost = $data['cost'] ?? [];
            $upgradeCost = $data['upgrade_cost'] ?? [];

            Yii::$app->db->createCommand()->insert('unit', [
                'name' => $data['name'],
                'slug' => $slug,
                'page_title' => $data['page_title'] ?? null,
                'type' => $data['type'] ?? null,
                'is_unique' => $data['is_unique'] ? 1 : 0,
                'age' => $data['age'] ?? null,
                'training_building' => $data['training_building'] ?? null,
                'civilization_id' => $civId,
                'cost_food' => $cost['food'] ?? null,
                'cost_wood' => $cost['wood'] ?? null,
                'cost_gold' => $cost['gold'] ?? null,
                'cost_stone' => $cost['stone'] ?? null,
                'training_time' => $data['training_time'] ?? null,
                'hit_points' => $data['hit_points'] ?? null,
                'melee_attack' => $data['melee_attack'] ?? null,
                'pierce_attack' => $data['pierce_attack'] ?? null,
                'blast_radius' => $data['blast_radius'] ?? null,
                'rate_of_fire' => $data['rate_of_fire'] ?? null,
                'attack_delay' => $data['attack_delay'] ?? null,
                'frame_delay' => $data['frame_delay'] ?? null,
                'range_val' => $data['range'] ?? null,
                'min_range' => $data['min_range'] ?? null,
                'accuracy' => $data['accuracy'] ?? null,
                'projectile_speed' => $data['projectile_speed'] ?? null,
                'projectile_count' => $data['projectile_count'] ?? null,
                'melee_armor' => $data['melee_armor'] ?? null,
                'pierce_armor' => $data['pierce_armor'] ?? null,
                'speed' => $data['speed'] ?? null,
                'line_of_sight' => $data['line_of_sight'] ?? null,
                'collision_size' => $data['collision_size'] ?? null,
                'garrison_capacity' => $data['garrison_capacity'] ?? null,
                'garrison_type' => $data['garrison_type'] ?? null,
                'abilities' => !empty($data['abilities']) ? json_encode($data['abilities']) : null,
                'regeneration' => $data['regeneration'] ?? null,
                'conversion_resistance' => $data['conversion_resistance'] ?? null,
                'train_limit' => $data['train_limit'] ?? null,
                'notes' => $data['notes'] ?? null,
                'upgrades_to' => $data['upgrades_to'] ?? null,
                'upgrade_cost_food' => $upgradeCost['food'] ?? null,
                'upgrade_cost_wood' => $upgradeCost['wood'] ?? null,
                'upgrade_cost_gold' => $upgradeCost['gold'] ?? null,
                'upgrade_cost_stone' => $upgradeCost['stone'] ?? null,
                'upgrade_time' => $data['upgrade_time'] ?? null,
                'upgrades_from' => $data['upgrades_from'] ?? null,
                'elite_variant' => !empty($data['elite_variant']) ? json_encode($data['elite_variant']) : null,
                'image_icon' => $data['image_icon'] ?? null,
                'image_sprite' => $data['image_sprite'] ?? null,
                'strong_against' => !empty($data['strong_against']) ? json_encode($data['strong_against']) : null,
                'weak_against' => !empty($data['weak_against']) ? json_encode($data['weak_against']) : null,
                'other_forms' => !empty($data['other_forms']) ? json_encode($data['other_forms']) : null,
            ])->execute();

            $unitId = Yii::$app->db->getLastInsertID();

            // Attack bonuses
            if (!empty($data['attack_bonuses'])) {
                foreach ($data['attack_bonuses'] as $ab) {
                    Yii::$app->db->createCommand()->insert('unit_attack_bonus', [
                        'unit_id' => $unitId,
                        'bonus' => $ab['bonus'],
                        'vs' => $ab['vs'],
                        'variant' => $ab['variant'] ?? null,
                    ])->execute();
                }
            }

            // Armor classes
            if (!empty($data['armor_classes'])) {
                foreach ($data['armor_classes'] as $ac) {
                    Yii::$app->db->createCommand()->insert('unit_armor_class', [
                        'unit_id' => $unitId,
                        'name' => $ac['name'],
                        'modifier' => $ac['modifier'] ?? null,
                    ])->execute();
                }
            }

            // Technology boosts
            if (!empty($data['technology_boosts'])) {
                foreach ($data['technology_boosts'] as $b) {
                    Yii::$app->db->createCommand()->insert('unit_boost', [
                        'unit_id' => $unitId,
                        'boost_type' => 'technology',
                        'name' => $b['name'],
                        'stat' => $b['stat'] ?? null,
                        'effect' => $b['effect'] ?? null,
                    ])->execute();
                }
            }

            // Civilization boosts
            if (!empty($data['civilization_boosts'])) {
                foreach ($data['civilization_boosts'] as $b) {
                    Yii::$app->db->createCommand()->insert('unit_boost', [
                        'unit_id' => $unitId,
                        'boost_type' => 'civilization',
                        'name' => $b['name'],
                        'stat' => $b['stat'] ?? null,
                        'effect' => $b['effect'] ?? null,
                    ])->execute();
                }
            }

            // Team boosts
            if (!empty($data['team_boosts'])) {
                foreach ($data['team_boosts'] as $b) {
                    Yii::$app->db->createCommand()->insert('unit_boost', [
                        'unit_id' => $unitId,
                        'boost_type' => 'team',
                        'name' => $b['name'],
                        'stat' => $b['stat'] ?? null,
                        'effect' => $b['effect'] ?? null,
                    ])->execute();
                }
            }

            $count++;
        }

        $this->stdout("Imported $count units\n", Console::FG_GREEN);
    }

    /**
     * Import unit availability data.
     */
    public function actionAvailability()
    {
        $this->stdout("=== Importing Availability ===\n", Console::FG_CYAN);

        // Build maps
        $civMap = [];
        $civRows = Yii::$app->db->createCommand('SELECT id, name FROM civilization')->queryAll();
        foreach ($civRows as $row) {
            $civMap[$row['name']] = $row['id'];
        }

        $unitMap = [];
        $unitRows = Yii::$app->db->createCommand('SELECT id, slug FROM unit')->queryAll();
        foreach ($unitRows as $row) {
            $unitMap[$row['slug']] = $row['id'];
        }

        // Read from individual unit JSON files that have civilization_availability
        $unitDir = $this->dataDir . '/units';
        $files = glob($unitDir . '/*.json');
        $count = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (empty($data['civilization_availability'])) {
                continue;
            }

            $slug = $this->slugify($data['name']);
            $unitId = $unitMap[$slug] ?? null;
            if (!$unitId) {
                continue;
            }

            foreach ($data['civilization_availability'] as $civName => $value) {
                $resolvedName = $this->resolveCivName($civName);
                $civId = $civMap[$resolvedName] ?? null;
                if (!$civId) {
                    continue;
                }

                $available = ($value === '0') ? 0 : 1;

                try {
                    Yii::$app->db->createCommand()->insert('unit_availability', [
                        'unit_id' => $unitId,
                        'civilization_id' => $civId,
                        'available' => $available,
                    ])->execute();
                    $count++;
                } catch (\Exception $e) {
                    // Skip duplicates
                }
            }
        }

        $this->stdout("Imported $count availability records\n", Console::FG_GREEN);
    }

    /**
     * Update civilization emblem images from JSON data.
     */
    public function actionUpdateCivImages()
    {
        $this->stdout("=== Updating Civilization Images ===\n", Console::FG_CYAN);
        $civDir = $this->dataDir . '/civilizations';
        $files = glob($civDir . '/*.json');
        $count = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || empty($data['name'])) {
                continue;
            }

            $imageEmblem = $data['image_emblem'] ?? null;
            if (!$imageEmblem) {
                continue;
            }

            $slug = $this->slugify($data['name']);
            $updated = Yii::$app->db->createCommand()->update('civilization', [
                'image_emblem' => $imageEmblem,
            ], 'slug = :slug', [':slug' => $slug])->execute();

            if ($updated) {
                $count++;
            }
        }

        $this->stdout("Updated $count civilization images\n", Console::FG_GREEN);
    }

    /**
     * Update unit images (icon + sprite) from JSON data.
     */
    public function actionUpdateUnitImages()
    {
        $this->stdout("=== Updating Unit Images ===\n", Console::FG_CYAN);
        $unitDir = $this->dataDir . '/units';
        $files = glob($unitDir . '/*.json');
        $count = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || empty($data['name'])) {
                continue;
            }

            $imageIcon = $data['image_icon'] ?? null;
            $imageSprite = $data['image_sprite'] ?? null;
            if (!$imageIcon && !$imageSprite) {
                continue;
            }

            $slug = $this->slugify($data['name']);
            $updates = [];
            if ($imageIcon) {
                $updates['image_icon'] = $imageIcon;
            }
            if ($imageSprite) {
                $updates['image_sprite'] = $imageSprite;
            }

            $updated = Yii::$app->db->createCommand()->update('unit', $updates, 'slug = :slug', [':slug' => $slug])->execute();
            if ($updated) {
                $count++;
            }
        }

        $this->stdout("Updated $count unit images\n", Console::FG_GREEN);
    }

    /**
     * Resolve a civilization name to its canonical form using aliases.
     */
    private function resolveCivName($name)
    {
        return $this->civAliases[$name] ?? $name;
    }

    private function slugify($name)
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}
