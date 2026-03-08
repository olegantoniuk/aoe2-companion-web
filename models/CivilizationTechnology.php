<?php

namespace app\models;

use yii\db\ActiveRecord;

class CivilizationTechnology extends ActiveRecord
{
    public static function tableName()
    {
        return 'civilization_technology';
    }

    public function getCivilization()
    {
        return $this->hasOne(Civilization::class, ['id' => 'civilization_id']);
    }
}
