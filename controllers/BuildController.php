<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\BuildOrder;
use app\models\Elo;

class BuildController extends Controller
{
    public function actionIndex()
    {
        $elo = Yii::$app->request->get('elo');
        $category = Yii::$app->request->get('category');
        $query = BuildOrder::find();
        if ($elo && isset(Elo::BANDS[$elo])) $query->andWhere(['elo' => $elo]);
        if ($category && isset(BuildOrder::CATEGORIES[$category])) $query->andWhere(['category' => $category]);
        $builds = $query->all();
        // easy builds first inside each band, the bands in skill order
        $order = array_flip(array_keys(Elo::BANDS));
        usort($builds, function ($a, $b) use ($order) {
            return [$order[$a->elo] ?? 9, $a->difficulty, $a->title] <=> [$order[$b->elo] ?? 9, $b->difficulty, $b->title];
        });

        return $this->render('index', [
            'builds' => $builds,
            'currentElo' => $elo,
            'currentCategory' => $category,
        ]);
    }

    public function actionView($slug)
    {
        $build = BuildOrder::findOne(['slug' => $slug]);
        if (!$build) {
            throw new NotFoundHttpException('Build order not found.');
        }
        $related = BuildOrder::find()
            ->where(['category' => $build->category])
            ->andWhere(['<>', 'id', $build->id])
            ->limit(4)->all();

        return $this->render('view', ['build' => $build, 'related' => $related]);
    }
}
