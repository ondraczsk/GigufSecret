<?php

namespace Annihilation\Task;

use Annihilation\Annihilation;
use Annihilation\Arena\Arena;
use Annihilation\WorldManager;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class WorldCopyTask extends AsyncTask{

    private $map;
    private $arena;
    private $dataPath;
    private $force;

    public function __construct(Annihilation $plugin, $map, $arena, $dataPath, $force = false){
        $this->map = $map;
        $this->arena = $arena;
        $this->dataPath = $dataPath;
        $this->force = $force;

        $plugin->getServer()->getScheduler()->scheduleAsyncTask($this);
    }

    public function onRun(){
        if(!file_exists($this->dataPath."worlds/annihilation")){
            WorldManager::xcopy("/root/worlds/", $this->dataPath);
        }

        WorldManager::resetWorld($this->map, $this->dataPath);
    }

    public function onCompletion(Server $server){
        $server->loadLevel($this->map);

        $plugin = $server->getPluginManager()->getPlugin("Annihilation");

        if($plugin instanceof Annihilation && $plugin->isEnabled()){
            /** @var Arena $arena */
            $arena = $plugin->ins[$this->arena];

            $arena->isMapLoaded = true;

            if($this->force){
                $arena->startGame(true);
            }
        }
    }

}