<?php

namespace Annihilation\Arena\Kits\Operative;

use pocketmine\scheduler\Task;
use pocketmine\Player;
use pocketmine\math\Vector3;
use Annihilation\Arena\Arena;
use Annihilation\Arena\Kits\Operative;
use pocketmine\block\Block;

class OperativeTask extends Task{
    
    public $data = [];
    public $plugin;
    public $kit;
    
    public function __construct(Player $p, Block $b, Arena $plugin, Operative $kit) {
        $this->data['player'] = $p;
        $this->data['pos'] = new Vector3($b->x, $b->y, $b->z);
        $this->data['block'] = $b;
        $this->plugin = $plugin;
        $this->kit = $kit;
    }
    
    public function onRun($currentTick) {
        $this->kit->execute($this->data);
    }
}