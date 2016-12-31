<?php

namespace Annihilation\MySQL;


use Annihilation\Annihilation;
use MTCore\MySQL\AsyncQuery;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class StatsQuery extends AsyncQuery{

    public function __construct(Annihilation $plugin, $player){
        $this->player = $player;
        $this->table = "annihilation";

        parent::__construct($plugin);
    }

    public function onQuery(array $data)
    {
        $this->setResult([TextFormat::BLUE . "> Your " . TextFormat::GOLD . TextFormat::BOLD . "Annihilation" . TextFormat::RESET . TextFormat::BLUE . " stats " . TextFormat::BLUE . " <\n"
            . TextFormat::DARK_GREEN . "Kills: " . TextFormat::DARK_PURPLE . $data["kills"] . "\n"
            . TextFormat::DARK_GREEN . "Deaths: " . TextFormat::DARK_PURPLE . $data["deaths"] . "\n"
            . TextFormat::DARK_GREEN . "Wins: " . TextFormat::DARK_PURPLE . $data["wins"] . "\n"
            . TextFormat::DARK_GREEN . "Losses: " . TextFormat::DARK_PURPLE . $data["losses"] . "\n"
            . TextFormat::DARK_GREEN . "Nexuses destroyed: " . TextFormat::DARK_PURPLE . $data["nexuses"] . "\n"
            . TextFormat::DARK_GREEN . "Nexus damaged: " . TextFormat::DARK_PURPLE . $data["nexusdmg"] . "\n"
            . TextFormat::GRAY . "---------------------"]);
    }

    public function onCompletion(Server $server){
        $p = $server->getPlayerExact($this->player);

        if(!$p instanceof Player || !$p->isOnline()){
            return;
        }

        $p->sendMessage($this->getResult()[0]);
    }
}