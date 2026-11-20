<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * How many villagers work on food, wood, gold and stone after each build order step.
 * The build page shows them as columns when the reader ticks "Villagers on each resource".
 * Data: data/kb/build_vills.json, {slug: [{food, wood, gold, stone, approx?}, ...]} with one entry per step.
 */
class m261120_050000_add_build_villagers extends Migration
{
    private static function enc($v)
    {
        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function safeUp()
    {
        $data = json_decode(file_get_contents(__DIR__ . '/data/kb/build_vills.json'), true) ?: [];
        foreach ($data as $slug => $vills) {
            $raw = (new Query())->select('steps')->from('{{%build_order}}')->where(['slug' => $slug])->scalar();
            if ($raw === false) continue;
            $steps = json_decode($raw, true);
            if (!is_array($steps) || count($steps) !== count($vills)) continue;
            foreach ($steps as $i => $s) {
                $steps[$i]['vills'] = $vills[$i];
            }
            $this->update('{{%build_order}}', ['steps' => self::enc($steps)], ['slug' => $slug]);
        }
    }

    public function safeDown()
    {
        foreach ((new Query())->select(['id', 'steps'])->from('{{%build_order}}')->all() as $r) {
            $steps = json_decode($r['steps'], true) ?: [];
            foreach ($steps as $i => $s) unset($steps[$i]['vills']);
            $this->update('{{%build_order}}', ['steps' => self::enc($steps)], ['id' => $r['id']]);
        }
    }
}
