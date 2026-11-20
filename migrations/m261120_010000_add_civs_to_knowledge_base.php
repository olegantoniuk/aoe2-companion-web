<?php

use yii\db\Migration;

/**
 * Guides and build orders can belong to civilizations (a guide for the Franks, a Mongol scout build),
 * so the lists can be filtered by civilization.
 */
class m261120_010000_add_civs_to_knowledge_base extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%article}}', 'civs', $this->text()->null()->after('related_units'));
        $this->addColumn('{{%build_order}}', 'civ_specific', $this->boolean()->notNull()->defaultValue(false)->after('civs'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%build_order}}', 'civ_specific');
        $this->dropColumn('{{%article}}', 'civs');
    }
}
