<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Unit;
use app\models\Civilization;
use app\components\Tr;

class UnitController extends Controller
{
    public function actionIndex()
    {
        // every unit on one page: the search and the filters work in the browser (web/js/live-filter.js)
        $units = Unit::find()->with(['armorClasses', 'civilization'])->all();

        return $this->render('index', ['units' => Tr::sortByName($units)]);
    }

    public function actionView($slug)
    {
        $unit = Unit::find()->where(['slug' => $slug])->one();
        if (!$unit) {
            throw new NotFoundHttpException(Yii::t('app', 'Unit not found.'));
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
            throw new NotFoundHttpException(Yii::t('app', 'Category not found.'));
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
     * 'name' stays English (it is a lookup key): show it with Tr::name(). 'description' is translated here.
     */
    public static function getCategoryDefinitions()
    {
        return [
            // Broad categories (by armor class)
            'archers' => [
                'name' => 'Archers',
                'armorClassNames' => ['Archers'],
                'icon' => 'arbalester',
                'description' => Yii::t('app', 'Foot archer units including Crossbowmen, Arbalests, and ranged infantry.'),
            ],
            'infantry' => [
                'name' => 'Infantry',
                'armorClassNames' => ['Infantry'],
                'icon' => 'champion',
                'description' => Yii::t('app', 'Melee and ranged infantry units trained primarily at the Barracks.'),
            ],
            'cavalry' => [
                'name' => 'Cavalry',
                'armorClassNames' => ['Cavalry'],
                'icon' => 'paladin',
                'description' => Yii::t('app', 'Mounted units including Knights, Light Cavalry, and Cavalry Archers.'),
            ],
            'siege-weapons' => [
                'name' => 'Siege Weapons',
                'armorClassNames' => ['Siege Weapons'],
                'icon' => 'siege-onager',
                'description' => Yii::t('app', 'Siege units built at the Siege Workshop.'),
            ],
            'naval' => [
                'name' => 'Naval Units',
                'armorClassNames' => ['Ship', 'Ships', 'Long-range warship'],
                'icon' => 'galleon',
                'description' => Yii::t('app', 'Ships and naval vessels built at the Dock.'),
            ],
            'monks' => [
                'name' => 'Monks',
                'armorClassNames' => ['Monks'],
                'icon' => 'monk',
                'description' => Yii::t('app', 'Religious units that can heal and convert.'),
            ],
            'gunpowder' => [
                'name' => 'Gunpowder Units',
                'armorClassNames' => ['Gunpowder Units'],
                'icon' => 'hand-cannoneer',
                'description' => Yii::t('app', 'Units using gunpowder technology.'),
            ],
            // Narrow categories (specific unit lines)
            'spearmen' => [
                'name' => 'Spearmen',
                'names' => ['Spearman', 'Pikeman', 'Halberdier'],
                'icon' => 'halberdier',
                'description' => Yii::t('app', 'The Spearman upgrade line, a counter to cavalry.'),
            ],
            'skirmishers' => [
                'name' => 'Skirmishers',
                'names' => ['Skirmisher', 'Elite Skirmisher', 'Imperial Skirmisher'],
                'icon' => 'elite-skirmisher',
                'description' => Yii::t('app', 'Ranged trash units that counter archers.'),
            ],
            'light-cavalry' => [
                'name' => 'Light Cavalry',
                'names' => ['Scout Cavalry', 'Light Cavalry', 'Hussar', 'Winged Hussar'],
                'icon' => 'hussar',
                'description' => Yii::t('app', 'Fast, cheap mounted units for raiding and scouting.'),
            ],
            'knights' => [
                'name' => 'Knights',
                'names' => ['Knight', 'Cavalier', 'Paladin'],
                'icon' => 'paladin',
                'description' => Yii::t('app', 'The Knight upgrade line of heavy cavalry.'),
            ],
            'camels' => [
                'name' => 'Camel Units',
                'like' => 'Camel',
                'icon' => 'heavy-camel-rider',
                'description' => Yii::t('app', 'Camel riders, a counter to cavalry.'),
            ],
            'eagle-warriors' => [
                'name' => 'Eagle Warriors',
                'names' => ['Eagle Scout', 'Eagle Warrior', 'Elite Eagle Warrior'],
                'icon' => 'elite-eagle-warrior',
                'description' => Yii::t('app', 'Fast Meso-American infantry that stands in for cavalry.'),
            ],
            'battle-elephants' => [
                'name' => 'Elephants',
                'like' => 'Elephant',
                'icon' => 'war-elephant',
                'description' => Yii::t('app', 'Powerful elephant-mounted units.'),
            ],
            'rams' => [
                'name' => 'Rams',
                'names' => ['Battering Ram', 'Capped Ram', 'Siege Ram'],
                'icon' => 'siege-ram',
                'description' => Yii::t('app', 'Rams, built to knock down buildings.'),
            ],
        ];
    }

    /**
     * Armor-class-based unit classification definitions.
     * 'name' stays English (it is a lookup key): show it with Tr::name(). 'description' is translated here.
     */
    public static function getClassDefinitions()
    {
        return [
            // Main classes
            'infantry' => [
                'name' => 'Infantry',
                'description' => Yii::t('app', 'Melee foot soldiers that form the backbone of most armies. Countered by archers and siege.'),
                'armorClasses' => ['Infantry'],
                'icon' => 'champion',
                'isMain' => true,
            ],
            'cavalry' => [
                'name' => 'Cavalry',
                'description' => Yii::t('app', 'Mounted melee units with high mobility and power. Countered by spearmen and camels.'),
                'armorClasses' => ['Cavalry'],
                'icon' => 'paladin',
                'isMain' => true,
            ],
            'archers' => [
                'name' => 'Archers',
                'description' => Yii::t('app', 'Ranged units that deal pierce damage from a distance. Countered by skirmishers and siege.'),
                'armorClasses' => ['Archers'],
                'icon' => 'arbalester',
                'isMain' => true,
            ],
            'cavalry-archers' => [
                'name' => 'Cavalry Archers',
                'description' => Yii::t('app', 'Mounted ranged units combining mobility with ranged firepower. Countered by skirmishers and camels.'),
                'armorClasses' => ['Cavalry Archers'],
                'icon' => 'heavy-cavalry-archer',
                'isMain' => true,
            ],
            'siege-weapons' => [
                'name' => 'Siege Weapons',
                'description' => Yii::t('app', 'Powerful machines designed to destroy buildings and massed units. Vulnerable to melee cavalry.'),
                'armorClasses' => ['Siege Weapons'],
                'icon' => 'siege-onager',
                'isMain' => true,
            ],
            'ships' => [
                'name' => 'Ships',
                'description' => Yii::t('app', 'Naval vessels for controlling waterways. Built at the Dock.'),
                'armorClasses' => ['Ship', 'Ships', 'Long-range warship'],
                'icon' => 'galleon',
                'isMain' => true,
            ],
            'monks' => [
                'name' => 'Monks',
                'description' => Yii::t('app', 'Religious units that can heal allies and convert enemies. Countered by light cavalry.'),
                'armorClasses' => ['Monks'],
                'icon' => 'monk',
                'isMain' => true,
            ],
            'gunpowder-units' => [
                'name' => 'Gunpowder Units',
                'description' => Yii::t('app', 'Units utilizing gunpowder technology for devastating ranged attacks. Available from Imperial Age.'),
                'armorClasses' => ['Gunpowder Units'],
                'icon' => 'hand-cannoneer',
                'isMain' => true,
            ],
            // Sub classes
            'elephant-units' => [
                'name' => 'Elephant Units',
                'description' => Yii::t('app', 'Powerful elephant-mounted units with high HP. Vulnerable to spearmen and monks.'),
                'armorClasses' => ['Elephant Units'],
                'icon' => 'battle-elephant',
                'isMain' => false,
            ],
            'camel-units' => [
                'name' => 'Camel Units',
                'description' => Yii::t('app', 'Camel riders effective against cavalry. Vulnerable to infantry.'),
                'armorClasses' => ['Camel Units'],
                'icon' => 'heavy-camel-rider',
                'isMain' => false,
            ],
            'spearmen' => [
                'name' => 'Spearmen',
                'description' => Yii::t('app', 'Anti-cavalry infantry wielding spears, pikes, and halberds.'),
                'armorClasses' => ['Spearmen'],
                'icon' => 'halberdier',
                'isMain' => false,
            ],
            'skirmishers' => [
                'name' => 'Skirmishers',
                'description' => Yii::t('app', 'Ranged trash units effective against archers. Weak against infantry and cavalry.'),
                'armorClasses' => ['Skirmishers'],
                'icon' => 'elite-skirmisher',
                'isMain' => false,
            ],
            'rams' => [
                'name' => 'Rams',
                'description' => Yii::t('app', 'Siege units specialized in destroying buildings. High pierce armor.'),
                'armorClasses' => ['Rams'],
                'icon' => 'siege-ram',
                'isMain' => false,
            ],
            'fire-ships' => [
                'name' => 'Fire Ships',
                'description' => Yii::t('app', 'Close-range naval vessels that set enemy ships ablaze.'),
                'armorClasses' => ['Fire Ships'],
                'icon' => 'fast-fire-ship',
                'isMain' => false,
            ],
            'shock-infantry' => [
                'name' => 'Shock Infantry',
                'description' => Yii::t('app', 'The Eagle Scout line: fast infantry with high pierce armor.'),
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
            throw new NotFoundHttpException(Yii::t('app', 'Unit class not found.'));
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
