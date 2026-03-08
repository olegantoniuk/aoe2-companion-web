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

        $recentCivs = Civilization::find()->orderBy(['name' => SORT_ASC])->limit(8)->all();

        return $this->render('index', [
            'civCount' => $civCount,
            'unitCount' => $unitCount,
            'uniqueCount' => $uniqueCount,
            'sharedCount' => $sharedCount,
            'recentCivs' => $recentCivs,
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
