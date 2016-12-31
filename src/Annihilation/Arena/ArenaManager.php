<?php

namespace Annihilation\Arena;

use Annihilation\Annihilation;
use Annihilation\Arena\Object\Color;
use Annihilation\Arena\Object\PlayerData;
use Annihilation\Arena\Object\Team;
use Annihilation\MySQL\JoinTeamQuery;
use MTCore\MTCore;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ArenaManager{

    /** @var Arena $plugin */
    private $plugin;

    /** @var Team[] $teams */
    public $teams = [];

    public function __construct(Arena $plugin){
        $this->plugin = $plugin;
    }

    private function getPlugin(){
        return $this->plugin;
    }

    public function getWinningTeam(){

    }

    public function registerTeams(){
        /*$this->teams = [1 => new Team(1, "blue", TextFormat::BLUE, 3361970,$this->plugin),
        2 => new Team(2, "red", TextFormat::RED, 10040115, $this->plugin),
        3 => new Team(3, "yellow", TextFormat::YELLOW, 15066419, $this->plugin),
        4 => new Team(4, "green", TextFormat::GREEN, 6717235, $this->plugin)];*/

        $this->teams = [1 => new Team(1, "blue", TextFormat::BLUE, Color::toDecimal(Color::BLUE),$this->plugin),
            2 => new Team(2, "red", TextFormat::RED, Color::toDecimal(Color::RED), $this->plugin),
            3 => new Team(3, "yellow", TextFormat::YELLOW, Color::toDecimal(Color::YELLOW), $this->plugin),
            4 => new Team(4, "green", TextFormat::GREEN, Color::toDecimal(Color::GREEN), $this->plugin)];
    }

    public function getPlayerTeam(Player $p){
        if($this->getPlayerData($p) instanceof PlayerData){
            return $this->getPlayerData($p)->getTeam();
        }
        return false;
    }

    public function addToTeam(Player $player, Team $team){
        $this->getPlayerData($player)->setTeam($team);
        $team->addPlayer($player);

        //new JoinTeamQuery($this->plugin->plugin->plugin, $player, $team->getColor());
        //$player->setNameTag($team->getColor().$player->getName().TextFormat::WHITE);
        //$player->setDisplayName($this->plugin->mtcore->getDisplayRank($player)." ".$team->getColor().$player->getName().TextFormat::WHITE);
    }

    /**
     * @param $team
     * @return Player[]
     */
    public function getTeamPlayers($team){
        return $this->getTeam($team)->getPlayers();
    }

    public function getTeam($team){
        return $this->teams[$team];
    }

    public function removeFromTeam(Player $player, $team){
        if(isset($this->plugin->playersData[strtolower($player->getName())]['team'])){
            unset($this->plugin->playersData[strtolower($player->getName())]['team']);
        }
    }

    /**
     * @return Player[]
     */
    public function getAllPlayers(){
        return $this->plugin->players;
    }

    /**
     * @return Player[]
     */
    public function getPlayersInTeams(){
        return array_merge($this->getTeam(1)->getPlayers(), $this->getTeam(2)->getPlayers(), $this->getTeam(3)->getPlayers(), $this->getTeam(4)->getPlayers());
    }

    public function messageAllPlayers($message, Player $player = null){
        if($player instanceof Player){
            if ($this->getPlayerTeam($player) === 0) {
                $msg = $player->getDisplayName() . TextFormat::DARK_AQUA . " > " . $message;
                return;
            }else{
                $color = $this->getPlayerTeam($player)->getColor();
                $msg = TextFormat::GRAY . "[{$color}All" . TextFormat::GRAY . "]   " . $player->getDisplayName() . TextFormat::DARK_AQUA . " > " . (isset($this->plugin->mtcore->chatColors[strtolower($player->getName())]) ? $this->plugin->mtcore->chatColors[strtolower($player->getName())] : "") . substr($message, 1);
            }

            foreach($this->getPlayersInTeams() as $p) {
                $p->sendMessage($msg);
            }

            Server::getInstance()->getLogger()->info($msg);
            return;
        }

        foreach($this->getAllPlayers() as $p) {
            $p->sendMessage($message);
        }
        Server::getInstance()->getLogger()->info($message);
    }

    /**
     * @param $team
     * @return Position
     */
    public function getTeamSpawn(Team $team){
        $team = $team->getId();
        return new Position($this->plugin->data[$team."Spawn"]->x, $this->plugin->data[$team."Spawn"]->y, $this->plugin->data[$team."Spawn"]->z, $this->plugin->level);
    }

    /**
     * @param Player $p
     * @return PlayerData|null
     */
    public function getPlayerData(Player $p){
        return isset($this->plugin->playersData[strtolower($p->getName())]) ? $this->plugin->playersData[strtolower($p->getName())] : null;
    }

    /**
     * @param Player $p
     * @return PlayerData
     */
    public function createPlayerData(Player $p){
        $data = new PlayerData($p->getName());
        $this->plugin->playersData[strtolower($p->getName())] = $data;

        return $data;
    }

    public function inArena(Player $p){
        return isset($this->plugin->players[strtolower($p->getName())]);
    }

    public function isTeamFree(Team $team){
        $players = count($team->getPlayers());

        /** @var Team[] $teams */
        $teams = [];

        foreach($this->teams as $teamm){
            if($teamm->getId() !== $team->getId()){
                if($this->plugin->phase >= 1 && $teamm->getNexus()->getHealth() <= 0){
                    continue;
                }

                $teams[] = count($teamm->getPlayers());
            }
        }

        switch(count($teams)){
            case 1:
                return $players - $teams[0] < 3;
            case 2:
                return $players - min($teams[0], $teams[1]) < 3;
            case 3:
                return $players - min($teams[0], $teams[1], $teams[2]) < 3;
            default:
                return false;
        }
    }
}