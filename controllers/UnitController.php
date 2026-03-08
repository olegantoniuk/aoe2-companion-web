<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\data\ActiveDataProvider;
use app\models\Unit;
use app\models\Civilization;

class UnitController extends Controller
{
    public function actionIndex()
    {
        $query = Unit::find();

        // Filters
        $type = Yii::$app->request->get('type');
        $age = Yii::$app->request->get('age');
        $unique = Yii::$app->request->get('unique');
        $search = Yii::$app->request->get('q');

        if ($type) {
            $query->andWhere(['type' => $type]);
        }
        if ($age) {
            $query->andWhere(['age' => $age]);
        }
        if ($unique !== null && $unique !== '') {
            $query->andWhere(['is_unique' => (int)$unique]);
        }
        if ($search) {
            $query->andWhere(['like', 'name', $search]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query->orderBy(['type' => SORT_ASC, 'name' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        // Get filter options
        $types = Unit::find()->select('type')->distinct()->where(['not', ['type' => null]])->column();
        $ages = Unit::find()->select('age')->distinct()->where(['not', ['age' => null]])->column();
        sort($types);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'types' => $types,
            'ages' => $ages,
            'currentType' => $type,
            'currentAge' => $age,
            'currentUnique' => $unique,
            'currentSearch' => $search,
        ]);
    }

    public function actionView($slug)
    {
        $unit = Unit::find()->where(['slug' => $slug])->one();
        if (!$unit) {
            throw new NotFoundHttpException('Unit not found.');
        }

        return $this->render('view', [
            'unit' => $unit,
        ]);
    }

    /**
     * Unit category page — shows all units in a category.
     */
    public function actionCategory($slug)
    {
        $categories = self::getCategoryDefinitions();

        if (!isset($categories[$slug])) {
            throw new NotFoundHttpException('Category not found.');
        }

        $cat = $categories[$slug];
        $query = Unit::find()->orderBy(['name' => SORT_ASC]);

        if (isset($cat['typeGroup'])) {
            // Broad category — filter in PHP via typeGroup (computed field)
            $allUnits = $query->all();
            $units = array_filter($allUnits, function ($u) use ($cat) {
                return $u->typeGroup === $cat['typeGroup'];
            });
            $units = array_values($units);
        } elseif (isset($cat['names'])) {
            // Narrow category — exact unit names
            $units = $query->andWhere(['name' => $cat['names']])->all();
        } elseif (isset($cat['like'])) {
            // Pattern-based category
            $units = $query->andWhere(['like', 'name', $cat['like']])->all();
        } else {
            $units = [];
        }

        return $this->render('category', [
            'categoryName' => $cat['name'],
            'categorySlug' => $slug,
            'units' => $units,
            'description' => $cat['description'] ?? null,
        ]);
    }

    /**
     * Category definitions used by actionCategory and counter linking.
     */
    public static function getCategoryDefinitions()
    {
        return [
            // Broad categories (by typeGroup)
            'archers' => [
                'name' => 'Archers',
                'typeGroup' => 'Archer',
                'icon' => 'arbalester',
                'description' => 'Foot archer units including Crossbowmen, Arbalests, and ranged infantry.',
            ],
            'infantry' => [
                'name' => 'Infantry',
                'typeGroup' => 'Infantry',
                'icon' => 'champion',
                'description' => 'Melee and ranged infantry units trained primarily at the Barracks.',
            ],
            'cavalry' => [
                'name' => 'Cavalry',
                'typeGroup' => 'Cavalry',
                'icon' => 'paladin',
                'description' => 'Mounted units including Knights, Light Cavalry, and Cavalry Archers.',
            ],
            'siege-weapons' => [
                'name' => 'Siege Weapons',
                'typeGroup' => 'Siege',
                'icon' => 'siege-onager',
                'description' => 'Siege units built at the Siege Workshop.',
            ],
            'naval' => [
                'name' => 'Naval Units',
                'typeGroup' => 'Naval',
                'icon' => 'galleon',
                'description' => 'Ships and naval vessels built at the Dock.',
            ],
            'monks' => [
                'name' => 'Monks',
                'typeGroup' => 'Monk',
                'icon' => 'monk',
                'description' => 'Religious units that can heal and convert.',
            ],
            'gunpowder' => [
                'name' => 'Gunpowder Units',
                'typeGroup' => 'Gunpowder',
                'icon' => 'hand-cannoneer',
                'description' => 'Units using gunpowder technology.',
            ],
            // Narrow categories (specific unit lines)
            'spearmen' => [
                'name' => 'Spearmen',
                'names' => ['Spearman', 'Pikeman', 'Halberdier'],
                'icon' => 'halberdier',
                'description' => 'The Spearman upgrade line — effective counter to cavalry.',
            ],
            'skirmishers' => [
                'name' => 'Skirmishers',
                'names' => ['Skirmisher', 'Elite Skirmisher', 'Imperial Skirmisher'],
                'icon' => 'elite-skirmisher',
                'description' => 'Ranged trash units — effective counter to archers.',
            ],
            'light-cavalry' => [
                'name' => 'Light Cavalry',
                'names' => ['Scout Cavalry', 'Light Cavalry', 'Hussar', 'Winged Hussar'],
                'icon' => 'hussar',
                'description' => 'Fast, cheap mounted units for raiding and scouting.',
            ],
            'knights' => [
                'name' => 'Knights',
                'names' => ['Knight', 'Cavalier', 'Paladin'],
                'icon' => 'paladin',
                'description' => 'The Knight upgrade line — heavy cavalry.',
            ],
            'camels' => [
                'name' => 'Camel Units',
                'like' => 'Camel',
                'icon' => 'heavy-camel-rider',
                'description' => 'Camel-mounted units — effective counter to cavalry.',
            ],
            'eagle-warriors' => [
                'name' => 'Eagle Warriors',
                'names' => ['Eagle Scout', 'Eagle Warrior', 'Elite Eagle Warrior'],
                'icon' => 'elite-eagle-warrior',
                'description' => 'Fast Meso-American infantry — cavalry substitute.',
            ],
            'battle-elephants' => [
                'name' => 'Elephants',
                'like' => 'Elephant',
                'icon' => 'war-elephant',
                'description' => 'Powerful elephant-mounted units.',
            ],
            'rams' => [
                'name' => 'Rams',
                'names' => ['Battering Ram', 'Capped Ram', 'Siege Ram'],
                'icon' => 'siege-ram',
                'description' => 'Ram units — effective against buildings.',
            ],
        ];
    }

    /**
     * Counter text → category slug mapping, used by views.
     */
    public static function getCounterCategoryMap()
    {
        return [
            'archers' => 'archers',
            'archer units' => 'archers',
            'foot archers' => 'archers',
            'infantry' => 'infantry',
            'cavalry' => 'cavalry',
            'heavy cavalry' => 'cavalry',
            'siege weapons' => 'siege-weapons',
            'siege units' => 'siege-weapons',
            'ranged siege weapons' => 'siege-weapons',
            'ranged siege units' => 'siege-weapons',
            'monks' => 'monks',
            'naval units' => 'naval',
            'ships' => 'naval',
            'gunpowder units' => 'gunpowder',
            'light cavalry' => 'light-cavalry',
            'camel riders' => 'camels',
            'camel units' => 'camels',
            'pikemen' => 'spearmen',
            'spearmen' => 'spearmen',
            'halberdiers' => 'spearmen',
            'skirmishers' => 'skirmishers',
            'elite skirmishers' => 'skirmishers',
            'eagle warriors' => 'eagle-warriors',
            'eagle scouts' => 'eagle-warriors',
            'knights' => 'knights',
            'battle elephants' => 'battle-elephants',
            'war elephants' => 'battle-elephants',
            'melee units' => 'infantry',
            'mounted archers' => 'cavalry',
            'cavalry archers' => 'cavalry',
            'rams' => 'rams',
            'battering rams' => 'rams',
            'siege' => 'siege-weapons',
            'siege weapon' => 'siege-weapons',
            'heavy siege' => 'siege-weapons',
            'ship' => 'naval',
            'camel' => 'camels',
            'elephants' => 'battle-elephants',
            'elephant units' => 'battle-elephants',
            'monk' => 'monks',
            'spearman' => 'spearmen',
            'skirmisher' => 'skirmishers',
        ];
    }
}
