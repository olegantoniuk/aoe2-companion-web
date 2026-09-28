<?php

use yii\db\Migration;

/**
 * Technologies with in-game cost and description, and which civilizations can research them.
 * Filled by `yii import/technologies`.
 */
class m260928_120000_create_technology_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('technology', [
            'id' => $this->primaryKey()->unsigned(),
            'game_id' => $this->integer()->notNull()->unique(),
            'name' => $this->string(200)->notNull()->unique(),
            'description' => $this->text(),
            'cost_food' => $this->integer(),
            'cost_wood' => $this->integer(),
            'cost_gold' => $this->integer(),
            'cost_stone' => $this->integer(),
            'research_time' => $this->integer(),
            // Set when only one civilization can research it (unique technology)
            'civilization_id' => $this->integer()->unsigned(),
        ], $tableOptions);
        $this->addForeignKey('fk_tech_civ', 'technology', 'civilization_id', 'civilization', 'id', 'SET NULL', 'CASCADE');

        $this->createTable('technology_availability', [
            'id' => $this->primaryKey()->unsigned(),
            'technology_id' => $this->integer()->unsigned()->notNull(),
            'civilization_id' => $this->integer()->unsigned()->notNull(),
        ], $tableOptions);
        $this->addForeignKey('fk_tech_avail_tech', 'technology_availability', 'technology_id', 'technology', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_tech_avail_civ', 'technology_availability', 'civilization_id', 'civilization', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx_tech_avail_tech_civ', 'technology_availability', ['technology_id', 'civilization_id'], true);
    }

    public function safeDown()
    {
        $this->dropTable('technology_availability');
        $this->dropTable('technology');
    }
}
