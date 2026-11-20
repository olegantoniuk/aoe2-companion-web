<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * - General guides about the ladder and moving between skill levels (category elo-and-progression).
 * - A corrected sentence about team ratings in the matchmaking guide.
 * - Scene data for every build order step, so the build page can draw the steps with game sprites.
 * Data: data/kb/progress_articles.json, data/kb/article_fixes.json, data/kb/build_scenes.json.
 */
class m261120_040000_add_progression_guides_and_build_scenes extends Migration
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
        foreach ($this->load('progress_articles') as $a) {
            $this->insert('{{%article}}', [
                'slug' => $a['slug'],
                'title' => $a['title'],
                'category' => 'elo-and-progression',
                'elo' => $a['elo'] ?? 'beginner',
                'summary' => $a['summary'] ?? null,
                'body' => $a['body'] ?? '',
                'reading_minutes' => $a['reading_minutes'] ?? null,
                'related_units' => self::enc($a['related_units'] ?? []),
                'civs' => self::enc([]),
                'sources' => self::enc($a['sources'] ?? []),
                'videos' => self::enc($a['videos'] ?? []),
            ]);
        }

        foreach ($this->load('article_fixes') as $fix) {
            $body = (new Query())->select('body')->from('{{%article}}')->where(['slug' => $fix['slug']])->scalar();
            if ($body !== false && strpos($body, $fix['old']) !== false) {
                $this->update('{{%article}}', ['body' => str_replace($fix['old'], $fix['new'], $body)], ['slug' => $fix['slug']]);
            }
        }

        foreach ($this->load('build_scenes') as $slug => $scenes) {
            $raw = (new Query())->select('steps')->from('{{%build_order}}')->where(['slug' => $slug])->scalar();
            if ($raw === false) continue;
            $steps = json_decode($raw, true);
            if (!is_array($steps) || count($steps) !== count($scenes)) continue;
            foreach ($steps as $i => $s) {
                $steps[$i]['scene'] = $scenes[$i];
            }
            $this->update('{{%build_order}}', ['steps' => self::enc($steps)], ['slug' => $slug]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%article}}', ['category' => 'elo-and-progression']);
        foreach ((new Query())->select(['id', 'steps'])->from('{{%build_order}}')->all() as $r) {
            $steps = json_decode($r['steps'], true) ?: [];
            foreach ($steps as $i => $s) unset($steps[$i]['scene']);
            $this->update('{{%build_order}}', ['steps' => self::enc($steps)], ['id' => $r['id']]);
        }
    }
}
