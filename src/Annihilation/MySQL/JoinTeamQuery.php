<?php

namespace Annihilation\MySQL;

use Annihilation\Annihilation;
use MTCore\MTCore;
use MTCore\MySQL\AsyncQuery;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class JoinTeamQuery extends AsyncQuery{

    private $color;

    public function __construct(Annihilation $plugin, $player, $color){
        $this->player = $player;
        $this->color = $color;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        $rank = $data["rank"];

        $result = [];

        $result["name"] = MTCore::getDisplayRank($rank) . " " . $this->color . $this->player . TextFormat::RESET;

        $this->setResult($result);
    }

    public function onCompletion(Server $server){
        $p = $server->getPlayerExact($this->player);

        if(!$p instanceof Player || !$p->isOnline()){
            return;
        }

        $p->setNameTag($this->color.$this->player);
        $p->setDisplayName($this->getResult()["name"]);
    }
}