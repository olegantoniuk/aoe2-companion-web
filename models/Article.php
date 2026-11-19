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

    public function getCategoryLabel()
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string)$this->category);
    }
}
