<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * A guide article of the knowledge base.
 */
class Article extends ActiveRecord
{
    use JsonFields;

    const CATEGORIES = [
        'elo-and-progression' => 'Elo and progression',
        'fundamentals' => 'Fundamentals',
        'economy' => 'Economy',
        'military' => 'Military',
        'scouting-and-map-control' => 'Scouting and map control',
        'build-order-theory' => 'Build order theory',
        'civilization-strategy' => 'Civilization strategy',
        'maps' => 'Maps',
        'improvement-and-mindset' => 'Improvement and mindset',
    ];

    public static function tableName()
    {
        return 'article';
    }

    /** Civilizations the guide is about, as models. */
    public function getCivModels()
    {
        $names = $this->listOf('civs');
        return $names ? Civilization::find()->where(['name' => $names])->orderBy(['name' => SORT_ASC])->all() : [];
    }

    /** Round picture left of the title: the civilization emblem for a civilization guide, else its own drawing. */
    public function getAvatarUrl()
    {
        $civs = $this->civModels;
        if (count($civs) === 1 && $civs[0]->emblemUrl) return $civs[0]->emblemUrl;
        $f = '/images/guides/' . $this->slug . '.png';
        return is_file(\Yii::getAlias('@webroot') . $f) ? $f : '/images/icons/guides.png';
    }

    public function getCategoryLabel()
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string)$this->category);
    }
}
