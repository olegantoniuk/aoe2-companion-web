<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Cleans the unique technology lists of civilizations: wiki footnote stars, notes that were scraped as names,
 * Bearded Axe (removed by The Viking Sagas) and the unique technologies that were missing
 * (Ordonnance Companies for the Franks, Fabric Shields for the Inca).
 */
class m261120_030000_fix_civilization_technologies extends Migration
{
    public function safeUp()
    {
        // "Eupseong*" -> "Eupseong"
        foreach ((new Query())->select(['id', 'name'])->from('{{%civilization_technology}}')->where(['like', 'name', '*'])->all() as $r) {
            $this->update('{{%civilization_technology}}', ['name' => rtrim($r['name'], '*')], ['id' => $r['id']]);
        }
        $this->delete('{{%civilization_technology}}', ['name' => [
            'called Couriers before the Definitive Edition',
            'Pavise (HD Edition)',
            'Bearded Axe',
        ]]);
        foreach (['Franks' => 'Ordonnance Companies', 'Inca' => 'Fabric Shields'] as $civ => $tech) {
            $civId = (new Query())->select('id')->from('{{%civilization}}')->where(['name' => $civ])->scalar();
            $desc = (new Query())->select('description')->from('{{%technology}}')->where(['name' => $tech])->scalar();
            $exists = (new Query())->from('{{%civilization_technology}}')->where(['civilization_id' => $civId, 'name' => $tech])->exists();
            if ($civId && !$exists) {
                $this->insert('{{%civilization_technology}}', ['civilization_id' => $civId, 'name' => $tech, 'description' => $desc ?: null]);
            }
        }
    }

    public function safeDown()
    {
        echo "m261120_030000_fix_civilization_technologies only removes bad rows and cannot be reverted.\n";
        return false;
    }
}
