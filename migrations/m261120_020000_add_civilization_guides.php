<?php

use yii\db\Migration;

/**
 * A guide for every civilization and its signature build orders, researched mostly from YouTube coaching videos.
 * Data: data/kb/civ_articles.json and data/kb/civ_builds.json.
 */
class m261120_020000_add_civilization_guides extends Migration
{
    private function load($name)
    {
        $f = __DIR__ . '/data/kb/' . $name . '.json';
        return is_file($f) ? json_decode(file_get_contents($f), true) : [];
    }

    private static function enc($v)
    {
        return $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function safeUp()
    {
        foreach ($this->load('civ_articles') as $a) {
            $this->insert('{{%article}}', [
                'slug' => $a['slug'],
                'title' => $a['title'],
                'category' => $a['category'] ?? 'civilization-strategy',
                'elo' => $a['elo'] ?? 'intermediate',
                'summary' => $a['summary'] ?? null,
                'body' => $a['body'] ?? '',
                'reading_minutes' => $a['reading_minutes'] ?? null,
                'related_units' => self::enc($a['related_units'] ?? []),
                'civs' => self::enc($a['civs'] ?? []),
                'sources' => self::enc($a['sources'] ?? []),
                'videos' => self::enc($a['videos'] ?? []),
            ]);
        }
        foreach ($this->load('civ_builds') as $b) {
            $this->insert('{{%build_order}}', [
                'slug' => $b['slug'],
                'title' => $b['title'],
                'strategy' => $b['strategy'] ?? null,
                'category' => $b['category'] ?? null,
                'elo' => $b['elo'] ?? null,
                'difficulty' => $b['difficulty'] ?? null,
                'map_types' => self::enc($b['map_types'] ?? []),
                'civs' => self::enc($b['civs'] ?? []),
                'civ_specific' => 1,
                'summary' => $b['summary'] ?? null,
                'description' => $b['description'] ?? null,
                'steps' => self::enc($b['steps'] ?? []),
                'after_feudal' => $b['after_feudal'] ?? null,
                'tips' => self::enc($b['tips'] ?? []),
                'beaten_by' => $b['beaten_by'] ?? null,
                'sources' => self::enc($b['sources'] ?? []),
                'videos' => self::enc($b['videos'] ?? []),
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%article}}', ['like', 'slug', 'civ-%', false]);
        $this->delete('{{%build_order}}', ['civ_specific' => 1]);
    }
}
