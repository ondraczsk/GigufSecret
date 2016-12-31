<?php

namespace Annihilation\Arena\Kits;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\Player;
use Annihilation\Arena\Arena;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class Archer{
    
    public $plugin;
    public $players;
    public $name;
    
    public function __construct(Arena $arena){
        $this->plugin = $arena;
        $this->name = 'archer';
    }
    
    public static function give(Player $p){
        /** @var Item[] $items */
        $items = [Item::get(261, 0, 1), Item::get(270, 0, 1), Item::get(271, 0, 1), Item::get(373, 21, 1), Item::get(345, 0, 1), Item::get(262, 0, 16)];
        foreach ($items as $i => $item){
            $item->setCustomName(TextFormat::GOLD."SoulBound");
            $p->getInventory()->setItem($i, $item);
        }
        for($i = 0; $i < 5; $i++){
            $p->getInventory()->setHotbarSlotIndex($i, $i);
        }
        return $items;
    }
}