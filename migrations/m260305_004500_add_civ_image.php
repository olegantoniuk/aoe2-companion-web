<?php

use yii\db\Migration;

class m260305_004500_add_civ_image extends Migration
{
    public function safeUp()
    {
        $this->addColumn('civilization', 'image_emblem', $this->string(255)->after('team_bonus'));
    }

    public function safeDown()
    {
        $this->dropColumn('civilization', 'image_emblem');
    }
}
