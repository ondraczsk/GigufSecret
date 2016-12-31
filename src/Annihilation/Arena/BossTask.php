<?php

namespace Annihilation\Arena;

use pocketmine\scheduler\Task;
use Annihilation\Arena\BossManager;

class BossTask extends Task{
    
    public $plugin;
    public $boss;
    
    public function __construct(BossManager $plugin, $boss){
        $this->plugin = $plugin;
        $this->boss = $boss;
    }
    
    public function onRun($currentTick){
        $this->plugin->spawnBoss($this->boss);
        if($this->plugin->task1 === $this){
            $this->plugin->task1 = null;
            return;
        }
        if($this->plugin->task2 === $this){
            $this->plugin->task2 = null;
        }
    }
}