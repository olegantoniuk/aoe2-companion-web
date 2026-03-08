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

    public function getTypeGroup()
    {
        $t = $this->type ?? '';
        if (strpos($t, 'Cavalry') !== false || strpos($t, 'Mounted archer') !== false || $t === 'Ranged cavalry') {
            return 'Cavalry';
        }
        if (strpos($t, 'Infantry') !== false) {
            return 'Infantry';
        }
        if (strpos($t, 'Foot archer') !== false || $t === 'ArcherGunpowder unit') {
            return 'Archer';
        }
        if (strpos($t, 'Siege weapon') !== false || strpos($t, 'Siege unit') !== false || $t === 'Cavalry siege unit') {
            return 'Siege';
        }
        if (strpos($t, 'Ship') !== false || strpos($t, 'ship') !== false || strpos($t, 'Siege ship') !== false || $t === 'Gunpowder siege ship') {
            return 'Naval';
        }
        if (strpos($t, 'Monk') !== false || strpos($t, 'Religious') !== false) {
            return 'Monk';
        }
        if ($t === 'Gunpowder unit' || $t === 'Mounted gunpowder unit') {
            return 'Gunpowder';
        }
        return 'Other';
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
