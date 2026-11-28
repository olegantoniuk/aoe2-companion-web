<?php

namespace app\components;

use Yii;
use yii\helpers\Html;

/**
 * Translation of game names and of the site's texts from the database.
 *
 * Names (units, buildings, technologies, civilizations, armor classes, ages, resources) live in
 * messages/<lang>/names.php, keyed by the English name. In Ukrainian a name is shown as "Арбалетник (Arbalester)":
 * the translation with the original in round brackets.
 *
 *   Tr::name('Arbalester')       "Арбалетник (Arbalester)"; plain text: titles, alt, attributes
 *   Tr::nameHtml('Arbalester')   "Арбалетник <span class="name-orig">Arbalester</span>", encoded, for HTML: the muted original needs no brackets
 *   Tr::short('Arbalester')      "Арбалетник", only where the original cannot fit (it stays in a title attribute)
 *
 * Texts from the database (descriptions, tactics, bonuses, build steps, guides) live in
 * messages/<lang>/content/<domain>.json as {"English text": "translation"}. The English text is the key, so an
 * edited English text falls back to English until it is translated again.
 *
 *   Tr::text($unit->description, 'units')
 *
 * Interface strings use Yii::t('app', 'English text'), messages/<lang>/app.php.
 * Counted words: Tr::plural($n, 'unit|units') with 'юніт|юніти|юнітів' as the translation in app.php.
 */
class Tr
{
    /** Domains of the content files, by the table the text comes from. */
    const DOMAINS = ['civs', 'units', 'techs', 'buildings', 'builds', 'guides'];

    /** The four ages, in order. They are shown without the English original: "Замкова епоха". */
    const AGES = ['Dark Age', 'Feudal Age', 'Castle Age', 'Imperial Age'];

    /** Ukrainian alphabet order: PHP compares bytes, which puts є, і, ї, ґ after я. */
    const UK_ALPHABET = 'абвгґдеєжзиіїйклмнопрстуфхцчшщьюя';

    private static $content = [];
    private static $missing = [];

    public static function isEnglish()
    {
        return Yii::$app->language === Lang::DEFAULT;
    }

    /** The translated name alone, or the English one when there is none. */
    public static function short($name)
    {
        $name = (string)$name;
        if ($name === '' || self::isEnglish()) return $name;
        return Yii::t('names', $name);
    }

    /** "Арбалетник (Arbalester)" in Ukrainian, "Arbalester" in English. Plain text. */
    public static function name($name)
    {
        $name = (string)$name;
        $t = self::short($name);
        return $t === $name || in_array($name, self::AGES, true) ? $t : $t . ' (' . $name . ')';
    }

    /** The same as name(), HTML encoded, with the original in a muted span. */
    public static function nameHtml($name)
    {
        $name = (string)$name;
        $t = self::short($name);
        if ($t === $name || in_array($name, self::AGES, true)) return Html::encode($t);
        return Html::encode($t) . ' <span class="name-orig">' . Html::encode($name) . '</span>';
    }

    /** A key that sorts names in the current language's alphabetical order: usort by strcmp(sortKey(a), sortKey(b)). */
    public static function sortKey($name)
    {
        $s = mb_strtolower(self::short($name), 'UTF-8');
        if (self::isEnglish()) return $s;
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach (preg_split('//u', self::UK_ALPHABET, -1, PREG_SPLIT_NO_EMPTY) as $i => $ch) {
                $map[$ch] = chr(160 + $i);   // one byte above ASCII per letter, in alphabet order
            }
            $map["'"] = '';
        }
        return strtr($s, $map);
    }

    /** Sorts models by their translated name ($attr), in the current language's alphabetical order. */
    public static function sortByName(array $models, $attr = 'name')
    {
        usort($models, function ($a, $b) use ($attr) {
            return strcmp(self::sortKey($a->$attr), self::sortKey($b->$attr));
        });
        return $models;
    }

    /** A text from the database in the current language; the English text when there is no translation yet. */
    public static function text($text, $domain)
    {
        if ($text === null || $text === '' || self::isEnglish()) return $text;
        $map = self::content($domain);
        $key = str_replace("\r", '', (string)$text);
        if (isset($map[$key])) return $map[$key];
        if (YII_ENV_DEV) self::$missing[$domain][$key] = true;
        return $text;
    }

    /** Every item of a list of texts (strong_against, tips...). */
    public static function texts($list, $domain)
    {
        return array_map(function ($t) use ($domain) { return is_string($t) ? self::text($t, $domain) : $t; }, (array)$list);
    }

    /**
     * The word for a count: Tr::plural(3, 'unit|units') is "units" in English and "юніти" in Ukrainian
     * when app.php translates 'unit|units' as 'юніт|юніти|юнітів' (one, few, many).
     */
    public static function plural($n, $forms)
    {
        $f = explode('|', Yii::t('app', $forms));
        $n = abs((int)$n);
        if (self::isEnglish() || count($f) < 3) return $n === 1 ? $f[0] : ($f[1] ?? $f[0]);
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        if ($mod10 === 1 && $mod100 !== 11) return $f[0];
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) return $f[1];
        return $f[2];
    }

    private static function content($domain)
    {
        $lang = Yii::$app->language;
        if (!isset(self::$content[$lang][$domain])) {
            $file = Yii::getAlias("@app/messages/$lang/content/$domain.json");
            $map = [];
            if (is_file($file)) {
                $key = ['tr-content', $lang, $domain, filemtime($file)];
                $map = Yii::$app->cache->get($key);
                if ($map === false) {
                    $map = json_decode(file_get_contents($file), true) ?: [];
                    Yii::$app->cache->set($key, $map, 86400);
                }
            }
            self::$content[$lang][$domain] = $map;
        }
        return self::$content[$lang][$domain];
    }

    /**
     * In dev, the texts a page asked for and did not find, appended to runtime/i18n-missing/<lang>/<domain>.jsonl:
     * crawling the site in a language collects everything still to translate.
     */
    public static function flushMissing()
    {
        if (!self::$missing) return;
        $dir = Yii::getAlias('@runtime/i18n-missing/' . Yii::$app->language);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        foreach (self::$missing as $domain => $texts) {
            $lines = '';
            foreach (array_keys($texts) as $t) $lines .= json_encode($t, JSON_UNESCAPED_UNICODE) . "\n";
            file_put_contents("$dir/$domain.jsonl", $lines, FILE_APPEND | LOCK_EX);
        }
        self::$missing = [];
    }
}
