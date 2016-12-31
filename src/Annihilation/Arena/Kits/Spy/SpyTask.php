<?php

namespace Annihilation\Arena\Kits\Spy;

use Annihilation\Annihilation;
use Annihilation\Arena\Kits\Spy;
use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use Annihilation\Arena\Arena;
use Annihilation\Arena\Kits\Operative;
use pocketmine\utils\TextFormat;

class SpyTask extends PluginTask{

    public $kit;
    public $player;

    public function __construct(Player $p, Annihilation $plugin, Spy $kit) {
        parent::__construct($plugin);
        $this->kit = $kit;
        $this->player = $p;
    }

    public function onRun($currentTick) {
        if(isset($this->kit->players[strtolower($this->player->getName())])){
            $this->player->despawnFromAll();
            $this->player->sendMessage(TextFormat::YELLOW."You are now invisible.");
        }
    }
}