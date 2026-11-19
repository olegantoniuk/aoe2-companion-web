<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Data fixes found while writing the civilization summaries:
 * - civilization bonuses and team bonuses rebuilt from the wiki articles (some were cut off or outdated),
 *   data in migrations/data/civ_bonuses.json;
 * - the Indians removed: the civilization is no longer in the game (split into Hindustanis, Gurjaras,
 *   Bengalis and Dravidians);
 * - unique units tied to their civilization: Genitour, Condottiero, Legionary, Savar, Imperial Camel Rider,
 *   Winged Hussar (Poles and Lithuanians, through availability);
 * - Longboat renamed Longship, as in the game since The Viking Sagas.
 *
 * Back up the database before running it; the down step cannot bring the old data back.
 */
class m261119_180000_fix_civ_and_unit_data extends Migration
{
    private function civId($slug)
    {
        return (new Query())->select('id')->from('civilization')->where(['slug' => $slug])->scalar();
    }

    public function safeUp()
    {
        // 1. bonuses
        $data = json_decode(file_get_contents(__DIR__ . '/data/civ_bonuses.json'), true);
        foreach ($data as $slug => $d) {
            $id = $this->civId($slug);
            if (!$id || !$d['bonuses']) continue;
            $this->delete('civilization_bonus', ['civilization_id' => $id]);
            foreach ($d['bonuses'] as $i => $text) {
                $this->insert('civilization_bonus', ['civilization_id' => $id, 'bonus_text' => $text, 'sort_order' => $i]);
            }
            if ($d['team_bonus']) {
                $this->update('civilization', ['team_bonus' => $d['team_bonus']], ['id' => $id]);
            }
        }

        // 2. the Indians are gone from the game
        $indians = $this->civId('indians');
        if ($indians) {
            $this->delete('civilization', ['id' => $indians]);
        }

        // 3. unique units and their civilizations
        $fix = [
            'genitour' => 'berbers',
            'condottiero' => 'italians',
            'legionary' => 'romans',
            'savar' => 'persians',
            'imperial-camel-rider' => 'hindustanis',
            'winged-hussar' => 'lithuanians',
        ];
        foreach ($fix as $unit => $civ) {
            $this->update('unit', ['is_unique' => 1, 'civilization_id' => $this->civId($civ)], ['slug' => $unit]);
        }

        // 4. Longboat -> Longship
        $this->update('unit', ['name' => 'Longship', 'slug' => 'longship', 'page_title' => 'Longship (Age of Empires II)'], ['slug' => 'longboat']);
        foreach (['upgrades_to', 'upgrades_from'] as $col) {
            $this->execute("UPDATE unit SET $col = REPLACE($col, 'Longboat', 'Longship') WHERE $col LIKE '%Longboat%'");
        }
        foreach (['strong_against', 'weak_against', 'elite_variant', 'other_forms'] as $col) {
            $this->execute("UPDATE unit SET $col = JSON_QUOTE(REPLACE(JSON_UNQUOTE($col), 'Longboat', 'Longship'))
                WHERE CAST($col AS CHAR) LIKE '%Longboat%'");
        }
    }

    public function safeDown()
    {
        echo "m261119_180000_fix_civ_and_unit_data cannot be reverted: restore the database backup instead.\n";
        return false;
    }
}
