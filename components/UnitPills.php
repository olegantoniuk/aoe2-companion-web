<?php

namespace app\components;

use app\controllers\UnitController;
use app\models\Unit;
use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/**
 * Round unit avatars, pills (armor classes, ages, buildings, unit groups) and their hover cards.
 * Shared by the unit page and the unit list.
 */
class UnitPills
{
    /** Hover card texts for things that are not units, and avatars for classes without one. */
    const TEXTS = [
        'class:Infantry' => 'Foot soldiers: the Militia line, spearmen, Eagle Warriors and most unique infantry. Archers and siege are good against them, and the Cataphract carries a large attack bonus against this class.',
        'class:Cavalry' => 'Mounted units. They are fast and strong in melee, and every anti-cavalry bonus hits them: spearmen, camels and several unique units deal them extra damage.',
        'class:Archers' => 'Units that shoot from range with pierce damage. Skirmishers carry a bonus against them, and armored infantry and siege can push through their fire.',
        'class:Cavalry Archers' => 'Mounted archers that shoot while staying out of reach. Skirmishers carry a bonus against them.',
        'class:Siege Weapons' => 'Machines from the Siege Workshop that break buildings or hit groups of units. Melee cavalry is the usual answer to them.',
        'class:Ship' => 'Units built at the Dock that fight for control of the water.',
        'class:Ships' => 'Units built at the Dock that fight for control of the water.',
        'class:Monks' => 'Monks heal their own units and convert enemy ones. Light cavalry and Eagles carry bonuses against them.',
        'class:Gunpowder Units' => 'Units with gunpowder weapons, such as the Hand Cannoneer, Janissary and Bombard Cannon. A single shot does a lot of damage.',
        'class:Camel Units' => 'Camel Riders and similar units. They are trained against cavalry and do poorly against infantry and archers.',
        'class:Elephant Units' => 'Elephants have a lot of HP and hit hard. Spearmen and Monks are the usual answer to them.',
        'class:Unique Units' => 'Units that only one civilization can train, most of them at the Castle. Every unique unit carries this class, so a unit with an attack bonus against it hits all of them harder. The Samurai has such a bonus, which is why it is the usual answer to enemy unique units.',
        'class:Shock infantry' => 'The Eagle Scout line and other fast infantry with high pierce armor. Archers do little against them, so they are fought with melee units.',
        'class:Standard Buildings' => 'Most buildings belong to this class. A unit with an attack bonus against it brings buildings down faster.',
        'classicon:Unique Units' => 'unit:samurai',
        'classicon:Standard Buildings' => 'building:House',
        'cat:archers' => 'Foot archers such as the Archer, Crossbowman and Arbalester. They shoot from range, so armored infantry and skirmishers handle them well.',
        'cat:infantry' => 'Infantry with weaker melee armor than the Serjeant, for example the Militia line before its armor upgrades, spearmen and Eagle Warriors.',
        'cat:light-cavalry' => 'Scout Cavalry, Light Cavalry, Hussar and Winged Hussar. Cheap and fast raiders that are good against monks and siege, but weak in a straight fight with infantry.',
        'cat:camels' => 'Camel Riders and similar units. They are trained against cavalry and do poorly against infantry and archers.',
        'cat:gunpowder' => 'Hand Cannoneers, Janissaries, Conquistadors and other gunpowder units. Their shots hit hard enough that infantry armor helps little against them.',
        'cat:siege-weapons' => 'Mangonels, Scorpions, rams and the rest of the Siege Workshop. Onager shots and Scorpion bolts hurt whole groups of infantry at once.',
        'cat:monks' => 'Monks convert enemy units from a distance and heal their own. A slow infantry army has trouble reaching them.',
        'cat:battle-elephants' => 'Battle Elephants, War Elephants and other elephant units. They have a lot of HP and hit hard, so they win against most infantry that is not a spearman.',
        'building:Buildings' => 'Houses, walls, towers and other standard buildings. Units with an attack bonus against buildings tear them down faster.',
        'building:Castle' => 'Trains the unique unit of its civilization, researches unique technologies and fires arrows at nearby enemies. Costs 650 stone.',
        'building:Donjon' => 'The Sicilian defensive tower. It trains Serjeants from the Feudal Age, holds up to 10 units inside and fires arrows. Serjeants can build it themselves.',
        'building:Barracks' => 'Trains infantry such as the Militia line and spearmen. It can be built from the Dark Age.',
        'building:Archery Range' => 'Trains archers, skirmishers and cavalry archers. It needs the Feudal Age and a Barracks.',
        'building:Stable' => 'Trains mounted units such as scouts, knights and camels. It needs the Feudal Age and a Barracks.',
        'building:Siege Workshop' => 'Builds siege weapons such as rams, mangonels and scorpions. It needs the Castle Age and a Blacksmith.',
        'building:Dock' => 'Builds fishing ships, trade cogs, transport ships and warships. It can be built from the Dark Age.',
        'building:Monastery' => 'Trains Monks and researches the technologies that make them stronger. It needs the Castle Age.',
        'building:Fortified Church' => 'The Armenian and Georgian version of the Monastery.',
        'building:Town Center' => 'Trains villagers and researches the age advances. Villagers inside it make it shoot more arrows.',
        'building:Market' => 'Trades resources for gold and the other way round, and trains Trade Carts. It needs the Feudal Age.',
        'building:Krepost' => 'A small Bulgarian castle that trains the Konnik and fires arrows at nearby enemies.',
        'age:Dark Age' => 'The first age. Every game starts here.',
        'age:Feudal Age' => 'The second age. Advancing takes 500 food and two Dark Age buildings.',
        'age:Castle Age' => 'The third age. Castles, unique units and most unit upgrades open up here.',
        'age:Imperial Age' => 'The last age. Elite upgrades of unique units are researched here.',
    ];

    /** Order in which a unit's armor classes decide its main class (same as Unit::getArmorClassGroup). */
    const MAIN_CLASS_ORDER = [
        'Infantry', 'Cavalry', 'Archers', 'Cavalry Archers', 'Siege Weapons', 'Ships', 'Ship',
        'Long-range warship', 'Monks', 'Gunpowder Units', 'Camel Units', 'Elephant Units',
    ];

    private static $tips = [];
    private static $icons = null;
    private static $classSlugs = null;

    /** A hover card text in the current language; the classicon: keys are data and stay as they are. */
    public static function text($key)
    {
        $t = self::TEXTS[$key] ?? null;
        return $t === null || strpos($key, 'classicon:') === 0 ? $t : Yii::t('app', $t);
    }

    /** Icon of a unit by slug, one query for all units. */
    public static function iconForSlug($slug)
    {
        if (self::$icons === null) {
            self::$icons = [];
            foreach (Unit::find()->select(['id', 'slug', 'image_icon'])->all() as $u) {
                self::$icons[$u->slug] = $u->iconUrl;
            }
        }
        return self::$icons[$slug] ?? null;
    }

    /** Icon of a unit by its name; elite versions that are not units of their own come from elite_*_icon.png. */
    public static function iconForName($name)
    {
        static $byName = null;
        if ($byName === null) {
            $byName = [];
            foreach (Unit::find()->select(['id', 'name', 'slug', 'image_icon'])->all() as $u) {
                $byName[strtolower($u->name)] = $u->iconUrl;
            }
        }
        if (!empty($byName[strtolower($name)])) {
            return $byName[strtolower($name)];
        }
        $file = '/images/units/' . strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $name)) . '_icon.png';
        $mtime = @filemtime(Yii::getAlias('@webroot') . $file);
        return $mtime ? "$file?v=$mtime" : null;
    }

    public static function buildingIcon($name)
    {
        $file = '/images/buildings/' . str_replace(' ', '', $name) . '.png';
        return file_exists(Yii::getAlias('@webroot') . $file) ? $file : null;
    }

    /** Age shield from the Combat Stats header, e.g. "Feudal Age" -> /images/ages/FeudalAge.png */
    public static function ageIcon($age)
    {
        $file = '/images/ages/' . str_replace(' ', '', $age) . '.png';
        return file_exists(Yii::getAlias('@webroot') . $file) ? $file : null;
    }

    /**
     * Keywords for picking a picture for a phrase that is not a unit ("Slow moving units", "Buildings on the
     * shoreline"): [keyword, kind (unit|building), unit slug or building file name]. The keyword found earliest in
     * the phrase wins, the longer one when two start at the same place.
     */
    const ICON_KEYWORDS = [
        ['castle', 'building', 'Castle'], ['wall', 'building', 'StoneWall'], ['gate', 'building', 'StoneWall'],
        ['tower', 'building', 'WatchTower'], ['stone defense', 'building', 'GuardTower'],
        ['building', 'building', 'House'], ['buidling', 'building', 'House'], ['structure', 'building', 'House'],
        ['defensive', 'building', 'GuardTower'], ['fortification', 'building', 'StoneWall'],
        ['fishing ship', 'unit', 'fishing-ship'], ['carrack', 'unit', 'galleon'], ['warship', 'unit', 'galleon'], ['ship', 'unit', 'galleon'],
        ['shoreline', 'unit', 'galleon'], ['shallows', 'unit', 'galleon'], ['naval', 'unit', 'galleon'],
        ['elephant', 'unit', 'war-elephant'], ['camel', 'unit', 'heavy-camel-rider'], ['monk', 'unit', 'monk'],
        ['hero', 'unit', 'king'], ['king', 'unit', 'king'], ['villager', 'unit', 'villager'],
        ['ranged siege', 'unit', 'siege-onager'], ['heavy siege', 'unit', 'siege-ram'], ['siege', 'unit', 'siege-onager'],
        ['ram', 'unit', 'siege-ram'], ['gunpowder', 'unit', 'hand-cannoneer'], ['skirmisher', 'unit', 'elite-skirmisher'],
        ['spear', 'unit', 'halberdier'], ['pike', 'unit', 'halberdier'],
        ['cavalry archer', 'unit', 'heavy-cavalry-archer'], ['mounted archer', 'unit', 'heavy-cavalry-archer'],
        ['foot archer', 'unit', 'arbalester'], ['archer', 'unit', 'arbalester'], ['ranged', 'unit', 'arbalester'],
        ['light cavalry', 'unit', 'hussar'], ['heavy cavalry', 'unit', 'paladin'], ['cavalry', 'unit', 'paladin'],
        ['mounted', 'unit', 'paladin'], ['shock infantry', 'unit', 'elite-eagle-warrior'],
        ['heavy infantry', 'unit', 'champion'], ['infantry', 'unit', 'champion'], ['militia', 'unit', 'champion'],
        ['unique unit', 'unit', 'samurai'], ['slow', 'unit', 'siege-ram'], ['melee', 'unit', 'champion'],
        ['dense', 'unit', 'man-at-arms'], ['massed', 'unit', 'man-at-arms'], ['group', 'unit', 'man-at-arms'],
        ['cluttered', 'unit', 'man-at-arms'], ['bunched', 'unit', 'man-at-arms'], ['packed', 'unit', 'man-at-arms'],
        ['unit', 'unit', 'man-at-arms'], ['everything', 'unit', 'man-at-arms'],
    ];

    /** A picture for a phrase: a unit named in it, or the best keyword. */
    public static function guessIcon($phrase)
    {
        static $unitNames = null;
        if ($unitNames === null) {
            $unitNames = [];
            foreach (Unit::find()->select(['id', 'name', 'slug', 'image_icon'])->all() as $u) {
                $n = strtolower($u->name);
                foreach ([$n, $n . 's', $n . 'es', preg_replace('/man$/', 'men', $n), preg_replace('/y$/', 'ies', $n), $n === 'condottiero' ? 'condottieri' : null] as $f) {
                    if ($f) $unitNames[$f] = $u->iconUrl;
                }
            }
        }
        $p = strtolower($phrase);
        $best = null;
        foreach ($unitNames as $n => $icon) {
            if (!$icon) continue;
            if (preg_match('/(?<![a-z])' . preg_quote($n, '/') . '(?![a-z])/', $p, $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1];
                if (!$best || $pos < $best[0] || ($pos === $best[0] && strlen($n) > $best[1])) $best = [$pos, strlen($n), $icon];
            }
        }
        if ($best) return $best[2];
        foreach (self::ICON_KEYWORDS as [$kw, $kind, $ref]) {
            $pos = strpos($p, $kw);
            if ($pos === false) continue;
            $icon = $kind === 'building' ? self::buildingIcon($ref) : self::iconForSlug($ref);
            if ($icon && (!$best || $pos < $best[0] || ($pos === $best[0] && strlen($kw) > $best[1]))) $best = [$pos, strlen($kw), $icon];
        }
        return $best ? $best[2] : null;
    }

    /**
     * Clean a counter phrase scraped from the wiki: wiki templates, leading "And"/"Especially", stray brackets.
     * Returns null for notes that are not counters at all ("Imperial Age before update 141935").
     */
    public static function cleanPhrase($text)
    {
        $t = preg_replace('/\{\{[^|}]*\|([^|}]*)[^}]*\}\}/', '$1', (string)$text);
        $t = trim(preg_replace('/\s+/', ' ', $t));
        if (preg_match('/\bbefore (update|the )/i', $t) || preg_match('/^(dark|feudal|castle|imperial) age\b/i', $t)) {
            return null;
        }
        $t = preg_replace('/^(and|especially|also)\s+/i', '', $t);
        $t = trim($t, " .,;");
        // a lone closing or opening bracket left from a split list
        if (substr_count($t, '(') !== substr_count($t, ')')) $t = trim(str_replace(['(', ')'], '', $t));
        return $t === '' ? null : mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1);
    }

    /** A round avatar image on the beige backdrop. */
    public static function avatar($url, $alt = '')
    {
        return $url ? '<img src="' . Html::encode($url) . '" alt="' . Html::encode($alt) . '" class="unit-avatar">' : '';
    }

    /**
     * One pill: optional avatar, label, optional link and hover card.
     * $o: href, icon, iconClass, variant (class|age|building), unitId, tip [title, subtitle, icon, iconPlain, text],
     * labelHtml (the label as ready HTML such as Tr::nameHtml(), used instead of the encoded $label)
     */
    public static function pill($label, array $o = [])
    {
        $classes = [!empty($o['href']) ? 'counter-unit-link' : 'counter-unit-text'];
        if (!empty($o['variant'])) $classes[] = 'pill-' . $o['variant'];
        $attrs = ' class="' . implode(' ', $classes) . '"';
        if (!empty($o['href'])) $attrs .= ' href="' . Html::encode($o['href']) . '"';
        if (!empty($o['unitId'])) $attrs .= ' data-unit-id="' . (int)$o['unitId'] . '"';
        if (!empty($o['tip'])) {
            $key = 't' . count(self::$tips);
            self::$tips[$key] = $o['tip'];
            $attrs .= ' data-tip="' . $key . '" tabindex="0"';
        }
        $img = !empty($o['icon'])
            ? '<img src="' . Html::encode($o['icon']) . '" alt="" class="' . ($o['iconClass'] ?? 'unit-image-xs') . '">'
            : '';
        $tag = !empty($o['href']) ? 'a' : 'span';
        return "<$tag$attrs>$img" . ($o['labelHtml'] ?? Html::encode($label)) . "</$tag>";
    }

    private static function classSlug($className)
    {
        if (self::$classSlugs === null) {
            self::$classSlugs = [];
            foreach (UnitController::getClassDefinitions() as $slug => $def) {
                foreach ($def['armorClasses'] as $ac) self::$classSlugs[strtolower($ac)] = $slug;
            }
        }
        return self::$classSlugs[strtolower($className)] ?? null;
    }

    /**
     * Pictures for the neutral tag pills of the knowledge base: guide topics, build styles and map types.
     * Values are unit slugs ("unit:archer"), building icon names ("building:Castle") or image paths.
     */
    const TAG_ICONS = [
        // guide topics
        'elo-and-progression' => '/images/icons/expert.png',
        'fundamentals' => '/images/icons/guides.png',
        'economy' => 'unit:villager',
        'military' => '/images/icons/matchup.png',
        'scouting-and-map-control' => 'unit:scout-cavalry',
        'build-order-theory' => '/images/icons/builds.png',
        'civilization-strategy' => '/images/icons/civilizations.png',
        'maps' => 'building:Outpost',
        'improvement-and-mindset' => '/images/icons/classes.png',
        // build styles
        'dark-age-rush' => 'unit:militia',
        'feudal-aggression' => 'unit:scout-cavalry',
        'fast-castle' => '/images/ages/CastleAge.png',
        'boom' => 'building:TownCenter',
        'water' => 'unit:galley',
        'defensive' => 'building:WatchTower',
        'hybrid' => 'building:Dock',
        // map types
        'open' => 'building:Outpost',
        'closed' => 'building:PalisadeWall',
        'hybrid-map' => 'building:Dock',
        'water-map' => 'unit:galley',
    ];

    /** Neutral beige pill for a topic, build style or map type, with its picture. */
    public static function tagPill($key, $label, $href = null)
    {
        $ref = self::TAG_ICONS[$key] ?? null;
        $icon = null;
        if ($ref && strpos($ref, 'unit:') === 0) {
            $icon = self::iconForSlug(substr($ref, 5));
        } elseif ($ref && strpos($ref, 'building:') === 0) {
            $icon = self::buildingIcon(substr($ref, 9));
        } elseif ($ref) {
            $icon = $ref;
        }
        return self::pill($label, ['href' => $href, 'variant' => 'tag', 'icon' => $icon]);
    }

    /** Tag pill for a map type of a build ("open" -> "Open maps"). */
    public static function mapPill($type, $href = null)
    {
        $key = in_array($type, ['hybrid', 'water'], true) ? $type . '-map' : $type;
        return self::tagPill($key, Yii::t('app', ucfirst($type) . ' maps'), $href);
    }

    /** Difficulty of a build as three equal circles; the filled ones take the colour of the level. */
    public static function difficulty($level)
    {
        $level = max(0, min(3, (int)$level));
        $names = [1 => Yii::t('app', 'Easy'), 2 => Yii::t('app', 'Medium'), 3 => Yii::t('app', 'Hard')];
        $label = Html::encode(Yii::t('app', 'Difficulty: {level}', ['level' => $names[$level] ?? Yii::t('app', 'unknown')]));
        $svg = '<svg class="difficulty difficulty-' . $level . '" viewBox="0 0 44 12" width="44" height="12" role="img" aria-label="'
            . $label . '"><title>' . $label . '</title>';
        for ($i = 0; $i < 3; $i++) {
            $svg .= '<circle cx="' . (6 + $i * 16) . '" cy="6" r="4.5" class="' . ($i < $level ? 'is-on' : 'is-off') . '"/>';
        }
        return $svg . '</svg>';
    }

    /**
     * Pill for a skill band (beginner, intermediate, advanced, expert) with its icon and colour.
     * $o: href, size ('lg' for the big one on the guides page).
     */
    public static function eloPill($band, array $o = [])
    {
        $icon = '/images/icons/' . $band . '.png';
        return self::pill(\app\models\Elo::label($band), [
            'href' => $o['href'] ?? null,
            'variant' => 'elo pill-elo-' . $band . (!empty($o['size']) ? ' pill-' . $o['size'] : ''),
            'icon' => is_file(\Yii::getAlias('@webroot') . $icon) ? $icon : null,
        ]);
    }

    /** Chevron pointing right, for "more" links (never an arrow). */
    public static function chevron()
    {
        return '<svg class="chevron-right" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true"><path d="M6 3l5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    /** Address of a section icon (builds, guides, units...). */
    public static function pageIconUrl($name)
    {
        return '/images/icons/' . $name . '.png';
    }

    /** Big round icon left of a page title, like the unit portrait on unit pages. */
    public static function pageIcon($name)
    {
        $f = '/images/icons/' . $name . '.png';
        return is_file(\Yii::getAlias('@webroot') . $f) ? '<img src="' . $f . '" alt="" class="unit-title-icon page-title-icon">' : '';
    }

    /** Purple pill for an armor class: a typical unit as the avatar, link to the class page, hover card. */
    public static function classPill($className, $label = null)
    {
        $defs = UnitController::getClassDefinitions();
        $slug = self::classSlug($className);
        $def = $slug ? $defs[$slug] : null;
        $icon = null;
        if ($ref = self::text('classicon:' . $className)) {
            [$kind, $name] = explode(':', $ref, 2);
            $icon = $kind === 'building' ? self::buildingIcon($name) : self::iconForSlug($name);
        } elseif ($def && !empty($def['icon'])) {
            $icon = self::iconForSlug($def['icon']);
        }
        if (!$icon) {
            // classes with no definition ("All Buildings", "Fishing Ships", "Heavy Siege")
            $icon = self::guessIcon($className);
        }
        $title = $def['name'] ?? $className;
        $text = self::text('class:' . $className) ?? ($def['description'] ?? null);
        return self::pill($label ?? $title, [
            'variant' => 'class',
            'href' => $slug ? Url::to(['unit/class', 'slug' => $slug]) : null,
            'icon' => $icon,
            'labelHtml' => $label === null ? Tr::nameHtml($title) : null,
            'tip' => $text ? ['title' => Tr::name($title), 'subtitle' => Yii::t('app', 'Armor class'), 'icon' => $icon, 'text' => $text] : null,
        ]);
    }

    /** Yellow pill for a civilization: emblem, name, link to its page. */
    public static function civPill($civ)
    {
        if (!$civ) return '';
        return self::pill($civ->name, [
            'labelHtml' => Tr::nameHtml($civ->name),
            'variant' => 'civ',
            'href' => Url::to(['civilization/view', 'slug' => $civ->slug]),
            'icon' => $civ->emblemUrl,
            'iconClass' => 'pill-plain-icon',
        ]);
    }

    /**
     * Links unit and civilization names in already escaped HTML text to their pages.
     * Each name is linked once per call (its first mention); $selfSlug (the unit or civilization of the page) stays plain text.
     */
    public static function linkify($html, $selfSlug = null)
    {
        static $targets = null, $alt = null;
        if ($targets === null) {
            $targets = [];
            // form => [route, slug, the English name the form belongs to]
            foreach (Unit::find()->select(['id', 'name', 'slug'])->all() as $u) {
                $forms = [$u->name, $u->name . 's', $u->name . 'es'];
                if (substr($u->name, -3) === 'man') $forms[] = substr($u->name, 0, -3) . 'men';
                foreach ($forms as $f) {
                    $targets[strtolower($f)] = ['unit/view', $u->slug, $u->name];
                }
            }
            $targets['leiciai'] = ['unit/view', 'leitis', 'Leitis'];
            foreach (\app\models\Civilization::find()->select(['id', 'name', 'slug'])->all() as $c) {
                $targets[strtolower($c->name)] = ['civilization/view', $c->slug, $c->name];
                // "Sicilians" -> "Sicilian"
                if (substr($c->name, -1) === 's') $targets[strtolower(substr($c->name, 0, -1))] = ['civilization/view', $c->slug, $c->name];
            }
            $names = array_keys($targets);
            usort($names, function ($a, $b) { return strlen($b) - strlen($a); });
            $alt = implode('|', array_map(function ($n) { return preg_quote(Html::encode($n), '/'); }, $names));
        }
        $seen = [];
        $link = function ($t, $inner) {
            return '<a href="' . Url::to([$t[0], 'slug' => $t[1]]) . '" class="text-link">' . $inner . '</a>';
        };
        // true for a name that stays plain: unknown, the page's own one, or one already linked
        $skip = function ($t) use ($selfSlug, &$seen) {
            if (!$t || $t[1] === $selfSlug || isset($seen[$t[0] . $t[1]])) return true;
            $seen[$t[0] . $t[1]] = true;
            return false;
        };
        $target = function ($name) use ($targets) {
            return $targets[strtolower(html_entity_decode($name, ENT_QUOTES))] ?? null;
        };

        if (Tr::isEnglish()) {
            return preg_replace_callback('/(?<![\w-])(' . $alt . ')(?![\w-])/i', function ($m) use ($link, $skip, $target) {
                $t = $target($m[1]);
                return $skip($t) ? $m[1] : $link($t, $m[1]);
            }, $html);
        }

        // Translated texts write a name as "Арбалетники (Arbalesters)": up to three words, then the English name in
        // brackets. Words and brackets become one link when the words start like the translated name of that unit or
        // civilization (Tr::short), word for word, with the last letters of each word left free for the case ending.
        // Otherwise only the English name inside the brackets is linked. English names outside brackets link as in English.
        $w = '[\p{L}\p{N}_-]';
        $regex = '/(?<pre>(?:[^\s()<>]+\s+){1,3})\((?<b>' . $alt . ')\)|(?<!' . $w . ')(?<n>' . $alt . ')(?!' . $w . ')/iu';
        return preg_replace_callback($regex, function ($m) use ($link, $skip, $target) {
            if (($m['n'] ?? '') !== '') {
                $t = $target($m['n']);
                return $skip($t) ? $m['n'] : $link($t, $m['n']);
            }
            $t = $target($m['b']);
            if ($skip($t)) return $m[0];
            $pre = rtrim($m['pre']);
            $space = substr($m['pre'], strlen($pre));
            $words = preg_split('/(\s+)/u', $pre, -1, PREG_SPLIT_DELIM_CAPTURE);
            $short = trim(Tr::short($t[2]));
            $own = preg_split('/\s+/u', $short);
            $n = count($own);
            if ($short !== '' && $short !== $t[2] && 2 * $n - 1 <= count($words)) {
                // the last $n words; punctuation or entities in front of the first one stay outside the link
                $start = count($words) - (2 * $n - 1);
                preg_match('/^((?:&[#\w]+;|[^\p{L}\p{N}&])*)(.*)$/su', $words[$start], $fm);
                $span = array_slice($words, $start);
                $span[0] = $fm[2];
                $ok = $fm[2] !== '';
                foreach ($own as $i => $word) {
                    // each part of a hyphenated word on its own: "Воїн-орел" fits "Воїни-орли"
                    $parts = explode('-', mb_strtolower($word));
                    $textParts = explode('-', mb_strtolower(html_entity_decode($span[2 * $i], ENT_QUOTES, 'UTF-8')));
                    if (count($parts) !== count($textParts)) $ok = false;
                    foreach ($parts as $k => $part) {
                        if (!$ok) break;
                        $len = mb_strlen($part);
                        $stem = mb_substr($part, 0, $len >= 4 ? $len - 2 : max(1, $len - 1));
                        if ($stem === '' || mb_strpos($textParts[$k], $stem) !== 0) $ok = false;
                    }
                    if (!$ok) break;
                }
                if ($ok) {
                    return implode('', array_slice($words, 0, $start)) . $fm[1]
                        . $link($t, implode('', $span) . $space . '(' . $m['b'] . ')');
                }
            }
            return $m['pre'] . '(' . $link($t, $m['b']) . ')';
        }, $html);
    }

    /** Hover card data for a unit: start of its description, main stats and cost. */
    public static function unitTip(Unit $u)
    {
        $text = null;
        if ($u->description) {
            // the whole description is the translation key: translate first, then cut
            $first = trim(preg_split('/\n\s*\n/', trim((string)Tr::text($u->description, 'units')))[0]);
            // the first two sentences are enough for a hover card
            $parts = preg_split('/(?<=[.!?])\s+(?=\p{Lu})/u', $first);
            $text = implode(' ', array_slice($parts, 0, 2));
        }
        $atkIcon = $u->melee_attack !== null ? 'melee_attack' : 'pierce_attack';
        $stats = [
            ['hit_points', $u->hit_points, Yii::t('app', 'Hit points')],
            [$atkIcon, $u->melee_attack ?? $u->pierce_attack, Yii::t('app', 'Attack')],
            ['melee_armor', ($u->melee_armor ?? 0) . '/' . ($u->pierce_armor ?? 0), Yii::t('app', 'Armor')],
            [$u->range_val ? 'range' : 'movement_speed', $u->range_val ?: $u->speed, $u->range_val ? Yii::t('app', 'Range') : Yii::t('app', 'Speed')],
        ];
        $cost = [];
        foreach (['Food' => $u->cost_food, 'Wood' => $u->cost_wood, 'Gold' => $u->cost_gold, 'Stone' => $u->cost_stone] as $res => $n) {
            // outside English a third item carries the translated name for the alt text (see registerTips)
            if ($n) $cost[] = Tr::isEnglish() ? [$res, (int)$n] : [$res, (int)$n, Tr::name($res)];
        }
        $building = $u->training_building ? preg_replace('/requires.*|since.*|\(.*\)|\*/i', '', $u->training_building) : '';
        if (!Tr::isEnglish() && trim($building) !== '') $building = Tr::name(trim($building));
        return [
            'title' => Tr::name($u->name),
            'subtitle' => trim(($u->age ? Tr::name($u->age) . ' · ' : '') . $building, ' ·'),
            'icon' => $u->iconUrl,
            'text' => $text,
            'stats' => array_values(array_filter($stats, function ($s) { return $s[1] !== null && $s[1] !== ''; })),
            'cost' => $cost,
        ];
    }

    /** Small round unit avatar that links to the unit and shows its hover card. */
    public static function unitAvatarLink(Unit $u)
    {
        $key = 't' . count(self::$tips);
        self::$tips[$key] = self::unitTip($u);
        return '<a href="' . Url::to(['unit/view', 'slug' => $u->slug]) . '" class="unit-avatar-link" data-tip="' . $key . '" aria-label="' . Html::encode(Tr::name($u->name)) . '">'
            . '<img src="' . Html::encode($u->iconUrl) . '" alt="" class="unit-avatar"></a>';
    }

    /** Number of columns of a unit table row (for group header rows). */
    const TABLE_COLS = 9;

    /** Header cells of a unit table; $third is the label of the third column (Class, Classes, Building). */
    public static function unitTableHead($third = 'Class', $skip = [])
    {
        return '<tr><th class="unit-list-avatar-col"></th><th>' . Yii::t('app', 'Name') . '</th>'
            . (in_array('third', $skip, true) ? '' : '<th>' . Html::encode(Yii::t('app', $third)) . '</th>')
            . (in_array('age', $skip, true) ? '' : '<th>' . Yii::t('app', 'Age') . '</th>')
            . '<th>' . Yii::t('app', 'HP') . '</th><th>' . Yii::t('app', 'Attack') . '</th><th>' . Yii::t('app', 'Armor') . '</th>'
            . '<th>' . Yii::t('app', 'Speed') . '</th><th>' . Yii::t('app', 'Cost') . '</th></tr>';
    }

    /** Column count of a unit table with some columns left out. */
    public static function tableCols($skip = [])
    {
        return self::TABLE_COLS - count($skip);
    }

    /**
     * The cells of one unit row: avatar, name (with the civilizations it belongs to, see Unit::getOwnerCivs), a third column,
     * age, stats with their icons, cost. $third: 'class' (main class), 'classes' (every armor class) or 'building'.
     */
    public static function unitRowCells(Unit $unit, $third = 'class', $skip = [])
    {
        $url = Url::to(['unit/view', 'slug' => $unit->slug]);
        $h = '<td><a href="' . $url . '" class="unit-avatar-link" aria-label="' . Html::encode(Tr::name($unit->name)) . '">'
            . self::avatar($unit->iconUrl) . '</a></td>';

        $h .= '<td><a href="' . $url . '" class="unit-list-name">' . Tr::nameHtml($unit->name) . '</a>';
        if ($civs = $unit->ownerCivs) {
            $h .= '<div class="unit-list-civ-row">' . implode('', array_map([self::class, 'civPill'], $civs)) . '</div>';
        }
        $h .= '</td>';

        if (!in_array('third', $skip, true)) {
        $h .= '<td>';
        if ($third === 'building') {
            $b = trim(preg_replace('/\s*\([^)]*\)|requires.*$|since.*$|before .*$|\*/i', '', (string)$unit->buildingGroup));
            if ($b !== '') {
                $h .= self::pill($b, ['variant' => 'building', 'icon' => self::buildingIcon($b), 'labelHtml' => Tr::nameHtml($b)]);
            }
        } elseif ($third === 'classes') {
            $h .= '<div class="pill-row">';
            foreach ($unit->armorClasses as $ac) {
                if (in_array($ac->name, ['Unique Units', 'Unique unit'], true)) continue;
                $h .= self::classPill($ac->name);
            }
            $h .= '</div>';
        } else {
            $main = self::mainClass($unit);
            $h .= $main ? self::classPill($main) : self::pill(Yii::t('app', 'Other'), ['variant' => 'class']);
        }
        $h .= '</td>';
        }

        if (!in_array('age', $skip, true)) {
            $h .= '<td>' . ($unit->age ? self::ageLabel($unit->age) : '') . '</td>';
        }

        $atkIcon = $unit->melee_attack !== null ? 'melee_attack' : 'pierce_attack';
        $stat = function ($icon, $value) {
            return '<td class="unit-list-stat"><img src="/images/stats/' . $icon . '.png" alt="" class="stat-icon">' . Html::encode($value) . '</td>';
        };
        $h .= $stat('hit_points', $unit->hit_points ?? '-');
        $h .= $stat($atkIcon, $unit->melee_attack ?? $unit->pierce_attack ?? '-');
        $h .= $stat('melee_armor', $unit->armorString);
        $h .= $stat('movement_speed', $unit->speed ?? '-');
        $h .= '<td class="unit-list-cost">' . $unit->costHtml . '</td>';
        return $h;
    }

    /**
     * An age as plain text: its shield and its name, no pill around them. Hovering it shows what the age opens.
     * Every page shows ages through this one method, so their look changes in one place (CSS .age-label).
     */
    public static function ageLabel($age)
    {
        $age = UnitLine::age($age);
        $icon = self::ageIcon($age);
        $text = self::text('age:' . $age);
        $attrs = ' class="age-label"';
        if ($text) {
            $key = 't' . count(self::$tips);
            self::$tips[$key] = ['title' => Tr::name($age), 'icon' => $icon, 'iconPlain' => true, 'text' => $text];
            $attrs .= ' data-tip="' . $key . '" tabindex="0"';
        }
        $img = $icon ? '<img src="' . Html::encode($icon) . '" alt="" class="age-label-icon">' : '';
        return "<span$attrs>$img<span>" . Tr::nameHtml($age) . '</span></span>';
    }

    /** The armor class that best names what the unit is (Infantry, Cavalry, Ships ...), or null. */
    public static function mainClass(Unit $unit)
    {
        $names = array_map(function ($ac) { return $ac->name; }, $unit->armorClasses);
        foreach (self::MAIN_CLASS_ORDER as $ac) {
            if (in_array($ac, $names, true)) return $ac;
        }
        return null;
    }

    /** Registers the script that shows hover cards for every pill with a data-tip on the page. */
    public static function registerTips(View $view)
    {
        if (!self::$tips) return;
        $tipJson = Json::encode(self::$tips);
        // alt of a resource icon: the English name, or the translated one unitTip() adds as a third item
        $resAlt = Tr::isEnglish() ? 'c[0]' : 'c[2] || c[0]';
        $view->registerJs(<<<JS
(function() {
    var tips = $tipJson;
    var popup = document.createElement('div');
    popup.className = 'unit-popup pill-popup';
    document.body.appendChild(popup);
    var hideTimer = null, showTimer = null;

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function build(t) {
        var h = '<div class="unit-popup-header">';
        if (t.icon) h += '<div class="unit-popup-portrait' + (t.iconPlain ? ' is-plain' : '') + '"><img src="' + esc(t.icon) + '" alt=""></div>';
        h += '<div class="unit-popup-info"><div class="unit-popup-name">' + esc(t.title) + '</div>';
        if (t.subtitle) h += '<div class="unit-popup-type">' + esc(t.subtitle) + '</div>';
        h += '</div></div>';
        var body = '';
        if (t.text) body += '<p class="pill-popup-text">' + esc(t.text) + '</p>';
        if (t.stats && t.stats.length) {
            body += '<div class="pill-popup-stats">';
            t.stats.forEach(function (s) {
                body += '<span title="' + esc(s[2]) + '"><img src="/images/stats/' + esc(s[0]) + '.png" alt="" class="stat-icon">' + esc(s[1]) + '</span>';
            });
            body += '</div>';
        }
        if (t.cost && t.cost.length) {
            body += '<div class="pill-popup-stats">';
            t.cost.forEach(function (c) {
                body += '<span><img src="/images/resources/' + esc(c[0]) + '.png" alt="' + esc($resAlt) + '" class="res-icon"> ' + esc(c[1]) + '</span>';
            });
            body += '</div>';
        }
        if (body) h += '<div class="unit-popup-body">' + body + '</div>';
        return h;
    }

    function show(el) {
        var t = tips[el.getAttribute('data-tip')];
        if (!t) return;
        popup.innerHTML = build(t);
        popup.classList.add('visible');
        var rect = el.getBoundingClientRect();
        var sx = window.pageXOffset || document.documentElement.scrollLeft;
        var sy = window.pageYOffset || document.documentElement.scrollTop;
        var w = popup.offsetWidth;
        var left = rect.left + sx + rect.width / 2 - w / 2;
        left = Math.max(8, Math.min(left, document.documentElement.clientWidth - w - 8));
        popup.style.left = left + 'px';
        var top = rect.top + sy - popup.offsetHeight - 8;
        if (rect.top - popup.offsetHeight - 8 < 0) top = rect.bottom + sy + 8;
        popup.style.top = top + 'px';
    }

    function hide() { popup.classList.remove('visible'); }

    function enter(e) {
        var el = e.target.closest && e.target.closest('[data-tip]');
        if (!el) return;
        clearTimeout(hideTimer); clearTimeout(showTimer);
        showTimer = setTimeout(function() { show(el); }, e.type === 'focusin' ? 0 : 200);
    }
    function leave(e) {
        var el = e.target.closest && e.target.closest('[data-tip]');
        if (!el) return;
        clearTimeout(showTimer);
        hideTimer = setTimeout(hide, 150);
    }
    document.addEventListener('mouseenter', enter, true);
    document.addEventListener('mouseleave', leave, true);
    document.addEventListener('focusin', enter);
    document.addEventListener('focusout', leave);
})();
JS
        );
    }
}
