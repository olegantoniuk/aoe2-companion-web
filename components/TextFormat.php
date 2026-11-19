<?php

namespace app\components;

use yii\helpers\Html;

/**
 * Turns the plain text format of the knowledge base into HTML: blank lines separate blocks,
 * "## " starts a heading, "- " starts a list item. Unit and civilization names become links.
 */
class TextFormat
{
    public static function render($text, $selfSlug = null, $headingTag = 'h2')
    {
        $html = '';
        $text = trim(str_replace("\r", '', (string)$text));
        if ($text === '') return '';
        foreach (preg_split('/\n\s*\n/', $text) as $block) {
            $lines = explode("\n", trim($block));
            $para = [];
            $list = [];
            $flushPara = function () use (&$para, &$html, $selfSlug) {
                if ($para) {
                    $html .= '<p>' . UnitPills::linkify(Html::encode(implode(' ', $para)), $selfSlug) . '</p>';
                    $para = [];
                }
            };
            $flushList = function () use (&$list, &$html, $selfSlug) {
                if ($list) {
                    $html .= '<ul>';
                    foreach ($list as $item) {
                        $html .= '<li>' . UnitPills::linkify(Html::encode($item), $selfSlug) . '</li>';
                    }
                    $html .= '</ul>';
                    $list = [];
                }
            };
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, '## ') === 0) {
                    $flushPara();
                    $flushList();
                    $html .= '<' . $headingTag . '>' . Html::encode(substr($line, 3)) . '</' . $headingTag . '>';
                } elseif (preg_match('/^[-*] (.*)$/', $line, $m)) {
                    $flushPara();
                    $list[] = $m[1];
                } elseif ($line !== '') {
                    $flushList();
                    $para[] = $line;
                }
            }
            $flushPara();
            $flushList();
        }
        return $html;
    }

    /** "https://www.youtube.com/watch?v=abc" -> "abc", for an embedded player; null for other links. */
    public static function youtubeId($url)
    {
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', (string)$url, $m)) {
            return $m[1];
        }
        return null;
    }

    /** Host name shown next to a source link. */
    public static function host($url)
    {
        $h = parse_url((string)$url, PHP_URL_HOST);
        return $h ? preg_replace('/^(www|old)\./', '', $h) : '';
    }
}
