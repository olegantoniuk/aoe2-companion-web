<?php

use yii\db\Migration;

/**
 * Adds unit.tactics: how to play the unit, shown in a second tab next to the description.
 * Lines starting with "## " are subheadings, blank lines split paragraphs.
 * Filled for the Serjeant first.
 */
class m261119_150000_add_unit_tactics extends Migration
{
    public function safeUp()
    {
        $this->addColumn('unit', 'tactics', $this->text()->after('description'));

        $this->update('unit', [
            'tactics' => <<<TXT
## Feudal Age: the Donjon rush
In the Feudal Age a Serjeant is a sturdier Man-at-Arms: 50 HP against 45 and 2/1 armor against 0/1, for one point less attack, less line of sight and a little more food and gold. The catch is that it comes from a Donjon, which costs 175 stone and 50 wood.

Save stone in the Dark Age, walk forward with a few villagers and build the first Donjon near the enemy base. Train Serjeants from it and let them put up more Donjons by the enemy gold, stone and main lumber camp, so keep at least another 50 stone for the second one. After the first Donjon the push no longer needs villagers. Bringing them along is faster but riskier. Feudal Serjeants build 25% slower than villagers, and a Donjon shoots no extra arrows for garrisoned units until the Castle Age.

## Castle Age and later
The Castle Age gives every Serjeant +25 HP, +3 attack and +2/+1 armor for free. Compared with a Long Swordsman it has one point less attack and smaller bonuses against Eagles and buildings, but its armor lets it hold a spot against a mix of melee and archers. As long as you have stone for Castles or Donjons, make it your main infantry. The Militia line is still the better pick against Eagle Warriors and for taking down buildings.

The Elite Serjeant sits between the Huskarl, which is built to shrug off arrows, and the Teutonic Knight, which is built to shrug off melee.

## First Crusade
The Sicilian unique technology brings 5 Serjeants from each Town Center, up to five Town Centers, and works even at the population cap. Use it to save a base under attack or to start a raid with an army the enemy did not see coming. It also makes Sicilian units harder to convert, so Monks stop being a good answer.

## What beats Serjeants
Units with high melee armor such as Teutonic Knights and Boyars. Units with big attack bonuses against them: Jaguar Warriors against infantry and Samurai against unique units. Attacks that ignore or strip armor: the Leitis, Urumi Swordsmen after Wootz Steel, the Obuch, and the bleed damage of the Liao Dao. Cataphracts still win thanks to trample damage and their large bonus against infantry, and Battle and War Elephants crush Serjeants with their HP and trample.
TXT
        ], ['slug' => 'serjeant']);
    }

    public function safeDown()
    {
        $this->dropColumn('unit', 'tactics');
    }
}
