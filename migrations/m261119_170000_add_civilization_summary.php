<?php

use yii\db\Migration;

/**
 * Adds civilization.summary: two or three sentences for the civilization list,
 * filled from migrations/data/civ_summaries.json (keyed by slug, written from the wiki articles).
 */
class m261119_170000_add_civilization_summary extends Migration
{
    public function safeUp()
    {
        $this->addColumn('civilization', 'summary', $this->text()->after('focus'));
        $texts = json_decode(file_get_contents(__DIR__ . '/data/civ_summaries.json'), true);
        foreach ($texts as $slug => $summary) {
            $this->update('civilization', ['summary' => $summary], ['slug' => $slug]);
        }
    }

    public function safeDown()
    {
        $this->dropColumn('civilization', 'summary');
    }
}
