<?php

use yii\db\Migration;

/**
 * Adds unit.age_stats: combat stats that change with the age, one column per age.
 *
 * Format: {"columns": [{"age": "Feudal Age", "icon": "FeudalAge", "label": "Feudal Age"}, ...],
 *          "stats": {"hit_points": [50, 75, 85], ...}}
 * A stat missing from "stats" keeps the unit's own value in every column.
 * Filled for the Serjeant, which gets free upgrades in the Castle Age.
 */
class m261119_120000_add_unit_age_stats extends Migration
{
    public function safeUp()
    {
        $this->addColumn('unit', 'age_stats', $this->json()->after('elite_variant'));

        $this->update('unit', [
            'age_stats' => [
                'columns' => [
                    ['age' => 'Feudal Age', 'icon' => 'FeudalAge', 'label' => 'Feudal Age'],
                    ['age' => 'Castle Age', 'icon' => 'CastleAge', 'label' => 'Castle Age'],
                    ['age' => 'Imperial Age', 'icon' => 'ImperialAge', 'label' => 'Elite'],
                ],
                'stats' => [
                    'hit_points' => [50, 75, 85],
                    'melee_attack' => [5, 8, 11],
                    'melee_armor' => [2, 4, 6],
                    'pierce_armor' => [1, 2, 3],
                    'line_of_sight' => [5, 5, 7],
                ],
            ],
        ], ['slug' => 'serjeant']);
    }

    public function safeDown()
    {
        $this->dropColumn('unit', 'age_stats');
    }
}
