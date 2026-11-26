<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\components\Matchup;
use app\components\Tr;
use app\models\Civilization;

class MatchupController extends Controller
{
    /** Ukrainian letters in alphabet order (strcmp puts і, ї, є and ґ after я). */
    const UK_ALPHABET = 'абвгґдеєжзиіїйклмнопрстуфхцчшщьюя';

    /** Every civilization, by name in the current language: the selectors list them by what people read. */
    private static function civsByName()
    {
        $civs = Civilization::find()->orderBy(['name' => SORT_ASC])->all();
        if (Tr::isEnglish()) return $civs;
        $key = function ($c) {
            $k = '';
            foreach (preg_split('//u', mb_strtolower(Tr::short($c->name)), -1, PREG_SPLIT_NO_EMPTY) as $ch) {
                $pos = mb_strpos(self::UK_ALPHABET, $ch);
                $k .= $pos === false ? $ch : sprintf("\x01%02d", $pos);
            }
            return $k;
        };
        usort($civs, function ($a, $b) use ($key) { return strcmp($key($a), $key($b)); });
        return $civs;
    }

    public function actionIndex()
    {
        $civs = self::civsByName();

        return $this->render('index', [
            'civs' => $civs,
        ]);
    }

    public function actionView($yourCiv, $enemyCiv)
    {
        $your = Civilization::find()->where(['slug' => $yourCiv])->one();
        $enemy = Civilization::find()->where(['slug' => $enemyCiv])->one();

        if (!$your || !$enemy) {
            throw new NotFoundHttpException(Yii::t('app', 'Civilization not found.'));
        }

        $civs = self::civsByName();

        return $this->render('view', array_merge(Matchup::analyse($your, $enemy), [
            'yourCiv' => $your,
            'enemyCiv' => $enemy,
            'civs' => $civs,
        ]));
    }
}
