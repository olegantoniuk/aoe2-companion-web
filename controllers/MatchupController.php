<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\components\Matchup;
use app\models\Civilization;

class MatchupController extends Controller
{
    public function actionIndex()
    {
        $civs = Civilization::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('index', [
            'civs' => $civs,
        ]);
    }

    public function actionView($yourCiv, $enemyCiv)
    {
        $your = Civilization::find()->where(['slug' => $yourCiv])->one();
        $enemy = Civilization::find()->where(['slug' => $enemyCiv])->one();

        if (!$your || !$enemy) {
            throw new NotFoundHttpException('Civilization not found.');
        }

        $civs = Civilization::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('view', array_merge(Matchup::analyse($your, $enemy), [
            'yourCiv' => $your,
            'enemyCiv' => $enemy,
            'civs' => $civs,
        ]));
    }
}
