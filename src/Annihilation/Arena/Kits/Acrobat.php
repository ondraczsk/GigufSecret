<?php

namespace Annihilation\Arena\Kits;

use pocketmine\Player;
use Annihilation\Arena\Arena;
use pocketmine\item\Item;
use pocketmine\entity\Effect;
use pocketmine\utils\TextFormat;

class Acrobat{
    
    public $plugin;
    public $players;
    public $name;
    
    public function __construct(Arena $arena){
        $this->plugin = $arena;
        $this->name = 'acrobat';
    }
    
    public static function give(Player $p){
        /** @var Item[] $items */
        $items = [Item::get(268, 0, 1), Item::get(270, 0, 1), Item::get(271, 0, 1), Item::get(58, 0, 1), Item::get(345, 0, 1), Item::get(261, 0, 1), Item::get(262, 0, 6)];
        foreach ($items as $i => $item){
            $item->setCustomName(TextFormat::GOLD."SoulBound");
            $p->getInventory()->setItem($i, $item);
        }
        for($i = 0; $i < 10; $i++){
            $p->getInventory()->setHotbarSlotIndex($i, $i);
        }

        $eff = Effect::getEffect(8);
        $eff->setDuration(999999999999999999999);
        $eff->setAmplifier(1);
        $eff->setVisible(false);
        $p->addEffect($eff);

        return $items;
    }
}