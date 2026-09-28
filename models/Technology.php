<?php

namespace app\models;

use yii\db\ActiveRecord;

class Technology extends ActiveRecord
{
    public static function tableName()
    {
        return 'technology';
    }

    /**
     * Owning civilization for unique technologies, null for shared ones.
     */
    public function getCivilization()
    {
        return $this->hasOne(Civilization::class, ['id' => 'civilization_id']);
    }

    public function getAvailableCivs()
    {
        return $this->hasMany(Civilization::class, ['id' => 'civilization_id'])
            ->viaTable('technology_availability', ['technology_id' => 'id']);
    }
}
