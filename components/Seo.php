<?php

namespace app\components;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Page title, meta description and social preview for a view.
 * In a view: Seo::page($this, 'Arbalester: Stats, Counters and Upgrades', $description, $imageUrl);
 * The layout prints the tags with Seo::head($this).
 */
class Seo
{
    const SITE = 'AoE2 Companion';
    const DEFAULT_DESCRIPTION = 'Age of Empires II: Definitive Edition reference: every civilization, unit, building and technology, counters, matchups, build orders and guides sorted by Elo.';
    const DEFAULT_IMAGE = '/images/hero/hero.webp';

    /** Remember the page's title, description and image; also sets $view->title for the breadcrumbs and h1 code. */
    public static function page($view, $title, $description = null, $image = null)
    {
        $view->params['seo'] = [
            'title' => $title,
            'description' => $description ? self::clip($description) : null,
            'image' => $image,
        ];
    }

    /** Plain text cut to about 155 characters at a word end, with a full stop or an ellipsis. */
    public static function clip($text, $max = 155)
    {
        $t = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('## ', '', (string)$text))));
        if (mb_strlen($t) <= $max) return $t;
        $cut = mb_substr($t, 0, $max);
        // end at the last full sentence if one fits, else at the last word
        $dot = mb_strrpos($cut, '. ');
        if ($dot !== false && $dot > $max * 0.5) return mb_substr($cut, 0, $dot + 1);
        return rtrim(mb_substr($cut, 0, mb_strrpos($cut, ' ')), ',;:') . '…';
    }

    /** The <title> text. */
    public static function title($view)
    {
        $t = $view->params['seo']['title'] ?? $view->title;
        if (!$t || $t === 'Home' || $t === Yii::t('app', 'Home')) {
            return self::SITE . ': ' . Yii::t('app', 'Age of Empires II Civilizations, Units, Build Orders and Guides');
        }
        return $t . ' | ' . self::SITE;
    }

    /** Meta description, canonical address, the page in the other languages and Open Graph / Twitter tags. */
    public static function head($view)
    {
        $seo = $view->params['seo'] ?? [];
        $desc = $seo['description'] ?? Yii::t('app', self::DEFAULT_DESCRIPTION);
        $title = self::title($view);
        $req = Yii::$app->request;
        // canonical: the address without filters that only reorder or narrow a list
        $canonical = $req->hostInfo . strtok($req->url, '?');
        $image = $seo['image'] ?? self::DEFAULT_IMAGE;
        if ($image && $image[0] === '/') $image = $req->hostInfo . strtok($image, '?');
        $tags = [
            '<meta name="description" content="' . Html::encode($desc) . '">',
            '<link rel="canonical" href="' . Html::encode($canonical) . '">',
            '<meta property="og:site_name" content="' . self::SITE . '">',
            '<meta property="og:type" content="website">',
            '<meta property="og:title" content="' . Html::encode($title) . '">',
            '<meta property="og:description" content="' . Html::encode($desc) . '">',
            '<meta property="og:url" content="' . Html::encode($canonical) . '">',
            '<meta property="og:image" content="' . Html::encode($image) . '">',
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="' . Html::encode($title) . '">',
            '<meta name="twitter:description" content="' . Html::encode($desc) . '">',
            '<meta property="og:locale" content="' . (Lang::current() === 'uk' ? 'uk_UA' : 'en_US') . '">',
        ];
        // the same page in every language; English is the default for everyone else
        foreach (array_keys(Lang::LANGS) as $code) {
            $tags[] = '<link rel="alternate" hreflang="' . $code . '" href="' . Html::encode($req->hostInfo . strtok(Lang::switchUrl($code), '?')) . '">';
        }
        $tags[] = '<link rel="alternate" hreflang="x-default" href="' . Html::encode($req->hostInfo . strtok(Lang::switchUrl(Lang::DEFAULT), '?')) . '">';
        return implode("\n    ", $tags);
    }
}
