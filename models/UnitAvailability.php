<?php

namespace app\models;

use yii\db\ActiveRecord;

class UnitAvailability extends ActiveRecord
{
    public static function tableName()
    {
        return 'unit_availability';
    }

    public function getUnit()
    {
        return $this->hasOne(Unit::class, ['id' => 'unit_id']);
    }

    public function getCivilization()
    {
        return $this->hasOne(Civilization::class, ['id' => 'civilization_id']);
    }
}
