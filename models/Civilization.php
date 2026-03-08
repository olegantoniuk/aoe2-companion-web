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
}
