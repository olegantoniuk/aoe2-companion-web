<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Article;
use app\models\Elo;

class GuideController extends Controller
{
    public function actionIndex()
    {
        $elo = Yii::$app->request->get('elo');
        $category = Yii::$app->request->get('category');
        $query = Article::find();
        if ($elo && isset(Elo::BANDS[$elo])) $query->andWhere(['elo' => $elo]);
        if ($category && isset(Article::CATEGORIES[$category])) $query->andWhere(['category' => $category]);
        $articles = $query->orderBy(['title' => SORT_ASC])->all();

        // grouped by Elo band, in skill order
        $byElo = [];
        foreach (array_keys(Elo::BANDS) as $band) {
            $byElo[$band] = [];
        }
        foreach ($articles as $a) {
            $byElo[$a->elo][] = $a;
        }

        return $this->render('index', [
            'byElo' => array_filter($byElo),
            'total' => count($articles),
            'currentElo' => $elo,
            'currentCategory' => $category,
        ]);
    }

    public function actionView($slug)
    {
        $article = Article::findOne(['slug' => $slug]);
        if (!$article) {
            throw new NotFoundHttpException('Guide not found.');
        }
        $more = Article::find()
            ->where(['elo' => $article->elo])
            ->andWhere(['<>', 'id', $article->id])
            ->orderBy(['title' => SORT_ASC])
            ->limit(6)->all();

        return $this->render('view', ['article' => $article, 'more' => $more]);
    }
}
