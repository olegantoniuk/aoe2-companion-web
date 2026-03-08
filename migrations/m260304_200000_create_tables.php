<?php

use yii\db\Migration;

class m260304_200000_create_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        // === civilization ===
        $this->createTable('civilization', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(100)->notNull(),
            'slug' => $this->string(100)->notNull()->unique(),
            'page_title' => $this->string(200),
            'architecture_set' => $this->string(100),
            'continent' => $this->string(200),
            'focus' => $this->string(200),
            'team_bonus' => $this->text(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        // === civilization_bonus ===
        $this->createTable('civilization_bonus', [
            'id' => $this->primaryKey()->unsigned(),
            'civilization_id' => $this->integer()->unsigned()->notNull(),
            'bonus_text' => $this->text()->notNull(),
            'sort_order' => $this->smallInteger()->defaultValue(0),
        ], $tableOptions);
        $this->addForeignKey('fk_civ_bonus_civ', 'civilization_bonus', 'civilization_id', 'civilization', 'id', 'CASCADE', 'CASCADE');

        // === civilization_technology ===
        $this->createTable('civilization_technology', [
            'id' => $this->primaryKey()->unsigned(),
            'civilization_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(200)->notNull(),
            'description' => $this->text(),
        ], $tableOptions);
        $this->addForeignKey('fk_civ_tech_civ', 'civilization_technology', 'civilization_id', 'civilization', 'id', 'CASCADE', 'CASCADE');

        // === unit ===
        $this->createTable('unit', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(100)->notNull(),
            'slug' => $this->string(100)->notNull()->unique(),
            'page_title' => $this->string(200),
            'type' => $this->string(50),
            'is_unique' => $this->tinyInteger(1)->defaultValue(0),
            'age' => $this->string(50),
            'training_building' => $this->string(100),
            'civilization_id' => $this->integer()->unsigned(),
            // Cost
            'cost_food' => $this->integer(),
            'cost_wood' => $this->integer(),
            'cost_gold' => $this->integer(),
            'cost_stone' => $this->integer(),
            'training_time' => $this->float(),
            // Combat
            'hit_points' => $this->integer(),
            'melee_attack' => $this->integer(),
            'pierce_attack' => $this->integer(),
            'blast_radius' => $this->float(),
            'rate_of_fire' => $this->float(),
            'attack_delay' => $this->float(),
            'frame_delay' => $this->integer(),
            'range_val' => $this->float(),
            'min_range' => $this->float(),
            'accuracy' => $this->integer(),
            'projectile_speed' => $this->float(),
            'projectile_count' => $this->integer(),
            // Defense
            'melee_armor' => $this->integer(),
            'pierce_armor' => $this->integer(),
            // Movement
            'speed' => $this->float(),
            'line_of_sight' => $this->integer(),
            'collision_size' => $this->float(),
            // Garrison
            'garrison_capacity' => $this->integer(),
            'garrison_type' => $this->string(100),
            // Special
            'abilities' => $this->json(),
            'regeneration' => $this->string(100),
            'conversion_resistance' => $this->string(100),
            'train_limit' => $this->integer(),
            'notes' => $this->text(),
            // Upgrade path
            'upgrades_to' => $this->string(100),
            'upgrade_cost_food' => $this->integer(),
            'upgrade_cost_wood' => $this->integer(),
            'upgrade_cost_gold' => $this->integer(),
            'upgrade_cost_stone' => $this->integer(),
            'upgrade_time' => $this->float(),
            'upgrades_from' => $this->string(100),
            // Elite variant
            'elite_variant' => $this->json(),
            // Images
            'image_icon' => $this->string(255),
            'image_sprite' => $this->string(255),
            // Counters
            'strong_against' => $this->json(),
            'weak_against' => $this->json(),
            // Other
            'other_forms' => $this->json(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);
        $this->addForeignKey('fk_unit_civ', 'unit', 'civilization_id', 'civilization', 'id', 'SET NULL', 'CASCADE');

        // === unit_attack_bonus ===
        $this->createTable('unit_attack_bonus', [
            'id' => $this->primaryKey()->unsigned(),
            'unit_id' => $this->integer()->unsigned()->notNull(),
            'bonus' => $this->integer()->notNull(),
            'vs' => $this->string(100)->notNull(),
            'variant' => $this->string(50),
        ], $tableOptions);
        $this->addForeignKey('fk_atk_bonus_unit', 'unit_attack_bonus', 'unit_id', 'unit', 'id', 'CASCADE', 'CASCADE');

        // === unit_armor_class ===
        $this->createTable('unit_armor_class', [
            'id' => $this->primaryKey()->unsigned(),
            'unit_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(100)->notNull(),
            'modifier' => $this->integer(),
        ], $tableOptions);
        $this->addForeignKey('fk_armor_class_unit', 'unit_armor_class', 'unit_id', 'unit', 'id', 'CASCADE', 'CASCADE');

        // === unit_boost ===
        $this->createTable('unit_boost', [
            'id' => $this->primaryKey()->unsigned(),
            'unit_id' => $this->integer()->unsigned()->notNull(),
            'boost_type' => "ENUM('technology','civilization','team') NOT NULL",
            'name' => $this->string(200)->notNull(),
            'stat' => $this->string(50),
            'effect' => $this->string(200),
        ], $tableOptions);
        $this->addForeignKey('fk_boost_unit', 'unit_boost', 'unit_id', 'unit', 'id', 'CASCADE', 'CASCADE');

        // === unit_availability ===
        $this->createTable('unit_availability', [
            'id' => $this->primaryKey()->unsigned(),
            'unit_id' => $this->integer()->unsigned()->notNull(),
            'civilization_id' => $this->integer()->unsigned()->notNull(),
            'available' => $this->tinyInteger(1)->defaultValue(1),
        ], $tableOptions);
        $this->addForeignKey('fk_avail_unit', 'unit_availability', 'unit_id', 'unit', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_avail_civ', 'unit_availability', 'civilization_id', 'civilization', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('idx_avail_unit_civ', 'unit_availability', ['unit_id', 'civilization_id'], true);

        // === user ===
        $this->createTable('user', [
            'id' => $this->primaryKey()->unsigned(),
            'username' => $this->string(100)->notNull()->unique(),
            'email' => $this->string(200),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'access_token' => $this->string(100),
            'role' => $this->string(20)->defaultValue('guest'),
            'status' => $this->tinyInteger()->defaultValue(10),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        // Insert admin user (password: admin123)
        $this->insert('user', [
            'username' => 'admin',
            'email' => 'admin@aoe2-companion.local',
            'password_hash' => Yii::$app->security->generatePasswordHash('admin123'),
            'auth_key' => Yii::$app->security->generateRandomString(),
            'role' => 'admin',
            'status' => 10,
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('user');
        $this->dropTable('unit_availability');
        $this->dropTable('unit_boost');
        $this->dropTable('unit_armor_class');
        $this->dropTable('unit_attack_bonus');
        $this->dropTable('unit');
        $this->dropTable('civilization_technology');
        $this->dropTable('civilization_bonus');
        $this->dropTable('civilization');
    }
}
