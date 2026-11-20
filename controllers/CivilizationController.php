<?php

namespace app\controllers;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Civilization;
use app\models\Unit;
use app\models\UnitAvailability;

class CivilizationController extends Controller
{
    public function actionIndex()
    {
        $civs = Civilization::find()->orderBy(['name' => SORT_ASC])->all();

        $uniqueByCiv = Unit::uniqueByCivilization();

        return $this->render('index', [
            'civs' => $civs,
            'uniqueByCiv' => $uniqueByCiv,
        ]);
    }

    public function actionView($slug)
    {
        $civ = Civilization::find()->where(['slug' => $slug])->one();
        if (!$civ) {
            throw new NotFoundHttpException('Civilization not found.');
        }

        // its own units: unique ones, units shared by a few civilizations, upgrades only it has (Houfnice)
        $uniqueUnits = Unit::uniqueByCivilization()[$civ->id] ?? [];

        // Get available shared units via availability table
        $availableUnits = Unit::find()
            ->innerJoin('unit_availability ua', 'ua.unit_id = unit.id')
            ->where(['ua.civilization_id' => $civ->id, 'ua.available' => 1, 'unit.is_unique' => 0])
            ->with('armorClasses')
            ->orderBy(['unit.name' => SORT_ASC])
            ->all();

        // Get unavailable shared units
        $unavailableUnits = Unit::find()
            ->innerJoin('unit_availability ua', 'ua.unit_id = unit.id')
            ->where(['ua.civilization_id' => $civ->id, 'ua.available' => 0, 'unit.is_unique' => 0])
            ->with('armorClasses')
            ->orderBy(['unit.name' => SORT_ASC])
            ->all();

        // its guide and its own build orders
        $like = ['like', 'civs', '"' . $civ->name . '"'];
        $guide = \app\models\Article::find()->where($like)->andWhere(['category' => 'civilization-strategy'])->one();
        $civBuilds = \app\models\BuildOrder::find()->where($like)->andWhere(['civ_specific' => 1])->orderBy(['difficulty' => SORT_ASC, 'title' => SORT_ASC])->all();

        return $this->render('view', [
            'civ' => $civ,
            'guide' => $guide,
            'civBuilds' => $civBuilds,
            'uniqueUnits' => $uniqueUnits,
            'availableUnits' => $availableUnits,
            'unavailableUnits' => $unavailableUnits,
        ]);
    }
}
