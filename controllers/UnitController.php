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

        $query->with('armorClasses');

        // Filters
        $age = Yii::$app->request->get('age');
        $unique = Yii::$app->request->get('unique');
        $search = Yii::$app->request->get('q');

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
            'query' => $query->orderBy(['name' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        // Get filter options
        $ages = Unit::find()->select('age')->distinct()->where(['not', ['age' => null]])->column();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'ages' => $ages,
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
        $query = Unit::find()->with('armorClasses', 'civilization')->orderBy(['name' => SORT_ASC]);

        if (isset($cat['armorClassNames'])) {
            // Broad category — filter by armor class
            $unitIds = (new \yii\db\Query())
                ->select(['unit_id'])
                ->distinct()
                ->from('unit_armor_class')
                ->where(['name' => $cat['armorClassNames']])
                ->column();
            $units = $unitIds ? $query->andWhere(['id' => $unitIds])->all() : [];
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
            // Broad categories (by armor class)
            'archers' => [
                'name' => 'Archers',
                'armorClassNames' => ['Archers'],
                'icon' => 'arbalester',
                'description' => 'Foot archer units including Crossbowmen, Arbalests, and ranged infantry.',
            ],
            'infantry' => [
                'name' => 'Infantry',
                'armorClassNames' => ['Infantry'],
                'icon' => 'champion',
                'description' => 'Melee and ranged infantry units trained primarily at the Barracks.',
            ],
            'cavalry' => [
                'name' => 'Cavalry',
                'armorClassNames' => ['Cavalry'],
                'icon' => 'paladin',
                'description' => 'Mounted units including Knights, Light Cavalry, and Cavalry Archers.',
            ],
            'siege-weapons' => [
                'name' => 'Siege Weapons',
                'armorClassNames' => ['Siege Weapons'],
                'icon' => 'siege-onager',
                'description' => 'Siege units built at the Siege Workshop.',
            ],
            'naval' => [
                'name' => 'Naval Units',
                'armorClassNames' => ['Ship', 'Ships', 'Long-range warship'],
                'icon' => 'galleon',
                'description' => 'Ships and naval vessels built at the Dock.',
            ],
            'monks' => [
                'name' => 'Monks',
                'armorClassNames' => ['Monks'],
                'icon' => 'monk',
                'description' => 'Religious units that can heal and convert.',
            ],
            'gunpowder' => [
                'name' => 'Gunpowder Units',
                'armorClassNames' => ['Gunpowder Units'],
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
     * Armor-class-based unit classification definitions.
     */
    public static function getClassDefinitions()
    {
        return [
            // Main classes
            'infantry' => [
                'name' => 'Infantry',
                'description' => 'Melee foot soldiers that form the backbone of most armies. Countered by archers and siege.',
                'armorClasses' => ['Infantry'],
                'icon' => 'champion',
                'isMain' => true,
            ],
            'cavalry' => [
                'name' => 'Cavalry',
                'description' => 'Mounted melee units with high mobility and power. Countered by spearmen and camels.',
                'armorClasses' => ['Cavalry'],
                'icon' => 'paladin',
                'isMain' => true,
            ],
            'archers' => [
                'name' => 'Archers',
                'description' => 'Ranged units that deal pierce damage from a distance. Countered by skirmishers and siege.',
                'armorClasses' => ['Archers'],
                'icon' => 'arbalester',
                'isMain' => true,
            ],
            'cavalry-archers' => [
                'name' => 'Cavalry Archers',
                'description' => 'Mounted ranged units combining mobility with ranged firepower. Countered by skirmishers and camels.',
                'armorClasses' => ['Cavalry Archers'],
                'icon' => 'heavy-cavalry-archer',
                'isMain' => true,
            ],
            'siege-weapons' => [
                'name' => 'Siege Weapons',
                'description' => 'Powerful machines designed to destroy buildings and massed units. Vulnerable to melee cavalry.',
                'armorClasses' => ['Siege Weapons'],
                'icon' => 'siege-onager',
                'isMain' => true,
            ],
            'ships' => [
                'name' => 'Ships',
                'description' => 'Naval vessels for controlling waterways. Built at the Dock.',
                'armorClasses' => ['Ship', 'Ships', 'Long-range warship'],
                'icon' => 'galleon',
                'isMain' => true,
            ],
            'monks' => [
                'name' => 'Monks',
                'description' => 'Religious units that can heal allies and convert enemies. Countered by light cavalry.',
                'armorClasses' => ['Monks'],
                'icon' => 'monk',
                'isMain' => true,
            ],
            'gunpowder-units' => [
                'name' => 'Gunpowder Units',
                'description' => 'Units utilizing gunpowder technology for devastating ranged attacks. Available from Imperial Age.',
                'armorClasses' => ['Gunpowder Units'],
                'icon' => 'hand-cannoneer',
                'isMain' => true,
            ],
            // Sub classes
            'elephant-units' => [
                'name' => 'Elephant Units',
                'description' => 'Powerful elephant-mounted units with high HP. Vulnerable to spearmen and monks.',
                'armorClasses' => ['Elephant Units'],
                'icon' => 'battle-elephant',
                'isMain' => false,
            ],
            'camel-units' => [
                'name' => 'Camel Units',
                'description' => 'Camel riders effective against cavalry. Vulnerable to infantry.',
                'armorClasses' => ['Camel Units'],
                'icon' => 'heavy-camel-rider',
                'isMain' => false,
            ],
            'spearmen' => [
                'name' => 'Spearmen',
                'description' => 'Anti-cavalry infantry wielding spears, pikes, and halberds.',
                'armorClasses' => ['Spearmen'],
                'icon' => 'halberdier',
                'isMain' => false,
            ],
            'skirmishers' => [
                'name' => 'Skirmishers',
                'description' => 'Ranged trash units effective against archers. Weak against infantry and cavalry.',
                'armorClasses' => ['Skirmishers'],
                'icon' => 'elite-skirmisher',
                'isMain' => false,
            ],
            'rams' => [
                'name' => 'Rams',
                'description' => 'Siege units specialized in destroying buildings. High pierce armor.',
                'armorClasses' => ['Rams'],
                'icon' => 'siege-ram',
                'isMain' => false,
            ],
            'fire-ships' => [
                'name' => 'Fire Ships',
                'description' => 'Close-range naval vessels that set enemy ships ablaze.',
                'armorClasses' => ['Fire Ships'],
                'icon' => 'fast-fire-ship',
                'isMain' => false,
            ],
            'shock-infantry' => [
                'name' => 'Shock Infantry',
                'description' => 'Fast infantry with high pierce armor — Eagle Scout line.',
                'armorClasses' => ['Shock infantry'],
                'icon' => 'elite-eagle-warrior',
                'isMain' => false,
            ],
        ];
    }

    /**
     * Classes catalog page.
     */
    public function actionClasses()
    {
        $classes = self::getClassDefinitions();

        // Compute real unit counts from DB
        foreach ($classes as $slug => &$def) {
            $unitIds = (new \yii\db\Query())
                ->select(['unit_id'])
                ->distinct()
                ->from('unit_armor_class')
                ->where(['name' => $def['armorClasses']])
                ->column();
            $def['unitCount'] = count($unitIds);
        }
        unset($def);

        // Resolve icon URLs
        $iconSlugs = array_column($classes, 'icon');
        $iconUnits = Unit::find()->select(['slug', 'image_icon'])->where(['slug' => $iconSlugs])->all();
        $iconMap = [];
        foreach ($iconUnits as $u) {
            $iconMap[$u->slug] = $u->iconUrl;
        }

        return $this->render('classes', [
            'classes' => $classes,
            'iconMap' => $iconMap,
        ]);
    }

    /**
     * Single class detail page.
     */
    public function actionClass($slug)
    {
        $classes = self::getClassDefinitions();
        if (!isset($classes[$slug])) {
            throw new NotFoundHttpException('Unit class not found.');
        }

        $classDef = $classes[$slug];

        $unitIds = (new \yii\db\Query())
            ->select(['unit_id'])
            ->distinct()
            ->from('unit_armor_class')
            ->where(['name' => $classDef['armorClasses']])
            ->column();

        $units = Unit::find()
            ->where(['id' => $unitIds])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        // Load armor classes for badge display
        $unitArmorClasses = [];
        if ($unitIds) {
            $rows = (new \yii\db\Query())
                ->select(['unit_id', 'name'])
                ->from('unit_armor_class')
                ->where(['unit_id' => $unitIds])
                ->all();
            foreach ($rows as $row) {
                $unitArmorClasses[$row['unit_id']][] = $row['name'];
            }
        }

        return $this->render('class', [
            'classDef' => $classDef,
            'classSlug' => $slug,
            'units' => $units,
            'unitArmorClasses' => $unitArmorClasses,
            'allClasses' => $classes,
        ]);
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
