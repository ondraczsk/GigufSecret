<?php

namespace Annihilation\Arena\Kits;

use pocketmine\Player;
use Annihilation\Arena\Arena;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class Warrior implements Listener{
    
    public $plugin;
    public $players;
    public $name;
    
    public function __construct(Arena $arena){
        $this->plugin = $arena;
        $this->name = 'warrior';
    }
    
    public static function give(Player $p){
        /** @var Item[] $items */
        $items = [Item::get(272, 0, 1), Item::get(270, 0, 1), Item::get(271, 0, 1), Item::get(373, 21, 1), Item::get(345, 0, 1)];
        foreach ($items as $i => $item){
            $item->setCustomName(TextFormat::GOLD."SoulBound");
            $p->getInventory()->setItem($i, $item);
        }
        for($i = 0; $i < 7; $i++){
            $p->getInventory()->setHotbarSlotIndex($i, $i);
        }

        return $items;
    }
}