<?php

namespace app\models;

use yii\db\ActiveRecord;

class Civilization extends ActiveRecord
{
    public static function tableName()
    {
        return 'civilization';
    }

    public function getBonuses()
    {
        return $this->hasMany(CivilizationBonus::class, ['civilization_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getTechnologies()
    {
        return $this->hasMany(CivilizationTechnology::class, ['civilization_id' => 'id']);
    }

    public function getUniqueUnits()
    {
        return $this->hasMany(Unit::class, ['civilization_id' => 'id']);
    }

    public function getAvailableUnits()
    {
        return $this->hasMany(Unit::class, ['id' => 'unit_id'])
            ->viaTable('unit_availability', ['civilization_id' => 'id'], function ($query) {
                $query->andWhere(['available' => 1]);
            });
    }

    public function getUnavailableUnits()
    {
        return $this->hasMany(Unit::class, ['id' => 'unit_id'])
            ->viaTable('unit_availability', ['civilization_id' => 'id'], function ($query) {
                $query->andWhere(['available' => 0]);
            });
    }

    public function getEmblemUrl()
    {
        return $this->image_emblem ? '/images/civs/' . basename($this->image_emblem) : null;
    }

    /**
     * The focus list from the wiki came glued together ("InfantryNaval", "Gunpowder unitsMonks"):
     * split it where a lower case letter runs into a capital.
     */
    public function getFocusList()
    {
        if (!$this->focus) return [];
        return array_values(array_filter(array_map('trim', preg_split('/(?<=[a-z])(?=[A-Z])|,/', $this->focus))));
    }

    /**
     * Groups for the civilization list: by region, the Three Kingdoms states on their own.
     * Each is [title, note, civilization slugs, icon file under /images/regions/].
     */
    public static function regions()
    {
        return [
            ['Western Europe', 'Knights, castles and longbows. The Britons outshoot everyone, the Franks and Burgundians build the heaviest cavalry, and the Portuguese and Spanish take gunpowder out onto the ocean.',
                ['britons', 'burgundians', 'celts', 'franks', 'italians', 'portuguese', 'saxons', 'sicilians', 'spanish'], 'western-europe'],
            ['Central and Northern Europe', 'Raiders from the fjords and stubborn defenders of the German lands. Vikings, Danes and Goths flood the map with infantry, while Teutons and Bohemians dig in behind tough walls and Hussite Wagons.',
                ['bohemians', 'danes', 'goths', 'teutons', 'vikings'], 'northern-europe'],
            ['Eastern Europe', 'Caught between the steppe and the Holy Roman Empire, these armies lean on horses. Magyar Huszars raid, Polish and Lithuanian Winged Hussars charge, Bulgarian Konniks keep fighting on foot after their horse falls, and the Varangians bring the Norse who settled along the rivers of Kievan Rus.',
                ['bulgarians', 'lithuanians', 'magyars', 'poles', 'slavs', 'varangians'], 'eastern-europe'],
            ['Mediterranean and Caucasus', 'Rome and its heirs. Byzantines answer every army with cheap counter units and thick walls, Romans march in Legionary lines behind their Centurions, and Armenians and Georgians defend their mountains with heavy infantry and cavalry.',
                ['armenians', 'byzantines', 'georgians', 'romans'], 'mediterranean'],
            ['The Steppe', 'Horse peoples who live in the saddle and play fast. Huns never build houses, Mongols hunt and raid with Mangudai, Cumans can have a second Town Center in the Feudal Age, and Tatars shoot from the high ground.',
                ['cumans', 'huns', 'mongols', 'tatars'], 'steppe'],
            ['Middle East and Africa', 'Deserts, camels and gold. Saracens and Berbers ride camels into battle, the Turks field the strongest gunpowder army, Persians bring War Elephants, and Ethiopian Shotel Warriors hit hard and train fast.',
                ['berbers', 'ethiopians', 'malians', 'persians', 'saracens', 'turks'], 'middle-east-africa'],
            ['South Asia', 'Elephants and camels decide the fights here. Bengalis ride Rathas and elephants, Gurjaras build their army around camels, Hindustanis add the Imperial Camel Rider, and the Dravidians rule the coast with the Thirisadai.',
                ['bengalis', 'dravidians', 'gurjaras', 'hindustanis', 'indians'], 'south-asia'],
            ['East Asia', 'Repeating crossbows, war wagons and samurai. The Chinese fire Chu Ko Nu volleys, the Koreans hold with War Wagons and Turtle Ships, Japanese Samurai cut down other unique units, and Jurchens and Khitans ride in from beyond the Great Wall.',
                ['chinese', 'japanese', 'jurchens', 'khitans', 'koreans'], 'east-asia'],
            ['Three Kingdoms', 'Shu, Wei and Wu, the states that split China after the fall of the Han. Each has its own heroes and its own army: Shu War Chariots, Wei Tiger Cavalry and Wu Fire Archers.',
                ['shu', 'wei', 'wu'], 'three-kingdoms'],
            ['Southeast Asia', 'Jungle, rivers and war elephants. Khmer Ballista Elephants shoot through crowds, Burmese Arambai throw darts in volleys, Vietnamese see the enemy base from the first minute, and the Malay swarm with cheap Karambit Warriors.',
                ['burmese', 'khmer', 'malay', 'vietnamese'], 'southeast-asia'],
            ['The Americas', 'Most of these civilizations have no horses and use Eagle Warriors for the scouting and raiding that cavalry does elsewhere. The Aztecs, Maya and Inca are the original three; the Mapuche, Muisca and Tupi joined later with new units of their own.',
                ['aztecs', 'inca', 'maya', 'mapuche', 'muisca', 'tupi'], 'americas'],
        ];
    }

    /** URL of a region emblem, or null while the file is missing. */
    public static function regionIcon($file)
    {
        $path = '/images/regions/' . $file . '.png';
        return is_file(\Yii::getAlias('@webroot') . $path) ? $path : null;
    }
}
