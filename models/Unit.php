<?php

namespace app\models;

use yii\db\ActiveRecord;

class Unit extends ActiveRecord
{
    public static function tableName()
    {
        return 'unit';
    }

    public function getCivilization()
    {
        return $this->hasOne(Civilization::class, ['id' => 'civilization_id']);
    }

    public function getAttackBonuses()
    {
        return $this->hasMany(UnitAttackBonus::class, ['unit_id' => 'id']);
    }

    public function getArmorClasses()
    {
        return $this->hasMany(UnitArmorClass::class, ['unit_id' => 'id']);
    }

    public function getBoosts()
    {
        return $this->hasMany(UnitBoost::class, ['unit_id' => 'id']);
    }

    public function getTechnologyBoosts()
    {
        return $this->hasMany(UnitBoost::class, ['unit_id' => 'id'])
            ->andWhere(['boost_type' => 'technology']);
    }

    public function getCivilizationBoosts()
    {
        return $this->hasMany(UnitBoost::class, ['unit_id' => 'id'])
            ->andWhere(['boost_type' => 'civilization']);
    }

    public function getTeamBoosts()
    {
        return $this->hasMany(UnitBoost::class, ['unit_id' => 'id'])
            ->andWhere(['boost_type' => 'team']);
    }

    public function getAvailability()
    {
        return $this->hasMany(UnitAvailability::class, ['unit_id' => 'id']);
    }

    public function getAvailableCivs()
    {
        return $this->hasMany(Civilization::class, ['id' => 'civilization_id'])
            ->viaTable('unit_availability', ['unit_id' => 'id'], function ($query) {
                $query->andWhere(['available' => 1]);
            });
    }

    public function getCostString()
    {
        $parts = [];
        if ($this->cost_food) $parts[] = "{$this->cost_food} Food";
        if ($this->cost_wood) $parts[] = "{$this->cost_wood} Wood";
        if ($this->cost_gold) $parts[] = "{$this->cost_gold} Gold";
        if ($this->cost_stone) $parts[] = "{$this->cost_stone} Stone";
        return implode(', ', $parts) ?: 'Free';
    }

    public function getArmorString()
    {
        $m = $this->melee_armor ?? 0;
        $p = $this->pierce_armor ?? 0;
        return "{$m}/{$p}";
    }

    public function getIconUrl()
    {
        return $this->image_icon ? '/images/units/' . basename($this->image_icon) : null;
    }

    public function getSpriteUrl()
    {
        return $this->image_sprite ? '/images/units/' . basename($this->image_sprite) : null;
    }

    /**
     * Get armor class group for this unit (armor-class-based replacement for typeGroup).
     * Priority order determines which group is "primary".
     */
    public function getArmorClassGroup()
    {
        static $groupOrder = [
            'Infantry', 'Cavalry', 'Archers', 'Cavalry Archers',
            'Siege Weapons', 'Ships', 'Ship', 'Long-range warship',
            'Monks', 'Gunpowder Units',
        ];
        static $displayMap = [
            'Infantry' => 'Infantry',
            'Cavalry' => 'Cavalry',
            'Archers' => 'Archers',
            'Cavalry Archers' => 'Cav Archers',
            'Siege Weapons' => 'Siege',
            'Ships' => 'Naval',
            'Ship' => 'Naval',
            'Long-range warship' => 'Naval',
            'Monks' => 'Monks',
            'Gunpowder Units' => 'Gunpowder',
        ];
        if ($this->isRelationPopulated('armorClasses')) {
            $names = array_map(function ($ac) { return $ac->name; }, $this->armorClasses);
        } else {
            $names = [];
        }
        foreach ($groupOrder as $ac) {
            if (in_array($ac, $names)) {
                return $displayMap[$ac];
            }
        }
        return 'Other';
    }

    /**
     * Check if unit is naval (armor-class-based).
     */
    public function getIsNaval()
    {
        if ($this->isRelationPopulated('armorClasses')) {
            $names = array_map(function ($ac) { return $ac->name; }, $this->armorClasses);
            return !empty(array_intersect($names, ['Ship', 'Ships', 'Long-range warship']));
        }
        return false;
    }

    /**
     * Get display-worthy armor class names (excludes Unique Units / Unique unit).
     */
    public function getDisplayArmorClasses()
    {
        $exclude = ['Unique Units', 'Unique unit'];
        if ($this->isRelationPopulated('armorClasses')) {
            return array_filter($this->armorClasses, function ($ac) use ($exclude) {
                return !in_array($ac->name, $exclude);
            });
        }
        return [];
    }

    public function getBuildingGroup()
    {
        $b = $this->training_building ?? '';
        $known = ['Barracks', 'Stable', 'Archery Range', 'Siege Workshop', 'Castle', 'Dock', 'Monastery', 'Town Center', 'Market'];
        foreach ($known as $building) {
            if (strpos($b, $building) === 0) {
                return $building;
            }
        }
        return $b ?: 'Other';
    }
}
