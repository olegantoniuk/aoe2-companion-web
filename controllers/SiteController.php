<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\LoginForm;
use app\models\Civilization;
use app\models\Unit;

class SiteController extends Controller
{
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex()
    {
        $civCount = Civilization::find()->count();
        $unitCount = Unit::find()->count();
        $uniqueCount = Unit::find()->where(['is_unique' => 1])->count();
        $sharedCount = Unit::find()->where(['is_unique' => 0])->count();
        $buildingCount = \app\models\Building::find()->count();
        $techCount = \app\models\Technology::find()->count();

        $recentCivs = Civilization::find()->orderBy(['name' => SORT_ASC])->limit(16)->all();

        // a picked set per section: the things a new player meets first
        $pick = function ($class, $column, $names) {
            $rows = $class::find()->where([$column => $names])->all();
            $by = [];
            foreach ($rows as $r) $by[$r->$column] = $r;
            return array_values(array_filter(array_map(function ($n) use ($by) { return $by[$n] ?? null; }, $names)));
        };
        $units = $pick(Unit::class, 'name', ['Villager', 'Scout Cavalry', 'Archer', 'Skirmisher', 'Spearman', 'Man-at-Arms',
            'Knight', 'Crossbowman', 'Camel Rider', 'Mangonel', 'Monk', 'Trebuchet']);
        $techs = $pick(\app\models\Technology::class, 'name', ['Loom', 'Double-Bit Axe', 'Horse Collar', 'Wheelbarrow',
            'Fletching', 'Bloodlines', 'Ballistics', 'Conscription']);
        $buildings = $pick(\app\models\Building::class, 'name', ['Town Center', 'House', 'Barracks', 'Archery Range',
            'Stable', 'Monastery', 'Castle', 'Dock']);
        $builds = \app\models\BuildOrder::find()->where(['elo' => ['beginner', 'intermediate']])->orderBy(['difficulty' => SORT_ASC, 'title' => SORT_ASC])->limit(4)->all();
        $guides = [];
        foreach (array_keys(\app\models\Elo::BANDS) as $band) {
            $g = \app\models\Article::find()->where(['elo' => $band])->orderBy(['title' => SORT_ASC])->one();
            if ($g) $guides[] = $g;
        }

        return $this->render('index', [
            'civCount' => $civCount,
            'unitCount' => $unitCount,
            'uniqueCount' => $uniqueCount,
            'sharedCount' => $sharedCount,
            'buildingCount' => $buildingCount,
            'techCount' => $techCount,
            'recentCivs' => $recentCivs,
            'units' => $units,
            'techs' => $techs,
            'buildings' => $buildings,
            'builds' => $builds,
            'guides' => $guides,
        ]);
    }

    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}
