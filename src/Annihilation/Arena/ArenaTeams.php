<?php

namespace Annihilation\Arena;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use Annihilation\Arena\Arena;
use pocketmine\level\Position;

class ArenaTeams{
    private $plugin;
    public $teams = [];
    
    public function __construct(Arena $arena)
    {
        $this->createTeams();
        $this->plugin = $arena;
    }
    
    public function getNexusHp($team)
    {
        return $this->teams[$team]["nexus"];
    }
    
    public function setNexusHp($team, $hp)
    {
        return $this->teams[$team]["nexus"] = $hp;
    }
    
    public function getWinningTeam(){
        if($this->getNexusHp(1) > $this->getNexusHp(2) && $this->getNexusHp(1) > $this->getNexusHp(3) && $this->getNexusHp(1) > $this->getNexusHp(4)){
            return 1; //returns team id
        }
        if($this->getNexusHp(2) > $this->getNexusHp(1) && $this->getNexusHp(2) > $this->getNexusHp(3) && $this->getNexusHp(2) > $this->getNexusHp(4)){
            return 2; //returns team id
        }
        if($this->getNexusHp(3) > $this->getNexusHp(1) && $this->getNexusHp(3) > $this->getNexusHp(2) && $this->getNexusHp(3) > $this->getNexusHp(4)){
            return 3; //returns team id
        }
        if($this->getNexusHp(4) > $this->getNexusHp(2) && $this->getNexusHp(4) > $this->getNexusHp(3) && $this->getNexusHp(4) > $this->getNexusHp(1)){
            return 4; //returns team id
        }
    }
    
    public function createTeams(){
        $this->teams = [0 => ['color' => "§5", 'name' => "lobby"], 1 => ['nexus' => 75, 'color' => '§9', 'name' => "blue"], 2 => ['nexus' => 75, 'color' => '§c', 'name' => "red"], 3 => ['nexus' => 75, 'color' => '§e', 'name' => "yellow"], 4 => ['nexus' => 75, 'color' => '§a', 'name' => "green"]];
    }
    
    public function getPlayerTeam(Player $p){
        return isset($this->plugin->playersData[strtolower($p->getName())]['team']) ? $this->plugin->playersData[strtolower($p->getName())]['team'] : false;
    }
    
    public function addToTeam(Player $player, $team){
        $this->plugin->playersData[strtolower($player->getName())]['team'] = $team;
        if($team === 0){
            $player->setDisplayName($this->plugin->mtcore->getDisplayRank($player)." ".$this->teams[$team]['color'].$player->getName().TextFormat::WHITE);
            return;
        }
        $player->setNameTag($this->teams[$team]['color'].$player->getName().TextFormat::WHITE);
        $player->setDisplayName($this->plugin->mtcore->getDisplayRank($player)." ".$this->teams[$team]['color'].$player->getName().TextFormat::WHITE);
    }

    /**
     * @param $team
     * @return Player[]
     */
    public function getTeamPlayers($team){
        $players = [];
        foreach($this->plugin->playersData as $p){
            if(isset($p['team']) && $p['team'] === $team && isset($p['ins'])){
                $players[strtolower($p['ins']->getName())] = $p['ins'];
            }
        }
        return $players;
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
        $players = [];
        foreach($this->plugin->playersData as $p){
            if(isset($p['ins'])){
                $players[strtolower($p['ins']->getName())] = $p['ins'];
            }
        }
        return $players;
    }

    /**
     * @return Player[]
     */
    public function getAllPlayersInTeam(){
        $players = [];
        foreach($this->plugin->playersData as $p){
            if(isset($p['team']) && isset($p['ins'])){
                if($p['team'] > 0){
                    $players[strtolower($p['ins']->getName())] = $p['ins'];
                }
            }
        }
        return $players;
    }

    /**
     * @param $team
     * @return bool
     */
    public function isTeamFree($team){
        switch($team){
            case 1:
                if(count($this->getTeamPlayers(1)) - min(count($this->getTeamPlayers(2)), count($this->getTeamPlayers(3)), count($this->getTeamPlayers(4))) <= 2){
                    return true;
                }
                break;
            case 2:
                if(count($this->getTeamPlayers(2)) - min(count($this->getTeamPlayers(1)), count($this->getTeamPlayers(3)), count($this->getTeamPlayers(4))) <= 2){
                    return true;
                }
                break;
            case 3:
                if(count($this->getTeamPlayers(3)) - min(count($this->getTeamPlayers(2)), count($this->getTeamPlayers(1)), count($this->getTeamPlayers(4))) <= 2){
                    return true;
                }
                break;
            case 4:
                if((count($this->getTeamPlayers(4)) - min(count($this->getTeamPlayers(2)), count($this->getTeamPlayers(3)), count($this->getTeamPlayers(1)))) <= 2){
                    return true;
                }
                break;
        }
        return false;
    }
    
    public function getTeamName($team){
        return $this->teams[$team]['name'];
    }
    
    public function getTeamColor($team){
        return $this->teams[$team]['color'];
    }
    
    public function messageTeam($message, Player $player = null, $team = null){
        if($player === null){
            foreach($this->getTeamPlayers($team) as $p){
                if($p->isOnline()){
                    $p->sendMessage($message);
                }
            }
            return;
        }
        foreach($this->getTeamPlayers($this->getPlayerTeam($player)) as $p){
            if($player !== null){
                if($p->isOnline()){
                    $color = $this->getTeamColor($this->getPlayerTeam($player));
                    $p->sendMessage(TextFormat::GRAY."[{$color}Team".TextFormat::GRAY."]   ".$player->getDisplayName().TextFormat::DARK_AQUA." > ".$message);
                }
            }
        }
    }
    
    public function messageAllPlayers($message, Player $player = null){
        foreach($this->getAllPlayers() as $p){
            if($player !== null){
                if($p->isOnline()){
                    if($this->getPlayerTeam($player) === 0){
                        $p->sendMessage($player->getDisplayName().TextFormat::DARK_AQUA." > ".$message);
                        return;
                    }
                    $color = $this->getTeamColor($this->getPlayerTeam($player));
                    $p->sendMessage(TextFormat::GRAY."[{$color}All".TextFormat::GRAY."]   ".$player->getDisplayName().TextFormat::DARK_AQUA." > ".substr($message, 1));
                }
            }
            else{
                $p->sendMessage($message);
            }
        }
    }

    /**
     * @param $team
     * @return Position
     */
    public function getTeamSpawn($team){
        return new Position($this->plugin->data[$team."Spawn"]->x, $this->plugin->data[$team."Spawn"]->y, $this->plugin->data[$team."Spawn"]->z, $this->plugin->level);
    }
}

