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
        return isset(self::CATEGORIES[$this->category]) ? \Yii::t('app', self::CATEGORIES[$this->category]) : ucfirst((string)$this->category);
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

    /**
     * Title without the population count, for the cards that show "Click up at N pop" anyway:
     * "Maya Archers (22 pop)" -> "Maya Archers", "Archers (22 pop, two ranges)" -> "Archers (two ranges)",
     * "Fast Castle into Knights (28+2)" -> "Fast Castle into Knights". Other notes in brackets stay.
     * The title is translated first; a translation may write the population as "22 нас." or "22 населення".
     */
    public function getShortTitle()
    {
        $title = (string)\app\components\Tr::text($this->title, 'builds');
        $t = preg_replace_callback('/\s*\(([^)]*)\)/u', function ($m) {
            $rest = preg_replace('/^\d+(?:\+\d+)*(?:\s*(?:pop|нас\.|населення))?\s*(?:,\s*|$)/iu', '', $m[1]);
            if ($rest === $m[1]) return $m[0];
            $rest = preg_replace('/^(?:plus|плюс)\s+/iu', '', $rest);
            return $rest === '' ? '' : ' (' . $rest . ')';
        }, $title);
        return trim($t) ?: $title;
    }
}
