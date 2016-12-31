<?php

namespace Annihilation\Arena\Kits;

use pocketmine\Player;
use pocketmine\block\Block;
use Annihilation\Arena\Arena;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use Annihilation\Arena\Kits\Operative\OperativeTask;
use pocketmine\utils\TextFormat;

class Operative implements Listener{
    
    public $plugin;
    public $players;
    public $blocks = [];
    public $name;
    
    public function __construct(Arena $arena){
        $this->plugin = $arena;
        $this->name = 'operative';
    }
    
    public static function give(Player $p){
        /** @var Item[] $items */
        $items = [Item::get(268, 0, 1), Item::get(270, 0, 1), Item::get(271, 0, 1), Item::get(88, 0, 1), Item::get(345, 0, 1)];
        foreach ($items as $i => $item){
            $item->setCustomName(TextFormat::GOLD."SoulBound");
            $p->getInventory()->setItem($i, $item);
        }
        for($i = 0; $i < 7; $i++){
            $p->getInventory()->setHotbarSlotIndex($i, $i);
        }

        return $items;
    }
    
    public function placed(Player $p){
        if(isset($this->blocks[strtolower($p->getName())])){
            return true;
        }
        return false;
    }
    
    public function onPlace(Player $p, Block $b){
        $p->sendMessage($this->plugin->plugin->getPrefix().TextFormat::YELLOW."You will be teleported back in 30 seconds...");
        $this->blocks[strtolower($p->getName())] = $b;
        $this->plugin->plugin->getServer()->getScheduler()->scheduleDelayedTask(new OperativeTask($p, $b, $this->plugin, $this), 600);
    }
    
    public function execute(array $data){
        $p = $data['player'];
        $pos = $data['pos'];
        $b = $data['block'];
        if($this->plugin->phase <= 0){
            return;
        }
        $item = Item::get(88, 0, 1);

        unset($this->blocks[strtolower($p->getName())]);
        $this->plugin->level->setBlock($pos, Block::get(0), true);
        if($this->plugin->inArena($p) && !$this->plugin->inLobby($p)){
            $p->getInventory()->addItem($item);
            $p->getInventory()->sendContents($p);
            $p->teleport($pos);
            return;
        }

        return;
    }
}