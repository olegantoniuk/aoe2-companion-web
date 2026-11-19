<?php

use yii\db\Migration;

/**
 * Fills the knowledge base: build orders, guide articles, buildings, and the building and age of each technology.
 * Data comes from migrations/data/kb/*.json (collected from the community: Reddit, guides, videos, the wiki).
 */
class m261119_230000_fill_knowledge_base extends Migration
{
    private function load($name)
    {
        $f = __DIR__ . '/data/kb/' . $name . '.json';
        return is_file($f) ? json_decode(file_get_contents($f), true) : [];
    }

    private static function encodeList($v)
    {
        return $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function safeUp()
    {
        foreach ($this->load('builds') as $b) {
            $this->insert('{{%build_order}}', [
                'slug' => $b['slug'],
                'title' => $b['title'],
                'strategy' => $b['strategy'] ?? null,
                'category' => $b['category'] ?? null,
                'elo' => $b['elo'] ?? null,
                'difficulty' => $b['difficulty'] ?? null,
                'map_types' => self::encodeList($b['map_types'] ?? []),
                'civs' => self::encodeList($b['civs'] ?? []),
                'summary' => $b['summary'] ?? null,
                'description' => $b['description'] ?? null,
                'steps' => self::encodeList($b['steps'] ?? []),
                'after_feudal' => $b['after_feudal'] ?? null,
                'tips' => self::encodeList($b['tips'] ?? []),
                'beaten_by' => $b['beaten_by'] ?? null,
                'sources' => self::encodeList($b['sources'] ?? []),
                'videos' => self::encodeList($b['videos'] ?? []),
            ]);
        }

        foreach ($this->load('articles') as $a) {
            $this->insert('{{%article}}', [
                'slug' => $a['slug'],
                'title' => $a['title'],
                'category' => $a['category'] ?? null,
                'elo' => $a['elo'] ?? null,
                'summary' => $a['summary'] ?? null,
                'body' => $a['body'] ?? '',
                'reading_minutes' => $a['reading_minutes'] ?? null,
                'related_units' => self::encodeList($a['related_units'] ?? []),
                'sources' => self::encodeList($a['sources'] ?? []),
                'videos' => self::encodeList($a['videos'] ?? []),
            ]);
        }

        foreach ($this->load('buildings') as $b) {
            $cost = $b['cost'] ?? [];
            $this->insert('{{%building}}', [
                'slug' => $b['slug'],
                'name' => $b['name'],
                'age' => $b['age'] ?? null,
                'category' => $b['category'] ?? null,
                'cost_food' => $cost['food'] ?? null,
                'cost_wood' => $cost['wood'] ?? null,
                'cost_gold' => $cost['gold'] ?? null,
                'cost_stone' => $cost['stone'] ?? null,
                'build_time' => $b['build_time'] ?? null,
                'hit_points' => $b['hit_points'] ?? null,
                'melee_armor' => $b['melee_armor'] ?? null,
                'pierce_armor' => $b['pierce_armor'] ?? null,
                'line_of_sight' => $b['line_of_sight'] ?? null,
                'garrison' => $b['garrison'] ?? null,
                'attack' => isset($b['attack']) ? (string)$b['attack'] : null,
                'range_val' => isset($b['range']) ? (string)$b['range'] : null,
                'size' => $b['size'] ?? null,
                'civs_only' => self::encodeList($b['civs_only'] ?? []),
                'civs_without' => self::encodeList($b['civs_without'] ?? []),
                'replaces' => $b['replaces'] ?? null,
                'requires' => $b['requires'] ?? null,
                'trains' => self::encodeList($b['trains'] ?? []),
                'researches' => self::encodeList($b['researches'] ?? []),
                'icon' => $b['icon'] ?? null,
                'description' => $b['description'] ?? null,
            ]);
        }

        foreach ($this->load('tech_meta') as $name => $m) {
            $this->update('{{%technology}}', [
                'building' => $m['building'] ?? null,
                'age' => $m['age'] ?? null,
            ], ['name' => array_unique([$name, str_replace('- ', '', $name)])]);
        }
    }

    public function safeDown()
    {
        $this->update('{{%technology}}', ['building' => null, 'age' => null]);
        $this->delete('{{%building}}');
        $this->delete('{{%article}}');
        $this->delete('{{%build_order}}');
    }
}
