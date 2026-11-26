<?php

namespace app\components;

use Yii;
use yii\base\BootstrapInterface;

/**
 * The site's languages. English lives at the root (/units/arbalester), every other language under its code
 * (/uk/units/arbalester). This bootstrap reads the code off the request path, sets Yii::$app->language and
 * leaves the rest of the path to the URL rules; LangUrlManager puts the code back in front of every URL it makes.
 */
class Lang implements BootstrapInterface
{
    const DEFAULT = 'en';

    /** code => [name in that language, short label for the header] */
    const LANGS = [
        'en' => ['English', 'EN'],
        'uk' => ['Українська', 'UA'],
    ];

    public function bootstrap($app)
    {
        if (!$app instanceof \yii\web\Application) return;
        $path = $app->request->getPathInfo();
        foreach (array_keys(self::LANGS) as $code) {
            if ($code === self::DEFAULT) continue;
            if ($path === $code || strpos($path, $code . '/') === 0) {
                $app->language = $code;
                $app->request->setPathInfo((string)substr($path, strlen($code) + 1));
                $app->homeUrl = '/' . $code;
                break;
            }
        }
    }

    public static function current()
    {
        return Yii::$app->language;
    }

    public static function isDefault()
    {
        return Yii::$app->language === self::DEFAULT;
    }

    /** "/uk" for Ukrainian, "" for English: what goes in front of a site path. */
    public static function prefix($lang = null)
    {
        $lang = $lang ?: Yii::$app->language;
        return $lang === self::DEFAULT ? '' : '/' . $lang;
    }

    /** The page being shown, in another language (its path and query string). */
    public static function switchUrl($lang)
    {
        $req = Yii::$app->request;
        $path = '/' . ltrim($req->getPathInfo(), '/');
        $query = $req->getQueryString();
        $url = self::prefix($lang) . ($path === '/' && $lang !== self::DEFAULT ? '' : $path);
        return ($url === '' ? '/' : $url) . ($query ? '?' . $query : '');
    }
}
