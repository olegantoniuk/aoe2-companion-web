<?php

namespace app\models;

use yii\db\ActiveRecord;

class UnitAttackBonus extends ActiveRecord
{
    public static function tableName()
    {
        return 'unit_attack_bonus';
    }
}
