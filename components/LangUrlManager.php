<?php

namespace app\components;

use Yii;
use yii\web\UrlManager;

/**
 * Prefixes every URL it makes with the current language ("/uk/units/arbalester"); English has no prefix.
 * Pass 'lang' => 'en' among the route params to make a URL in another language.
 */
class LangUrlManager extends UrlManager
{
    public function createUrl($params)
    {
        $params = (array)$params;
        $lang = $params['lang'] ?? Yii::$app->language;
        unset($params['lang']);
        $url = parent::createUrl($params);
        $prefix = Lang::prefix($lang);
        if ($prefix === '') return $url;
        $base = $this->showScriptName ? $this->getScriptUrl() : $this->getBaseUrl();
        $rest = substr($url, strlen($base));
        // the home page is "/uk", not "/uk/"
        if ($rest === '/' || $rest === '') return $base . $prefix;
        return $base . $prefix . $rest;
    }
}
