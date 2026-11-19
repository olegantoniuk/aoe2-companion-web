<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * A build order: the step list from the first villager to the age up, with notes on when to use it.
 */
class BuildOrder extends ActiveRecord
{
    use JsonFields;

    const CATEGORIES = [
        'dark-age-rush' => 'Dark Age rush',
        'feudal-aggression' => 'Feudal aggression',
        'fast-castle' => 'Fast Castle',
        'boom' => 'Boom',
        'water' => 'Water',
        'defensive' => 'Defensive',
        'hybrid' => 'Hybrid',
    ];

    const STEP_TYPES = ['villager', 'build', 'research', 'age', 'military', 'note'];

    public static function tableName()
    {
        return 'build_order';
    }

    public function getCategoryLabel()
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string)$this->category);
    }

    /** Civilizations the build suits, as models, in the order the data lists them. */
    public function getCivModels()
    {
        $names = $this->listOf('civs');
        if (!$names) return [];
        $byName = [];
        foreach (Civilization::find()->where(['name' => $names])->all() as $c) {
            $byName[$c->name] = $c;
        }
        return array_values(array_filter(array_map(function ($n) use ($byName) { return $byName[$n] ?? null; }, $names)));
    }

    /** Population at the age up click, for the list page. */
    public function getAgeUpPop()
    {
        foreach ($this->listOf('steps') as $s) {
            if (($s['type'] ?? '') === 'age' && !empty($s['pop'])) return (int)$s['pop'];
        }
        return null;
    }
}
