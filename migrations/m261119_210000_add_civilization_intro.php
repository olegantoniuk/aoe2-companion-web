<?php

use yii\db\Migration;

/**
 * A short intro under the civilization name: the one or two things that make it stand out,
 * with the history behind them where there is one.
 */
class m261119_210000_add_civilization_intro extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%civilization}}', 'intro', $this->text()->null()->after('summary'));
        $texts = json_decode(file_get_contents(__DIR__ . '/data/civ_intros.json'), true);
        foreach ($texts as $slug => $intro) {
            $this->update('{{%civilization}}', ['intro' => $intro], ['slug' => $slug]);
        }
    }

    public function safeDown()
    {
        $this->dropColumn('{{%civilization}}', 'intro');
    }
}
