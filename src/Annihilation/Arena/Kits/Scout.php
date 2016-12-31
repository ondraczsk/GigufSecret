<?php

namespace Annihilation\Arena\Kits;

use pocketmine\Player;
use Annihilation\Arena\Arena;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\entity\Effect;
use pocketmine\utils\TextFormat;

class Scout implements Listener{
    
    public $plugin;
    public $players;
    public $name;
    
    public function __construct(Arena $arena){
        $this->plugin = $arena;
        $this->name = 'scout';
    }
    
    public static function give(Player $p){
        /** @var Item[] $items */
        $items = [Item::get(Item::GOLDEN_SWORD, 0, 1), Item::get(Item::FISHING_ROD, 0, 1), Item::get(Item::WOODEN_PICKAXE, 0, 1), Item::get(Item::WOODEN_AXE, 0, 1), Item::get(Item::CRAFTING_TABLE, 0, 1)];
        foreach ($items as $i => $item){
            $item->setCustomName(TextFormat::GOLD."SoulBound");
            $p->getInventory()->setItem($i, $item);
        }
        for($i = 0; $i < 7; $i++){
            $p->getInventory()->setHotbarSlotIndex($i, $i);
        }

        //$p->setSpeed(2);
        //$p->getAttribute()->sendAll();

        return $items;
    }
}