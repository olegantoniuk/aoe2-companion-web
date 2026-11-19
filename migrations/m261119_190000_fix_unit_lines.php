<?php

use yii\db\Migration;

/**
 * Cleans the upgrade links that the Combat Stats line table reads:
 * the Eagle Scout comes in the Feudal Age, and the Fire Ship upgrades to the Fast Fire Ship
 * (the Dragon Ship, Chinese only, points back to it through upgrades_from).
 */
class m261119_190000_fix_unit_lines extends Migration
{
    public function safeUp()
    {
        $this->update('unit', ['age' => 'Feudal Age'], ['slug' => 'eagle-scout']);
        $this->update('unit', ['upgrades_to' => 'Fast Fire Ship'], ['slug' => 'fire-ship']);
    }

    public function safeDown()
    {
        $this->update('unit', ['age' => 'Castle Age before the African Kingdoms'], ['slug' => 'eagle-scout']);
        $this->update('unit', ['upgrades_to' => 'Fast Fire ShipDragon Ship (Chinese only)'], ['slug' => 'fire-ship']);
    }
}
