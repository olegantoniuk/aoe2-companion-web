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
        $civSlug = Yii::$app->request->get('civ');
        $civ = $civSlug ? \app\models\Civilization::findOne(['slug' => $civSlug]) : null;
        $query = Article::find();
        if ($elo && isset(Elo::BANDS[$elo])) $query->andWhere(['elo' => $elo]);
        if ($category && isset(Article::CATEGORIES[$category])) $query->andWhere(['category' => $category]);
        if ($civ) $query->andWhere(['like', 'civs', '"' . $civ->name . '"']);
        $articles = $query->orderBy(['title' => SORT_ASC])->all();

        // grouped by Elo band, in skill order
        $byElo = [];
        foreach (array_keys(Elo::BANDS) as $band) {
            $byElo[$band] = [];
        }
        // the general articles about levels and progression get a section of their own above the bands
        $progression = [];
        foreach ($articles as $a) {
            if ($a->category === 'elo-and-progression') {
                $progression[] = $a;
                continue;
            }
            $byElo[$a->elo][] = $a;
        }
        $bandOrder = array_flip(array_keys(Elo::BANDS));
        usort($progression, function ($x, $y) use ($bandOrder) {
            return [$bandOrder[$x->elo] ?? 9, $x->title] <=> [$bandOrder[$y->elo] ?? 9, $y->title];
        });

        return $this->render('index', [
            'byElo' => array_filter($byElo),
            'progression' => $progression,
            'total' => count($articles),
            'currentElo' => $elo,
            'currentCategory' => $category,
            'currentCiv' => $civ,
            'civs' => \app\models\Civilization::find()->orderBy(['name' => SORT_ASC])->all(),
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
