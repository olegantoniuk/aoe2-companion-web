<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\Url;
use app\models\Article;
use app\models\BuildOrder;
use app\models\Building;
use app\models\Civilization;
use app\models\Unit;

/** sitemap.xml with every public page, for search engines. */
class SitemapController extends Controller
{
    public function actionIndex()
    {
        $urls = [];
        $add = function ($route, $priority = '0.6') use (&$urls) {
            $urls[] = [Url::to($route, true), $priority];
        };
        $add(['site/index'], '1.0');
        foreach (['civilization/index', 'unit/index', 'building/index', 'technology/index', 'build/index', 'guide/index', 'matchup/index', 'unit/classes'] as $r) {
            $add([$r], '0.8');
        }
        foreach (Civilization::find()->select('slug')->column() as $s) $add(['civilization/view', 'slug' => $s], '0.7');
        foreach (Unit::find()->select('slug')->column() as $s) $add(['unit/view', 'slug' => $s]);
        foreach (Building::find()->select('slug')->column() as $s) $add(['building/view', 'slug' => $s]);
        foreach (BuildOrder::find()->select('slug')->column() as $s) $add(['build/view', 'slug' => $s], '0.7');
        foreach (Article::find()->select('slug')->column() as $s) $add(['guide/view', 'slug' => $s], '0.7');
        foreach (array_keys(UnitController::getClassDefinitions()) as $s) $add(['unit/class', 'slug' => $s], '0.5');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as [$loc, $p]) {
            $xml .= '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc><priority>' . $p . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        return $xml;
    }
}
