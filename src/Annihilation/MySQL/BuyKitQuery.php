<?php

namespace Annihilation\MySQL;


use Annihilation\Annihilation;
use MTCore\MySQL\AsyncQuery;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BuyKitQuery extends AsyncQuery{

    const ACTION_BUY = 0;
    const ACTION_INFO = 1;

    private $kit;
    private $action;
    private $cost;

    public function __construct(Annihilation $plugin, string $player, string $kit, int $action){
        $this->player = (string) $player;
        $this->kit = (string) $kit;
        $this->action = (int) $action;
        //$this->cost = (int) $cost;
        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        $result = ["msg" => ""];

        $anniData = $this->getPlayer($this->player, "annihilation");

        if($this->action === self::ACTION_BUY){
            $prices = [
                "civilian" => 0,
                "miner" => 10000,
                "lumberjack" => 5000,
                "warrior" => 5000,
                "berserker" => 10000,
                "acrobat" => 10000,
                "archer" => 10000,
                "operative" => 10000,
                "handyman" => 0,
                "scout" => 10000
            ];

            $cost = $prices[$this->kit];

            if(stripos($anniData["kits"], $this->kit)){
                $result["msg"] = TextFormat::GREEN."» You have already purchased this kit";
            } else if($data["tokens"] < $cost){
                $result["msg"] = TextFormat::RED."» You haven't enough money"."\n".TextFormat::ITALIC.TextFormat::GRAY."Buy some credits at ".TextFormat::RESET.TextFormat::GREEN."bit.ly/mtBUY".TextFormat::ITALIC.TextFormat::GRAY." in section Ranks & Tokens";
            } else {
                $result["msg"] = Annihilation::getPrefix().TextFormat::GREEN."Purchased kit ".$this->kit." for ".TextFormat::AQUA.$cost.TextFormat::GREEN." tokens";

                $this->addKit($this->player, $this->kit);
                $this->addTokens($this->player, -$cost);

            }
        } else{
            $purchaseMessage = stripos($anniData["kits"], $this->kit) ? TextFormat::GREEN."» You have already purchased this kit" : TextFormat::YELLOW."» To buy this kit use a gold ingot";

            $infoMessage = Annihilation::$kits[$this->kit];

            $finalMessage = $purchaseMessage."\n".$infoMessage;

            $result["msg"] = $finalMessage;
        }

        $this->setResult($result);
    }

    public function onCompletion(Server $server){
        $result = $this->getResult();

        $p = $server->getPlayerExact($this->player);

        if(!$p instanceof Player || !$p->isOnline()){
            return;
        }

        $p->sendMessage($result["msg"]);
    }
}