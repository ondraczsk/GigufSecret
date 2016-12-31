<?php

namespace Annihilation\MySQL;


use Annihilation\Annihilation;
use Annihilation\Arena\Arena;
use MTCore\MySQL\AsyncQuery;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class KitSelectQuery extends AsyncQuery{

    private $kit;

    public function __construct(Annihilation $plugin, $player, $kit){
        $this->player = $player;
        $this->kit = $kit;
        $this->table = "annihilation";

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        $kits = $data["kits"];

        $ranks = ["vip", "vip+", "extra", "banner", "youtuber"];

        $playerData = $this->getPlayer($this->player);
        $rank = $playerData["rank"];

        $hasRank = in_array(strtolower($rank), $ranks);
        $isPurchased = stripos(strtolower($kits), strtolower($this->kit)) !== false || $hasRank || strtolower($this->kit) == "handyman";

        $this->setResult([$isPurchased]);
    }

    public function onCompletion(Server $server){
        $result = $this->getResult()[0];

        $p = $server->getPlayerExact($this->player);

        if(!$p instanceof Player || !$p->isOnline()){
            return;
        }

        $plugin = $server->getPluginManager()->getPlugin("Annihilation");

        if($plugin instanceof Annihilation && $plugin->isEnabled()){
            /** @var Arena $arena */
            $arena = $plugin->getPlayerArena($p);

            if($arena instanceof Arena){
                if(!$result && !$p->isOp()){
                    $p->sendMessage(Annihilation::getPrefix().TextFormat::RED."You have not purchased this kit");
                } else{
                    //$arena->kitManager->onKitChange($p, $this->kit);
                    $arena->getPlayerData($p)->setNewKit($this->kit);
                    $p->sendMessage(Annihilation::getPrefix().TextFormat::GREEN.'Selected class '.TextFormat::BLUE."$this->kit");
                }
            }
        }
    }

}