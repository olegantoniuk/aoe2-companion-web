<?php

namespace app\controllers;

use app\components\Matchup;
use app\models\Civilization;
use Yii;
use yii\filters\Cors;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

/**
 * A read-only JSON API for apps such as AoE2 Companion Client.
 *
 *   GET /api/civilizations
 *   GET /api/matchup?me=bohemians&enemies=inca,byzantines
 *
 * A civilization is given by its slug or its name, in any case; the game's own names (Incas, Mayans) work too.
 * Answers are open to any origin (CORS), since the client runs in a desktop web view.
 */
class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    /** The game's names for civilizations whose name here differs. */
    private const ALIASES = ['incas' => 'inca', 'mayans' => 'maya'];

    public function behaviors()
    {
        return [
            'cors' => [
                'class' => Cors::class,
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Max-Age' => 86400,
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    /** Every civilization: slug, name and emblem. */
    public function actionCivilizations()
    {
        $civs = Civilization::find()->orderBy(['name' => SORT_ASC])->all();
        return ['civilizations' => array_map([$this, 'civRef'], $civs)];
    }

    /**
     * For each enemy: what it is good at, its bonuses, its units in upgrade lines and, for each line, the units of
     * `me` that counter it (best first, with the attack bonus behind the counter when there is one).
     */
    public function actionMatchup($me, $enemies)
    {
        $mine = $this->findCiv($me);
        if (!$mine) {
            throw new BadRequestHttpException("Unknown civilization: $me");
        }
        $out = ['me' => $this->civRef($mine), 'enemies' => [], 'unknown' => []];
        foreach (array_slice(array_filter(array_map('trim', explode(',', $enemies))), 0, 7) as $name) {
            $enemy = $this->findCiv($name);
            if (!$enemy) {
                $out['unknown'][] = $name;
                continue;
            }
            $m = Matchup::analyse($mine, $enemy);
            $lines = [];
            foreach ([[$m['upgradeLines'], false], [$m['navalLines'], true]] as [$group, $naval]) {
                foreach ($group as $line) {
                    $last = end($line['units']);
                    $lines[] = [
                        'naval' => $naval,
                        'units' => array_map([$this, 'unitRef'], $line['units']),
                        'counters' => array_map(function ($c) use ($line, $m) {
                            $bonus = null;
                            foreach ($line['units'] as $u) {
                                if (isset($m['counterBonuses'][$u->id][$c->id])) {
                                    $bonus = $m['counterBonuses'][$u->id][$c->id];
                                }
                            }
                            return $this->unitRef($c) + ['bonus' => $bonus];
                        }, $line['counters']),
                    ];
                }
            }
            $out['enemies'][] = [
                'civ' => $this->civRef($enemy) + [
                    'focus' => $enemy->focus,
                    'team_bonus' => self::clean($enemy->team_bonus),
                    'bonuses' => array_map(function ($b) { return self::clean($b->bonus_text); }, $enemy->bonuses),
                    'unique_techs' => array_values(array_map(function ($t) {
                        return ['name' => $t->name, 'description' => self::clean($t->description)];
                    }, array_filter($enemy->technologies, function ($t) { return preg_match('/^[A-Z]/', $t->name); }))),
                ],
                'page' => Url::to(['matchup/view', 'yourCiv' => $mine->slug, 'enemyCiv' => $enemy->slug], true),
                'lines' => $lines,
            ];
        }
        return $out;
    }

    private function findCiv($name)
    {
        $key = strtolower(trim($name));
        $key = self::ALIASES[$key] ?? $key;
        return Civilization::find()->where(['slug' => $key])->one()
            ?? Civilization::find()->where(['LOWER(name)' => $key])->one();
    }

    private function civRef($c)
    {
        return [
            'slug' => $c->slug,
            'name' => $c->name,
            'emblem' => $c->emblemUrl ? Url::to($c->emblemUrl, true) : null,
        ];
    }

    private function unitRef($u)
    {
        return [
            'slug' => $u->slug,
            'name' => $u->name,
            'unique' => (bool)$u->is_unique,
            'age' => preg_replace('/\s+(before|since).*/', '', $u->age ?? ''),
            'building' => $u->buildingGroup,
            'icon' => $u->iconUrl ? Url::to($u->iconUrl, true) : null,
        ];
    }

    /** The scraped texts keep some wiki markup: [[Castle Age (Age of Empires II)|Castle Age]] -> Castle Age. */
    private static function clean($t)
    {
        $t = preg_replace('/\[\[(?:[^\]|]*\|)?([^\]]*)\]\]/', '$1', (string)$t);
        $t = preg_replace('/\[\[([^\]|(]*)(?:\s*\([^)]*\))?/', '$1', $t);
        $t = preg_replace('/\s*\(Age of Empires II\)/', '', $t);
        return trim(preg_replace('/\s+([.,;])/', '$1', $t));
    }
}
