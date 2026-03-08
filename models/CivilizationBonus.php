<?php

namespace app\models;

use yii\db\ActiveRecord;

class CivilizationBonus extends ActiveRecord
{
    public static function tableName()
    {
        return 'civilization_bonus';
    }

    public function getCivilization()
    {
        return $this->hasOne(Civilization::class, ['id' => 'civilization_id']);
    }
}
