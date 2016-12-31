<?php

namespace Annihilation\Arena\Kits;

use pocketmine\Player;
use Annihilation\Arena\Arena;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use Annihilation\Arena\Kits\Spy\SpyTask;
use pocketmine\utils\TextFormat;

class Spy implements Listener{
    
    public $plugin;
    public $name;

    public $players = [];
    
    public function __construct(Arena $arena){
        $this->plugin = $arena;
        $this->name = 'spy';
    }
    
    public static function give(Player $p){
        /** @var Item[] $items */
        $items = [Item::get(283, 0, 1), Item::get(270, 0, 1), Item::get(271, 0, 1), Item::get(58, 0, 1), Item::get(345, 0, 1)];
        foreach ($items as $i => $item){
            $item->setCustomName(TextFormat::GOLD."SoulBound");
            $p->getInventory()->setItem($i, $item);
        }
        for($i = 0; $i < 7; $i++){
            $p->getInventory()->setHotbarSlotIndex($i, $i);
        }

        return $items;
    }

    public function onSneak(Player $p){
        $this->plugin->plugin->getServer()->getScheduler()->scheduleDelayedTask(new SpyTask($p, $this->plugin->plugin, $this), 100);
        $this->players[strtolower($p->getName())] = true;
    }

    public function onUnsneak(Player $p){
        if(isset($this->players[strtolower($p->getName())])){
            unset($this->players[strtolower($p->getName())]);
            $p->spawnToAll();
            $p->sendMessage(TextFormat::YELLOW."You are no longer invisible.");
        }
    }
}