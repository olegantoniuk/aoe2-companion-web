<?php

namespace app\controllers;

use yii\web\Controller;
use app\models\Building;
use app\models\Technology;

class TechnologyController extends Controller
{
    /** Every technology, grouped by the building that researches it, ordered by age inside a group. */
    public function actionIndex()
    {
        $techs = Technology::find()->with('civilization')->all();
        $ageOrder = ['Dark Age' => 0, 'Feudal Age' => 1, 'Castle Age' => 2, 'Imperial Age' => 3];
        usort($techs, function ($a, $b) use ($ageOrder) {
            return [$ageOrder[$a->age] ?? 4, (int)!!$a->civilization_id, $a->name] <=> [$ageOrder[$b->age] ?? 4, (int)!!$b->civilization_id, $b->name];
        });
        $groups = [];
        foreach ($techs as $t) {
            $groups[$t->building ?: 'Other'][] = $t;
        }
        $buildings = [];
        foreach (Building::find()->where(['name' => array_keys($groups)])->all() as $b) {
            $buildings[$b->name] = $b;
        }
        // economy buildings first, then the rest in the order a game reaches them
        $first = ['Town Center', 'Lumber Camp', 'Mill', 'Mining Camp', 'Market', 'Blacksmith', 'Barracks', 'Archery Range',
            'Stable', 'Siege Workshop', 'Dock', 'University', 'Monastery', 'Castle'];
        uksort($groups, function ($a, $b) use ($first) {
            $ia = array_search($a, $first, true);
            $ib = array_search($b, $first, true);
            return [$ia === false ? 99 : $ia, $a] <=> [$ib === false ? 99 : $ib, $b];
        });

        return $this->render('index', ['groups' => $groups, 'buildings' => $buildings, 'total' => count($techs)]);
    }
}
