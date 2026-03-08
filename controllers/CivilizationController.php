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

        return $this->render('index', [
            'civs' => $civs,
        ]);
    }

    public function actionView($slug)
    {
        $civ = Civilization::find()->where(['slug' => $slug])->one();
        if (!$civ) {
            throw new NotFoundHttpException('Civilization not found.');
        }

        // Get unique units for this civ
        $uniqueUnits = Unit::find()
            ->where(['civilization_id' => $civ->id, 'is_unique' => 1])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        // Get available shared units via availability table
        $availableUnits = Unit::find()
            ->innerJoin('unit_availability ua', 'ua.unit_id = unit.id')
            ->where(['ua.civilization_id' => $civ->id, 'ua.available' => 1, 'unit.is_unique' => 0])
            ->orderBy(['unit.type' => SORT_ASC, 'unit.name' => SORT_ASC])
            ->all();

        // Get unavailable shared units
        $unavailableUnits = Unit::find()
            ->innerJoin('unit_availability ua', 'ua.unit_id = unit.id')
            ->where(['ua.civilization_id' => $civ->id, 'ua.available' => 0, 'unit.is_unique' => 0])
            ->orderBy(['unit.type' => SORT_ASC, 'unit.name' => SORT_ASC])
            ->all();

        return $this->render('view', [
            'civ' => $civ,
            'uniqueUnits' => $uniqueUnits,
            'availableUnits' => $availableUnits,
            'unavailableUnits' => $unavailableUnits,
        ]);
    }
}
