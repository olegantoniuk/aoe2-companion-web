<?php

use yii\db\Migration;

/**
 * Fills unit.description and unit.tactics for every unit from migrations/data/unit_texts.json
 * (keyed by slug). Written from the unit articles of the Age of Empires wiki.
 */
class m261119_160000_fill_unit_texts extends Migration
{
    private function texts()
    {
        return json_decode(file_get_contents(__DIR__ . '/data/unit_texts.json'), true);
    }

    public function safeUp()
    {
        foreach ($this->texts() as $slug => $t) {
            $this->update('unit', ['description' => $t['description'], 'tactics' => $t['tactics']], ['slug' => $slug]);
        }
    }

    public function safeDown()
    {
        foreach (array_keys($this->texts()) as $slug) {
            $this->update('unit', ['description' => null, 'tactics' => null], ['slug' => $slug]);
        }
    }
}
