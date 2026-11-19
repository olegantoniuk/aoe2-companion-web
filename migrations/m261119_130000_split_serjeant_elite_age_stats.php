<?php

use yii\db\Migration;

/**
 * The Imperial column of the Serjeant now holds the regular Serjeant; the Elite Serjeant
 * is shown in its own row from elite_variant. Column labels drop the word "Age".
 */
class m261119_130000_split_serjeant_elite_age_stats extends Migration
{
    public function safeUp()
    {
        $this->update('unit', [
            'age_stats' => [
                'columns' => [
                    ['age' => 'Feudal Age', 'icon' => 'FeudalAge', 'label' => 'Feudal'],
                    ['age' => 'Castle Age', 'icon' => 'CastleAge', 'label' => 'Castle'],
                    ['age' => 'Imperial Age', 'icon' => 'ImperialAge', 'label' => 'Imperial'],
                ],
                'elite_age' => 'Imperial Age',
                'stats' => [
                    'hit_points' => [50, 75, 75],
                    'melee_attack' => [5, 8, 8],
                    'melee_armor' => [2, 4, 4],
                    'pierce_armor' => [1, 2, 2],
                    'line_of_sight' => [5, 5, 5],
                ],
            ],
        ], ['slug' => 'serjeant']);
    }

    public function safeDown()
    {
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
}
