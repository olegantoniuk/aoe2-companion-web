<?php

use yii\db\Migration;

/**
 * Shorter unit descriptions that say what the unit is for and what makes it different,
 * instead of repeating the cost, the building and the stats already shown on the page.
 * The previous texts stay in data/unit_texts.json, so down() can put them back.
 */
class m261119_200000_rewrite_unit_descriptions extends Migration
{
    public function safeUp()
    {
        $texts = json_decode(file_get_contents(__DIR__ . '/data/unit_descriptions_v2.json'), true);
        foreach ($texts as $slug => $description) {
            $this->update('{{%unit}}', ['description' => $description], ['slug' => $slug]);
        }
    }

    public function safeDown()
    {
        $texts = json_decode(file_get_contents(__DIR__ . '/data/unit_texts.json'), true);
        foreach ($texts as $slug => $t) {
            if (!empty($t['description'])) {
                $this->update('{{%unit}}', ['description' => $t['description']], ['slug' => $slug]);
            }
        }
    }
}
