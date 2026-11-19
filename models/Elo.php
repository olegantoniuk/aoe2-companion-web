<?php

namespace app\models;

/**
 * Skill bands the knowledge base is split by (1v1 random map ladder rating).
 */
class Elo
{
    const BANDS = [
        'beginner' => ['Beginner', 'Elo < 900'],
        'intermediate' => ['Intermediate', '900 ≤ Elo < 1300'],
        'advanced' => ['Advanced', '1300 ≤ Elo < 1700'],
        'expert' => ['Expert', 'Elo ≥ 1700'],
    ];

    public static function label($key)
    {
        return self::BANDS[$key][0] ?? ucfirst((string)$key);
    }

    public static function range($key)
    {
        return self::BANDS[$key][1] ?? '';
    }
}
