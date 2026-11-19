<?php

use yii\db\Migration;

/**
 * Knowledge base tables: build orders, guide articles and buildings, plus the building and age of each technology.
 * Lists (steps, sources, videos, civilizations) are JSON text; the models decode them.
 */
class m261119_220000_create_knowledge_base extends Migration
{
    public function safeUp()
    {
        $opts = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%build_order}}', [
            'id' => $this->primaryKey()->unsigned(),
            'slug' => $this->string(120)->notNull()->unique(),
            'title' => $this->string(200)->notNull(),
            'strategy' => $this->string(100),
            'category' => $this->string(40),
            'elo' => $this->string(20),
            'difficulty' => $this->tinyInteger()->unsigned(),
            'map_types' => $this->text(),
            'civs' => $this->text(),
            'summary' => $this->text(),
            'description' => $this->text(),
            'steps' => $this->text(),
            'after_feudal' => $this->text(),
            'tips' => $this->text(),
            'beaten_by' => $this->text(),
            'sources' => $this->text(),
            'videos' => $this->text(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $opts);
        $this->createIndex('idx_build_order_elo', '{{%build_order}}', 'elo');

        $this->createTable('{{%article}}', [
            'id' => $this->primaryKey()->unsigned(),
            'slug' => $this->string(120)->notNull()->unique(),
            'title' => $this->string(200)->notNull(),
            'category' => $this->string(40),
            'elo' => $this->string(20),
            'summary' => $this->text(),
            'body' => 'MEDIUMTEXT',
            'reading_minutes' => $this->smallInteger()->unsigned(),
            'related_units' => $this->text(),
            'sources' => $this->text(),
            'videos' => $this->text(),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $opts);
        $this->createIndex('idx_article_elo', '{{%article}}', 'elo');
        $this->createIndex('idx_article_category', '{{%article}}', 'category');

        $this->createTable('{{%building}}', [
            'id' => $this->primaryKey()->unsigned(),
            'slug' => $this->string(120)->notNull()->unique(),
            'name' => $this->string(120)->notNull(),
            'age' => $this->string(40),
            'category' => $this->string(40),
            'cost_food' => $this->integer(),
            'cost_wood' => $this->integer(),
            'cost_gold' => $this->integer(),
            'cost_stone' => $this->integer(),
            'build_time' => $this->integer(),
            'hit_points' => $this->integer(),
            'melee_armor' => $this->integer(),
            'pierce_armor' => $this->integer(),
            'line_of_sight' => $this->integer(),
            'garrison' => $this->integer(),
            'attack' => $this->string(40),
            'range_val' => $this->string(40),
            'size' => $this->string(20),
            'civs_only' => $this->text(),
            'civs_without' => $this->text(),
            'replaces' => $this->string(120),
            'requires' => $this->string(255),
            'trains' => $this->text(),
            'researches' => $this->text(),
            'icon' => $this->string(120),
            'description' => $this->text(),
        ], $opts);

        $this->addColumn('{{%technology}}', 'building', $this->string(120)->null());
        $this->addColumn('{{%technology}}', 'age', $this->string(40)->null());
    }

    public function safeDown()
    {
        $this->dropColumn('{{%technology}}', 'age');
        $this->dropColumn('{{%technology}}', 'building');
        $this->dropTable('{{%building}}');
        $this->dropTable('{{%article}}');
        $this->dropTable('{{%build_order}}');
    }
}
