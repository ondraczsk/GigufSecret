<?php

namespace Annihilation;

use pocketmine\scheduler\Task;
use Annihilation\MySQLManager;

class MySQLPingTask extends Task
{
    
    public $plugin;
		
    public function __construct(MySQLManager $plugin)
    {
        $this->plugin = $plugin;
    }
	

    public function onRun($currentTick)
    {
        $this->plugin->getDatabase()->ping();
    }
}