<?php

use yii\db\Migration;

/**
 * Fixes missing unit data for Maya and Inca civilizations.
 *
 * Root cause: unit JSON files use "Mayans"/"Incas" but civilization records
 * store "Maya"/"Inca", so the import skipped these two civilizations.
 */
class m260324_120000_fix_maya_inca_availability extends Migration
{
    public function safeUp()
    {
        // Fix unique unit assignments
        $mayaId = $this->db->createCommand(
            'SELECT id FROM civilization WHERE name = :name', [':name' => 'Maya']
        )->queryScalar();

        $incaId = $this->db->createCommand(
            'SELECT id FROM civilization WHERE name = :name', [':name' => 'Inca']
        )->queryScalar();

        if (!$mayaId || !$incaId) {
            echo "Maya or Inca civilization not found, skipping.\n";
            return;
        }

        // Assign unique units to their civilizations
        $this->update('unit', ['civilization_id' => $mayaId], ['slug' => 'plumed-archer', 'is_unique' => 1]);
        $this->update('unit', ['civilization_id' => $incaId], ['slug' => 'kamayuk', 'is_unique' => 1]);

        // Insert availability records from parsed data
        $availability = $this->getAvailabilityData();

        foreach ($availability as $civName => $civId) {
            foreach ($availability[$civName] as $unitSlug => $available) {
                $unitId = $this->db->createCommand(
                    'SELECT id FROM unit WHERE slug = :slug', [':slug' => $unitSlug]
                )->queryScalar();

                if (!$unitId) {
                    continue;
                }

                // Skip if already exists
                $exists = $this->db->createCommand(
                    'SELECT COUNT(*) FROM unit_availability WHERE unit_id = :uid AND civilization_id = :cid',
                    [':uid' => $unitId, ':cid' => $civId]
                )->queryScalar();

                if (!$exists) {
                    $this->insert('unit_availability', [
                        'unit_id' => $unitId,
                        'civilization_id' => $civId,
                        'available' => $available,
                    ]);
                }
            }
        }
    }

    public function safeDown()
    {
        $mayaId = $this->db->createCommand(
            'SELECT id FROM civilization WHERE name = :name', [':name' => 'Maya']
        )->queryScalar();

        $incaId = $this->db->createCommand(
            'SELECT id FROM civilization WHERE name = :name', [':name' => 'Inca']
        )->queryScalar();

        if ($mayaId) {
            $this->update('unit', ['civilization_id' => null], ['slug' => 'plumed-archer', 'is_unique' => 1]);
            $this->delete('unit_availability', ['civilization_id' => $mayaId]);
        }

        if ($incaId) {
            $this->update('unit', ['civilization_id' => null], ['slug' => 'kamayuk', 'is_unique' => 1]);
            $this->delete('unit_availability', ['civilization_id' => $incaId]);
        }
    }

    private function getAvailabilityData()
    {
        $mayaId = $this->db->createCommand(
            'SELECT id FROM civilization WHERE name = :name', [':name' => 'Maya']
        )->queryScalar();

        $incaId = $this->db->createCommand(
            'SELECT id FROM civilization WHERE name = :name', [':name' => 'Inca']
        )->queryScalar();

        // Unit slug => available (1/0) — extracted from parsed JSON data
        return [
            $mayaId => [
                'arbalester' => 1, 'archer' => 1, 'armored-elephant' => 0,
                'battering-ram' => 1, 'battle-elephant' => 0, 'bombard-cannon' => 0,
                'camel-rider' => 0, 'cannon-galleon' => 0, 'capped-ram' => 1,
                'cavalier' => 0, 'cavalry-archer' => 0, 'champion' => 0,
                'condottiero' => 0, 'crossbowman' => 1, 'demolition-raft' => 1,
                'demolition-ship' => 1, 'dromon' => 0, 'eagle-scout' => 1,
                'eagle-warrior' => 1, 'elephant-archer' => 0, 'elite-battle-elephant' => 0,
                'elite-cannon-galleon' => 0, 'elite-eagle-warrior' => 1,
                'elite-elephant-archer' => 0, 'elite-skirmisher' => 1,
                'elite-steppe-lancer' => 0, 'fast-fire-ship' => 1, 'fire-galley' => 1,
                'fire-ship' => 1, 'fishing-ship' => 1, 'flemish-militia' => 0,
                'galleon' => 1, 'galley' => 1, 'genitour' => 0, 'halberdier' => 1,
                'hand-cannoneer' => 0, 'heavy-camel-rider' => 0,
                'heavy-cavalry-archer' => 0, 'heavy-demolition-ship' => 1,
                'heavy-scorpion' => 1, 'hussar' => 0, 'imperial-camel-rider' => 0,
                'imperial-skirmisher' => 0, 'king' => 1, 'knight' => 0,
                'light-cavalry' => 0, 'long-swordsman' => 1, 'man-at-arms' => 1,
                'mangonel' => 1, 'militia' => 1, 'missionary' => 0, 'monk' => 1,
                'onager' => 1, 'paladin' => 0, 'petard' => 1, 'pikeman' => 1,
                'scorpion' => 1, 'scout-cavalry' => 0, 'siege-elephant' => 0,
                'siege-onager' => 0, 'siege-ram' => 1, 'siege-tower' => 1,
                'skirmisher' => 1, 'slinger' => 0, 'spearman' => 1,
                'steppe-lancer' => 0, 'trade-cart' => 1, 'trade-cog' => 1,
                'transport-ship' => 1, 'trebuchet' => 1, 'two-handed-swordsman' => 1,
                'villager' => 1, 'war-galley' => 1, 'warrior-priest' => 0,
                'winged-hussar' => 0, 'xolotl-warrior' => 0,
            ],
            $incaId => [
                'arbalester' => 1, 'archer' => 1, 'armored-elephant' => 0,
                'battering-ram' => 1, 'battle-elephant' => 0, 'bombard-cannon' => 0,
                'camel-rider' => 0, 'cannon-galleon' => 0, 'capped-ram' => 1,
                'cavalier' => 0, 'cavalry-archer' => 0, 'champion' => 0,
                'condottiero' => 0, 'crossbowman' => 1, 'demolition-raft' => 1,
                'demolition-ship' => 1, 'dromon' => 0, 'eagle-scout' => 0,
                'eagle-warrior' => 0, 'elephant-archer' => 0, 'elite-battle-elephant' => 0,
                'elite-cannon-galleon' => 0, 'elite-eagle-warrior' => 0,
                'elite-elephant-archer' => 0, 'elite-skirmisher' => 1,
                'elite-steppe-lancer' => 0, 'fast-fire-ship' => 1, 'fire-galley' => 1,
                'fire-ship' => 1, 'fishing-ship' => 1, 'flemish-militia' => 0,
                'galleon' => 1, 'galley' => 1, 'genitour' => 0, 'halberdier' => 1,
                'hand-cannoneer' => 0, 'heavy-camel-rider' => 0,
                'heavy-cavalry-archer' => 0, 'heavy-demolition-ship' => 0,
                'heavy-scorpion' => 1, 'hussar' => 0, 'imperial-camel-rider' => 0,
                'imperial-skirmisher' => 0, 'king' => 1, 'knight' => 0,
                'light-cavalry' => 0, 'long-swordsman' => 0, 'man-at-arms' => 0,
                'mangonel' => 1, 'militia' => 0, 'missionary' => 0, 'monk' => 1,
                'onager' => 1, 'paladin' => 0, 'petard' => 1, 'pikeman' => 1,
                'scorpion' => 1, 'scout-cavalry' => 0, 'siege-elephant' => 0,
                'siege-onager' => 0, 'siege-ram' => 1, 'siege-tower' => 1,
                'skirmisher' => 1, 'slinger' => 1, 'spearman' => 1,
                'steppe-lancer' => 0, 'trade-cart' => 1, 'trade-cog' => 1,
                'transport-ship' => 1, 'trebuchet' => 1, 'two-handed-swordsman' => 0,
                'villager' => 1, 'war-galley' => 1, 'warrior-priest' => 0,
                'winged-hussar' => 0, 'xolotl-warrior' => 0,
            ],
        ];
    }
}
