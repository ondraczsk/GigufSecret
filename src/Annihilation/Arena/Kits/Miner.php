<?php

namespace Annihilation\Arena\Kits;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\Player;
use Annihilation\Arena\Arena;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

class Miner{
    
    public $plugin;
    public $players;
    public $name;
    
    public function __construct(Arena $arena){
        $this->plugin = $arena;
        $this->name = 'miner';
    }
    
    public static function give(Player $p){
        /** @var Item[] $items */
        $items = [Item::get(268, 0, 1), Item::get(274, 0, 1), Item::get(271, 0, 1), Item::get(61, 0, 1), Item::get(345, 0, 1)];

        $ench = Enchantment::getEnchantment(Enchantment::TYPE_MINING_EFFICIENCY);
        $ench->setLevel(1);
        $items[1]->addEnchantment($ench);

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