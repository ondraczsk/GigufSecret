<?php

namespace Annihilation\Arena\Kits;

use pocketmine\Player;
use Annihilation\Arena\Arena;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class Handyman{
    
    public $plugin;
    public $players;
    public $name;
    
    public function __construct(Arena $arena){
        $this->plugin = $arena;
        $this->name = 'handyman';
    }
    
    public static function give(Player $p){
        /** @var Item[] $items */
        $items = [Item::get(268, 0, 1), Item::get(270, 0, 1), Item::get(271, 0, 1), Item::get(58, 0, 1), Item::get(345, 0, 1)];
        foreach ($items as $i => $item){
            $item->setCustomName(TextFormat::GOLD."SoulBound");
            $p->getInventory()->setItem($i, $item);
        }
        for($i = 0; $i < 7; $i++){
            $p->getInventory()->setHotbarSlotIndex($i, $i);
        }

        return $items;
    }
    
    public function calculateDamage($phase){
        switch($phase){
            case 2:
                $rnd = rand(1, 10);
                if($rnd === 1 || $rnd === 2){
                    return true;
                }
                break;
            case 3:
                $rnd = rand(1, 10);
                if($rnd === 1){
                    return true;
                }
                break;
            case 4:
                $rnd = rand(1, 100);
                if($rnd === 1 || $rnd === 2 || $rnd === 3 || $rnd === 4 || $rnd === 5 || $rnd === 6 || $rnd === 7){
                    return true;
                }
                break;
            case 5:
                $rnd = rand(1, 100);
                if($rnd === 1 || $rnd === 2 || $rnd === 3 || $rnd === 4 || $rnd === 5){
                    return true;
                }
                break;
        }
        return false;   
    }
}