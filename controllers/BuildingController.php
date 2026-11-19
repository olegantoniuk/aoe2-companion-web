<?php

namespace app\controllers;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Building;

class BuildingController extends Controller
{
    public function actionIndex()
    {
        $buildings = Building::find()->orderBy(['name' => SORT_ASC])->all();
        $byCategory = [];
        foreach (array_keys(Building::CATEGORIES) as $c) {
            $byCategory[$c] = [];
        }
        foreach ($buildings as $b) {
            $byCategory[isset($byCategory[$b->category]) ? $b->category : 'other'][] = $b;
        }

        return $this->render('index', ['byCategory' => array_filter($byCategory), 'total' => count($buildings)]);
    }

    public function actionView($slug)
    {
        $building = Building::findOne(['slug' => $slug]);
        if (!$building) {
            throw new NotFoundHttpException('Building not found.');
        }

        return $this->render('view', ['building' => $building]);
    }
}
