<?php

namespace app\models;

use yii\db\ActiveRecord;

class UnitBoost extends ActiveRecord
{
    public static function tableName()
    {
        return 'unit_boost';
    }
}
