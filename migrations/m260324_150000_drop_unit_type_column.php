<?php

use yii\db\Migration;

/**
 * Removes the unit.type column — classification now uses armor classes
 * via the unit_armor_class table instead.
 */
class m260324_150000_drop_unit_type_column extends Migration
{
    public function safeUp()
    {
        $this->dropColumn('unit', 'type');
    }

    public function safeDown()
    {
        $this->addColumn('unit', 'type', $this->string(50)->after('slug'));
    }
}
