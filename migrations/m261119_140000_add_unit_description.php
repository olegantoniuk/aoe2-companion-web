<?php

use yii\db\Migration;

/**
 * Adds unit.description: a short note at the top of the unit page on how the unit plays.
 * Filled for the Serjeant first.
 */
class m261119_140000_add_unit_description extends Migration
{
    public function safeUp()
    {
        $this->addColumn('unit', 'description', $this->text()->after('notes'));

        $this->update('unit', [
            'description' => "The Sicilian unique infantry and the only unique unit you can train in the Feudal Age, "
                . "at a Donjon. Serjeants build and repair Donjons themselves, which is what makes the Sicilian "
                . "Donjon rush work: one Donjon near the enemy, then Serjeants put up more of them by the gold, "
                . "stone and wood.\n\n"
                . "On reaching the Castle Age every Serjeant gets +25 HP, +3 attack and +2/+1 armor for free. "
                . "It hits a little softer than a Long Swordsman but holds up much better against arrows and "
                . "melee, and Sicilian land units shrug off part of every attack bonus, so the usual infantry "
                . "counters work less well. Heavily armored melee units such as Teutonic Knights and Boyars, "
                . "and units with big bonuses against infantry, still beat it. The First Crusade technology "
                . "brings 5 Serjeants from each Town Center, up to five Town Centers, even at the population cap.",
        ], ['slug' => 'serjeant']);
    }

    public function safeDown()
    {
        $this->dropColumn('unit', 'description');
    }
}
