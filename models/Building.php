<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * A building: stats, what it trains and what it researches.
 */
class Building extends ActiveRecord
{
    use JsonFields;

    const CATEGORIES = [
        'economy' => 'Economy',
        'military' => 'Military',
        'defense' => 'Defense',
        'religion' => 'Religion',
        'other' => 'Other',
    ];

    public static function tableName()
    {
        return 'building';
    }

    public function getIconUrl()
    {
        if (!$this->icon) return null;
        $path = '/images/buildings/' . $this->icon;
        return is_file(\Yii::getAlias('@webroot') . $path) ? $path : null;
    }

    /**
     * Who can build it, as [mode, names]: ['only', [...]] when a few civilizations have it,
     * ['except', [...]] when almost all do, ['all', []] otherwise.
     */
    public function getAvailabilityNote()
    {
        $only = $this->listOf('civs_only');
        $without = $this->listOf('civs_without');
        if ($without && (!$only || count($without) < count($only))) return ['except', $without];
        if ($only) return ['only', $only];
        return ['all', []];
    }

    /**
     * Big picture for the building page: its own preview sprite, else the smaller scene sprite of the same building.
     * Returns [url, width, height] or null.
     */
    public function getPreview()
    {
        $root = \Yii::getAlias('@webroot');
        $files = ['/images/buildings-preview/' . $this->slug . '.png'];
        $key = \app\components\BuildScene::BUILDINGS[$this->name] ?? null;
        if ($key) $files[] = '/images/scene/bld_' . $key . '.png';
        foreach ($files as $f) {
            if (is_file($root . $f)) {
                [$w, $h] = getimagesize($root . $f);
                return [$f . '?v=' . filemtime($root . $f), $w, $h];
            }
        }
        return null;
    }

    public function getCategoryLabel()
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string)$this->category);
    }

    public function getCostHtml()
    {
        $parts = [];
        foreach (['Food' => $this->cost_food, 'Wood' => $this->cost_wood, 'Gold' => $this->cost_gold, 'Stone' => $this->cost_stone] as $res => $n) {
            if ($n) {
                $parts[] = '<span class="cost-part"><img src="/images/resources/' . $res . '.png" alt="' . $res . '" class="resource-icon"> ' . (int)$n . '</span>';
            }
        }
        return $parts ? implode(' ', $parts) : 'Free';
    }

    /** Units it trains, as models, in the data order. */
    public function getTrainedUnits()
    {
        $names = $this->listOf('trains');
        if (!$names) return [];
        $byName = [];
        foreach (Unit::find()->where(['name' => $names])->all() as $u) {
            $byName[$u->name] = $u;
        }
        return array_values(array_filter(array_map(function ($n) use ($byName) { return $byName[$n] ?? null; }, $names)));
    }

    /** Technologies researched here, as models. */
    public function getTechnologies()
    {
        return Technology::find()->where(['building' => $this->name])->with('civilization')->orderBy(['name' => SORT_ASC])->all();
    }
}
